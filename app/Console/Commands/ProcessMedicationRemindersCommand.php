<?php

namespace App\Console\Commands;

use App\Models\MedicationTracker;
use App\Models\PatientComplianceAlert;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessMedicationRemindersCommand extends Command
{
    protected $signature = 'patient:process-medication-reminders';

    protected $description = 'Send medication reminders and detect non-compliance for patient medication trackers.';

    public function __construct(private PushNotificationService $pushNotificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $now = now();
        $graceCutoff = $now->copy()->subMinutes(5);

        $trackers = MedicationTracker::query()
            ->with(['prescription', 'patient.user', 'doctor', 'doseLogs'])
            ->where('status', 'active')
            ->get();

        foreach ($trackers as $tracker) {
            DB::transaction(function () use ($tracker, $graceCutoff, $now) {
                $updatedTracker = MedicationTracker::query()
                    ->with(['prescription', 'patient.user', 'doctor'])
                    ->lockForUpdate()
                    ->find($tracker->id);

                if (!$updatedTracker || $updatedTracker->status !== 'active') {
                    return;
                }

                $updatedTracker->doseLogs()
                    ->where('status', 'pending')
                    ->where('scheduled_at', '<=', $graceCutoff)
                    ->update(['status' => 'missed', 'updated_at' => now()]);

                $consecutiveMissed = $this->calculateConsecutiveMissedDoses($updatedTracker->id);
                $updatedTracker->consecutive_missed_doses = $consecutiveMissed;

                if ($consecutiveMissed >= 5) {
                    $updatedTracker->status = 'non_compliant';
                    $updatedTracker->non_compliant_at = $now;
                    $updatedTracker->next_dose_at = null;
                    $updatedTracker->save();

                    $this->createDoctorComplianceAlert($updatedTracker);
                    $this->notifyDoctorOfNonCompliance($updatedTracker);
                    return;
                }

                $dueDose = $updatedTracker->doseLogs()
                    ->where('status', 'pending')
                    ->where('scheduled_at', '<=', $now)
                    ->orderBy('scheduled_at')
                    ->first();

                $nextDose = $updatedTracker->doseLogs()
                    ->where('status', 'pending')
                    ->orderBy('scheduled_at')
                    ->first();

                $updatedTracker->next_dose_at = $nextDose?->scheduled_at;
                $updatedTracker->save();

                if (!$dueDose || !$updatedTracker->prescription || !$updatedTracker->patient) {
                    return;
                }

                $medicineName = $updatedTracker->prescription->medication_name ?? 'Medication';
                $doseDescription = $updatedTracker->prescription->dose_description
                    ?: $updatedTracker->prescription->dosage
                    ?: 'Dose';
                $foodRelation = $updatedTracker->prescription->food_relation ?? '';
                $body = "حان موعد جرعة {$medicineName} ({$doseDescription}) - {$foodRelation}";

                $this->pushNotificationService->sendToPatient(
                    $updatedTracker->patient_id,
                    'Dose Reminder',
                    $body,
                    [
                        'type' => 'MEDICATION_REMINDER',
                        'medication_id' => (string) $updatedTracker->id,
                        'title' => 'Dose Reminder',
                        'actions' => ['Taken', 'Snooze'],
                    ]
                );
            });
        }

        return self::SUCCESS;
    }

    private function calculateConsecutiveMissedDoses(int $trackerId): int
    {
        $logs = DB::table('dose_logs')
            ->select(['status'])
            ->where('medication_tracker_id', $trackerId)
            ->whereIn('status', ['missed', 'taken'])
            ->orderByDesc('scheduled_at')
            ->get();

        $consecutive = 0;
        foreach ($logs as $log) {
            if ($log->status === 'missed') {
                $consecutive++;
            } else {
                break;
            }
        }

        return $consecutive;
    }

    private function createDoctorComplianceAlert(MedicationTracker $tracker): void
    {
        if (!$tracker->doctor_id || !$tracker->patient_id) {
            return;
        }

        $existing = PatientComplianceAlert::query()
            ->where('medication_tracker_id', $tracker->id)
            ->where('alert_type', 'medication_non_compliant')
            ->first();

        if ($existing) {
            return;
        }

        $medicineName = $tracker->prescription?->medication_name ?? 'Medication';
        PatientComplianceAlert::create([
            'patient_id' => $tracker->patient_id,
            'doctor_id' => $tracker->doctor_id,
            'medication_tracker_id' => $tracker->id,
            'alert_type' => 'medication_non_compliant',
            'message' => "Patient became non-compliant for {$medicineName}",
            'is_read' => false,
            'triggered_at' => now(),
        ]);
    }

    private function notifyDoctorOfNonCompliance(MedicationTracker $tracker): void
    {
        if (!$tracker->doctor_id || !$tracker->patient || !$tracker->prescription) {
            return;
        }

        $patientName = $tracker->patient->user?->full_name ?? 'Patient';
        $medicineName = $tracker->prescription->medication_name ?? 'medication';

        $this->pushNotificationService->sendToDoctor(
            $tracker->doctor_id,
            'Patient Non-Compliance Alert',
            "{$patientName} missed 5 consecutive doses for {$medicineName}."
        );
    }
}
