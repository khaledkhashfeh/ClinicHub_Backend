<?php

namespace App\Console\Commands;

use App\Models\LabRequest;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessLabRemindersCommand extends Command
{
    protected $signature = 'patient:process-lab-reminders';

    protected $description = 'Send lab test reminders every two days for pending tests.';

    public function __construct(private PushNotificationService $pushNotificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $maxReminders = 5;
        $now = now();

        $pendingLabRequests = LabRequest::query()
            ->with(['visitRecord.patient'])
            ->where('status', 'pending')
            ->where('reminders_sent', '<', $maxReminders)
            ->get();

        foreach ($pendingLabRequests as $labRequest) {
            if (!$labRequest->visitRecord || !$labRequest->visitRecord->patient_id) {
                continue;
            }

            $baseDate = $labRequest->created_at ?? $now;
            $nextReminderDueAt = $baseDate->copy()->addDays((((int) $labRequest->reminders_sent) + 1) * 2);

            if ($nextReminderDueAt->greaterThan($now)) {
                continue;
            }

            DB::transaction(function () use ($labRequest, $maxReminders) {
                $locked = LabRequest::query()->lockForUpdate()->find($labRequest->id);
                if (!$locked || $locked->status !== 'pending') {
                    return;
                }

                $newCount = ((int) $locked->reminders_sent) + 1;

                $this->pushNotificationService->sendToPatient(
                    $locked->visitRecord->patient_id,
                    'Lab Reminder',
                    "ذكرى ودية: هل قمت بإجراء تحليل {$locked->test_name}؟ يمكنك رفع النتيجة هنا.",
                    [
                        'type' => 'LAB_REMINDER',
                        'test_id' => (string) ($locked->test_id ?? $locked->id),
                        'reminders_count' => $newCount,
                    ]
                );

                $locked->reminders_sent = $newCount;
                if ($newCount >= $maxReminders) {
                    $locked->status = 'reminder_stopped';
                }
                $locked->save();
            });
        }

        return self::SUCCESS;
    }
}
