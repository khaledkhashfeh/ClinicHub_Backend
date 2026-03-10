<?php

namespace App\Http\Controllers;

use App\Models\DoseLog;
use App\Models\LabRequest;
use App\Models\MedicationTracker;
use App\Services\ImageKitService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PatientJourneyController extends Controller
{
    private ImageKitService $imageKitService;

    public function __construct(ImageKitService $imageKitService)
    {
        $this->imageKitService = $imageKitService;
    }

    public function getLabTests(Request $request): JsonResponse
    {
        $patient = $this->authenticatedPatient();
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Only patients can access lab tests',
            ], 403);
        }

        $labTests = LabRequest::query()
            ->whereHas('visitRecord', function ($query) use ($patient) {
                $query->where('patient_id', $patient->id);
            })
            ->whereIn('status', ['pending', 'completed', 'reminder_stopped'])
            ->orderByDesc('created_at')
            ->get();

        $payload = $labTests->map(function (LabRequest $labTest) {
            return [
                'id' => $labTest->id,
                'test_id' => $labTest->test_id,
                'test_name' => $labTest->test_name,
                'status' => $labTest->status,
                'instructions' => $labTest->instructions,
                'is_urgent' => in_array($labTest->priority, ['urgent', 'stat'], true),
                'request_date' => optional($labTest->created_at)->toDateString(),
                'reminders_sent' => $labTest->reminders_sent ?? 0,
            ];
        })->values();

        return response()->json($payload);
    }

    public function uploadLabTestResult($id, Request $request): JsonResponse
    {
        $patient = $this->authenticatedPatient();
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Only patients can upload lab results',
            ], 403);
        }

        $validated = $request->validate([
            'file_url' => 'nullable|url|max:2048',
            'file_type' => 'nullable|in:image,pdf',
            'file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'completion_date' => 'required|date',
        ]);

        $labTest = LabRequest::query()
            ->with('visitRecord')
            ->where('id', $id)
            ->first();

        if (!$labTest) {
            return response()->json([
                'success' => false,
                'message' => 'Lab test request not found',
            ], 404);
        }

        if ((int) ($labTest->visitRecord?->patient_id ?? 0) !== (int) $patient->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to upload this lab test result',
            ], 403);
        }

        $uploadedFile = $request->file('file');
        $fileUrl = $validated['file_url'] ?? null;
        $fileType = $validated['file_type'] ?? null;

        if ($uploadedFile) {
            $uploadResult = $this->imageKitService->upload(
                $uploadedFile,
                "patients/{$patient->id}/lab-results"
            );
            $fileUrl = $uploadResult['url'];
            $fileType = $this->resolveFileType($uploadedFile->getClientOriginalExtension());
        }

        if (!$fileUrl) {
            return response()->json([
                'success' => false,
                'message' => 'Either file or file_url is required',
            ], 422);
        }

        if (!$fileType) {
            $fileType = $this->resolveFileTypeFromUrl($fileUrl);
        }

        if (!$fileType) {
            return response()->json([
                'success' => false,
                'message' => 'file_type is required when file_url is provided',
            ], 422);
        }

        $labTest->update([
            'status' => 'completed',
            'result_file_url' => $fileUrl,
            'result_file_type' => $fileType,
            'completed_at' => Carbon::parse($validated['completion_date']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lab test result uploaded successfully',
            'data' => [
                'id' => $labTest->id,
                'status' => $labTest->status,
            ],
        ], 200);
    }

    public function getMedications(Request $request): JsonResponse
    {
        $patient = $this->authenticatedPatient();
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Only patients can access medications',
            ], 403);
        }

        $trackers = MedicationTracker::query()
            ->with(['prescription'])
            ->where('patient_id', $patient->id)
            ->whereIn('status', ['waiting_purchase', 'active', 'non_compliant'])
            ->orderByDesc('created_at')
            ->get();

        $payload = $trackers->map(function (MedicationTracker $tracker) {
            $prescription = $tracker->prescription;
            $totalDoses = max((int) $tracker->total_doses, 0);
            $takenDoses = max((int) $tracker->taken_doses, 0);
            $percentage = $totalDoses > 0 ? round(($takenDoses / $totalDoses) * 100, 1) : 0.0;

            return [
                'medication_id' => $tracker->id,
                'medicine_name' => $prescription?->medication_name,
                'dose_description' => $prescription?->dose_description ?? $prescription?->dosage,
                'status' => $tracker->status,
                'progress' => [
                    'total_doses' => $totalDoses,
                    'taken_doses' => $takenDoses,
                    'percentage' => $percentage,
                ],
                'timing' => [
                    'frequency_per_day' => $prescription?->daily_frequency,
                    'interval_hours' => $prescription?->hourly_interval,
                    'food_relation' => $prescription?->food_relation,
                ],
                'next_dose_at' => $tracker->status === 'active' && $tracker->next_dose_at
                    ? $tracker->next_dose_at->toISOString()
                    : null,
            ];
        })->values();

        return response()->json($payload);
    }

    public function activateMedication($id, Request $request): JsonResponse
    {
        $patient = $this->authenticatedPatient();
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Only patients can activate medications',
            ], 403);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
        ]);

        $tracker = MedicationTracker::with('prescription')->find($id);

        if (!$tracker) {
            return response()->json([
                'success' => false,
                'message' => 'Medication tracker not found',
            ], 404);
        }

        if ((int) $tracker->patient_id !== (int) $patient->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to activate this medication',
            ], 403);
        }

        if ($tracker->status === 'finished') {
            return response()->json([
                'success' => false,
                'message' => 'This medication has already been finished',
            ], 400);
        }

        $startAt = Carbon::parse($validated['start_date']);
        $totalDoses = $this->calculateTotalDoses($tracker);
        $intervalMinutes = $this->resolveIntervalMinutes($tracker);

        DB::transaction(function () use ($tracker, $startAt, $totalDoses, $intervalMinutes) {
            $tracker->doseLogs()->delete();

            $logs = [];
            for ($i = 0; $i < $totalDoses; $i++) {
                $logs[] = [
                    'medication_tracker_id' => $tracker->id,
                    'scheduled_at' => $startAt->copy()->addMinutes($i * $intervalMinutes),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($logs)) {
                DoseLog::insert($logs);
            }

            $nextDoseAt = $totalDoses > 0 ? $startAt->copy() : null;

            $tracker->update([
                'status' => 'active',
                'start_at' => $startAt,
                'total_doses' => $totalDoses,
                'taken_doses' => 0,
                'next_dose_at' => $nextDoseAt,
                'consecutive_missed_doses' => 0,
                'non_compliant_at' => null,
            ]);
        });

        $tracker->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Medication activated successfully',
            'data' => [
                'medication_id' => $tracker->id,
                'status' => $tracker->status,
                'total_doses' => $tracker->total_doses,
                'next_dose_at' => $tracker->next_dose_at?->toISOString(),
            ],
        ], 200);
    }

    public function trackDose($id, Request $request): JsonResponse
    {
        $patient = $this->authenticatedPatient();
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Only patients can track doses',
            ], 403);
        }

        $tracker = MedicationTracker::with('doseLogs')->find($id);

        if (!$tracker) {
            return response()->json([
                'success' => false,
                'message' => 'Medication tracker not found',
            ], 404);
        }

        if ((int) $tracker->patient_id !== (int) $patient->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to track this medication',
            ], 403);
        }

        if ($tracker->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Medication is not active',
            ], 400);
        }

        $now = now();

        $responseData = DB::transaction(function () use ($tracker, $now) {
            $dueDose = $tracker->doseLogs()
                ->where('status', 'pending')
                ->where('scheduled_at', '<=', $now)
                ->orderBy('scheduled_at')
                ->first();

            if (!$dueDose) {
                $dueDose = $tracker->doseLogs()
                    ->where('status', 'pending')
                    ->orderBy('scheduled_at')
                    ->first();
            }

            if (!$dueDose) {
                return null;
            }

            $dueDose->update([
                'status' => 'taken',
                'taken_at' => $now,
                'action_source' => 'patient_manual',
            ]);

            $takenDoses = $tracker->doseLogs()
                ->where('status', 'taken')
                ->count();

            $nextPending = $tracker->doseLogs()
                ->where('status', 'pending')
                ->orderBy('scheduled_at')
                ->first();

            $remaining = max(((int) $tracker->total_doses) - $takenDoses, 0);
            $nextDoseAt = $nextPending?->scheduled_at;
            $newStatus = $remaining === 0 ? 'finished' : 'active';

            $tracker->update([
                'taken_doses' => $takenDoses,
                'status' => $newStatus,
                'next_dose_at' => $remaining === 0 ? null : $nextDoseAt,
                'consecutive_missed_doses' => 0,
            ]);

            $percentage = ((int) $tracker->total_doses) > 0
                ? round(($takenDoses / (int) $tracker->total_doses) * 100, 1)
                : 0.0;

            return [
                'status' => 'success',
                'new_progress_percentage' => $percentage,
                'doses_remaining' => $remaining,
                'next_dose_at' => $remaining === 0 || !$nextDoseAt
                    ? null
                    : Carbon::parse($nextDoseAt)->toISOString(),
            ];
        });

        if (!$responseData) {
            return response()->json([
                'success' => false,
                'message' => 'No pending doses to track',
            ], 400);
        }

        return response()->json($responseData, 200);
    }

    private function authenticatedPatient()
    {
        $user = Auth::user();
        if (!$user || !$user->patient) {
            return null;
        }

        return $user->patient;
    }

    private function calculateTotalDoses(MedicationTracker $tracker): int
    {
        $prescription = $tracker->prescription;
        if (!$prescription) {
            return 1;
        }

        $frequency = (int) ($prescription->daily_frequency ?? 0);
        $durationDays = $this->extractDurationDays((string) ($prescription->duration ?? ''));

        if ($frequency > 0 && $durationDays > 0) {
            return max($frequency * $durationDays, 1);
        }

        return max((int) ($prescription->total_quantity ?? 1), 1);
    }

    private function resolveIntervalMinutes(MedicationTracker $tracker): int
    {
        $prescription = $tracker->prescription;
        if (!$prescription) {
            return 24 * 60;
        }

        $hourlyInterval = (int) ($prescription->hourly_interval ?? 0);
        if ($hourlyInterval > 0) {
            return $hourlyInterval * 60;
        }

        $frequency = (int) ($prescription->daily_frequency ?? 0);
        if ($frequency > 0) {
            return max((int) round((24 * 60) / $frequency), 1);
        }

        return 24 * 60;
    }

    private function extractDurationDays(string $duration): int
    {
        if (preg_match('/(\d+)/', $duration, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function resolveFileType(string $extension): ?string
    {
        $extension = strtolower($extension);
        if ($extension === 'pdf') {
            return 'pdf';
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return 'image';
        }

        return null;
    }

    private function resolveFileTypeFromUrl(string $url): ?string
    {
        $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION);
        if ($extension === '') {
            return null;
        }

        return $this->resolveFileType($extension);
    }
}
