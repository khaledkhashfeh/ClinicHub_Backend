<?php

namespace App\Http\Controllers;

use App\Http\Requests\Appointment\DoctorWorkSettingsRequest;
use App\Http\Requests\Appointment\GenerateSlotsRequest;
use App\Http\Requests\Appointment\WeeklyScheduleRequest;
use App\Http\Requests\Appointment\OverrideRequest;
use App\Http\Requests\Appointment\CreateManualSlotsRequest;
use App\Models\Appointment;
use App\Models\ClinicDoctor;
use App\Models\DoctorClinicSchedule;
use App\Models\LabRequest;
use App\Models\LabResult;
use App\Models\MedicationTracker;
use App\Models\MedicalFile;
use App\Models\Prescription;
use App\Models\ScheduleOverride;
use App\Models\ScheduleSlot;
use App\Models\VisitDiagnosis;
use App\Models\VisitRecord;
use App\Models\WaitingList;
use App\Services\ImageKitService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppointmentsController extends Controller
{
    private ImageKitService $imageKitService;

    public function __construct(ImageKitService $imageKitService)
    {
        $this->imageKitService = $imageKitService;
    }

    /**
     * Set doctor's work settings within a specific clinic
     * 
     * This function updates the work settings for a doctor at a specific clinic,
     * including method selection, appointment period, and queue settings.
     */
    public function setDoctorWorkSettings(DoctorWorkSettingsRequest $request)
    {
        $validatedData = $request->validated();

        // Find the clinic-doctor relationship
        $connection = ClinicDoctor::where('clinic_id', $validatedData['clinic_id'])
                                    ->where('doctor_id', $validatedData['doctor_id'])
                                    ->first();

        if (!$connection) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor is not associated with the specified clinic'
            ], 404);
        }

        if ((int) $connection->method_id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Weekly schedules are only allowed for method 1 (Auto scheduling)'
            ], 403);
        }

        try {
            // Update the work settings
                $connection->method_id = $validatedData['method_id'];
                $connection->appointment_period = $validatedData['appointment_period'];
            $connection->queue = $validatedData['queue'];
            
            // Set queue_number only if queue is enabled, otherwise set to null
            if ($validatedData['queue']) {
                $connection->queue_number = $validatedData['queue_number'] ?? null;
            } else {
                $connection->queue_number = null;
            }
            
                $connection->save();

            // Reload the model with relationships for response
            $connection->load(['clinic', 'doctor', 'method']);

            return response()->json([
                'success' => true,
                'message' => 'Doctor work settings updated successfully',
                'data' => [
                    'clinic_id' => $connection->clinic_id,
                    'doctor_id' => $connection->doctor_id,
                    'method_id' => $connection->method_id,
                    'appointment_period' => $connection->appointment_period,
                    'queue' => $connection->queue,
                    'queue_number' => $connection->queue_number,
                    'clinic' => $connection->clinic,
                    'doctor' => $connection->doctor,
                    'method' => $connection->method,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update doctor work settings',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating settings'
            ], 500);
        }
        }

    /**
     * Create or update a weekly schedule template for a doctor in a clinic.
     */
    public function setWeeklySchedule(WeeklyScheduleRequest $request)
    {
        $validatedData = $request->validated();
        $weeklySchedule = $validatedData['weekly_schedule'];

        // Ensure doctor is associated with the clinic
        $connection = ClinicDoctor::where('clinic_id', $validatedData['clinic_id'])
                                    ->where('doctor_id', $validatedData['doctor_id'])
                                    ->first();

        if (!$connection) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor is not associated with the specified clinic'
            ], 404);
        }

        // Validate weekly schedule uniqueness and time ranges
        $days = array_column($weeklySchedule, 'day_of_week');
        if (count($days) !== count(array_unique($days))) {
            return response()->json([
                'success' => false,
                'message' => 'Each day_of_week must be unique in weekly_schedule'
            ], 422);
        }

        foreach ($weeklySchedule as $day) {
            if (strtotime($day['start_time']) >= strtotime($day['end_time'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'start_time must be before end_time'
                ], 422);
            }

            if (!empty($day['breaks'])) {
                foreach ($day['breaks'] as $break) {
                    if (empty($break['start']) || empty($break['end'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Break start and end times are required'
                        ], 422);
                    }

                    if (strtotime($break['start']) >= strtotime($break['end'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Break start time must be before end time'
                        ], 422);
                    }

                    if (strtotime($break['start']) < strtotime($day['start_time']) ||
                        strtotime($break['end']) > strtotime($day['end_time'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Break times must be within the work hours'
                        ], 422);
                    }
                }
            }
        }

        try {
            $effectiveFrom = Carbon::parse($validatedData['effective_from'])->startOfDay();
            $effectiveTo = !empty($validatedData['effective_to'])
                ? Carbon::parse($validatedData['effective_to'])->startOfDay()
                : null;

            DB::transaction(function () use (
                $validatedData,
                $weeklySchedule,
                $effectiveFrom,
                $effectiveTo
            ) {
                $doctorId = $validatedData['doctor_id'];
                $clinicId = $validatedData['clinic_id'];

                $currentMaxVersion = DoctorClinicSchedule::where('doctor_id', $doctorId)
                    ->where('clinic_id', $clinicId)
                    ->max('version');

                $newVersion = ($currentMaxVersion ?? 0) + 1;

                // Close existing active schedules that overlap with the new effective period
                DoctorClinicSchedule::where('doctor_id', $doctorId)
                    ->where('clinic_id', $clinicId)
                    ->where('is_active', true)
                    ->where(function ($query) use ($effectiveFrom) {
                        $query->whereNull('effective_to')
                            ->orWhere('effective_to', '>=', $effectiveFrom->toDateString());
                    })
                    ->update([
                        'effective_to' => $effectiveFrom->copy()->subDay()->toDateString(),
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);

                // Create new schedule rows (active days only)
                foreach ($weeklySchedule as $day) {
                    DoctorClinicSchedule::create([
                        'doctor_id' => $doctorId,
                        'clinic_id' => $clinicId,
                        'day_of_week' => $day['day_of_week'],
                        'start_time' => $day['start_time'],
                        'end_time' => $day['end_time'],
                        'appointment_duration' => $validatedData['appointment_duration'],
                        'breaks' => $day['breaks'] ?? null,
                        'effective_from' => $effectiveFrom->toDateString(),
                        'effective_to' => $effectiveTo ? $effectiveTo->toDateString() : null,
                        'version' => $newVersion,
                        'is_active' => true,
                    ]);
                }
            });

        return response()->json([
                'success' => true,
                'message' => 'Weekly schedule created successfully',
                'data' => [
                    'clinic_id' => $validatedData['clinic_id'],
                    'doctor_id' => $validatedData['doctor_id'],
                    'appointment_duration' => $validatedData['appointment_duration'],
                    'effective_from' => $validatedData['effective_from'],
                    'effective_to' => $validatedData['effective_to'] ?? null,
                    'week_days' => count($weeklySchedule),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create weekly schedule',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while saving schedule'
            ], 500);
        }
    }

    /**
     * Generate appointment slots from schedule templates for a date range.
     */
    public function generateSlots(GenerateSlotsRequest $request)
    {
        $validatedData = $request->validated();

        // Find the clinic-doctor relationship
        $connection = ClinicDoctor::where('clinic_id', $validatedData['clinic_id'])
                                    ->where('doctor_id', $validatedData['doctor_id'])
                                    ->first();

        if (!$connection) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor is not associated with the specified clinic'
            ], 404);
        }

        // Check if method is 1 (Auto scheduling)
        if ((int) $connection->method_id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Slot generation is only allowed for method 1 (Auto scheduling)'
            ], 403);
        }

        try {
            $startDate = Carbon::parse($validatedData['start_date'])->startOfDay();
            $endDate = Carbon::parse($validatedData['end_date'])->startOfDay();
            $doctorId = $validatedData['doctor_id'];
            $clinicId = $validatedData['clinic_id'];

            // Get active schedule templates for this doctor/clinic
            $schedules = DoctorClinicSchedule::where('doctor_id', $doctorId)
                ->where('clinic_id', $clinicId)
                ->where('is_active', true)
                ->get()
                ->groupBy('day_of_week');

            if ($schedules->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active schedule templates found. Please create a weekly schedule first.'
                ], 404);
            }

            $slotsCreated = 0;
            $slotsSkipped = 0;
            $datesProcessed = [];
            $appointmentPeriod = $connection->appointment_period;

            DB::transaction(function () use (
                $startDate,
                $endDate,
                $doctorId,
                $clinicId,
                $schedules,
                $appointmentPeriod,
                &$slotsCreated,
                &$slotsSkipped,
                &$datesProcessed
            ) {
                $currentDate = $startDate->copy();

                while ($currentDate->lte($endDate)) {
                    $dateString = $currentDate->toDateString();
                    $dayOfWeek = $currentDate->dayOfWeekIso; // 1=Monday, 7=Sunday

                    // Check for override
                    $override = ScheduleOverride::where('doctor_id', $doctorId)
                        ->where('clinic_id', $clinicId)
                        ->where('date', $dateString)
                        ->first();

                    if ($override) {
                        if ($override->isClosed()) {
                            // Day is closed, skip it
                            $slotsSkipped++;
                            $currentDate->addDay();
                            continue;
                        } elseif ($override->hasCustomSlots()) {
                            // Generate slots from custom override
                            $this->generateSlotsFromCustomOverride(
                                $doctorId,
                                $clinicId,
                                $currentDate,
                                $override,
                                $appointmentPeriod,
                                $slotsCreated
                            );
                            $datesProcessed[] = $dateString;
                        }
                    } elseif (isset($schedules[$dayOfWeek])) {
                        // Get schedule template for this day
                        $schedule = $schedules[$dayOfWeek]->first();

                        // Check if schedule is effective for this date
                        $scheduleFrom = Carbon::parse($schedule->effective_from);
                        $scheduleTo = $schedule->effective_to 
                            ? Carbon::parse($schedule->effective_to) 
                            : null;

                        if ($currentDate->gte($scheduleFrom) && 
                            ($scheduleTo === null || $currentDate->lte($scheduleTo))) {
                            
                            // Generate slots from template
                            $this->generateSlotsFromTemplate(
                                $doctorId,
                                $clinicId,
                                $currentDate,
                                $schedule,
                                $appointmentPeriod,
                                $slotsCreated
                            );
                            $datesProcessed[] = $dateString;
                        } else {
                            $slotsSkipped++;
                        }
                    } else {
                        // No schedule for this day
                        $slotsSkipped++;
                    }

                    $currentDate->addDay();
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Slots generated successfully',
                'data' => [
                    'clinic_id' => $clinicId,
                    'doctor_id' => $doctorId,
                    'start_date' => $validatedData['start_date'],
                    'end_date' => $validatedData['end_date'],
                    'slots_created' => $slotsCreated,
                    'slots_skipped' => $slotsSkipped,
                    'dates_processed' => count($datesProcessed),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate slots',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while generating slots'
            ], 500);
        }
    }

    /**
     * Generate slots from a schedule template.
     */
    private function generateSlotsFromTemplate(
        $doctorId,
        $clinicId,
        Carbon $date,
        DoctorClinicSchedule $schedule,
        $appointmentPeriod,
        &$slotsCreated
    ) {
        $startTime = Carbon::parse($date->toDateString() . ' ' . $schedule->start_time);
        $endTime = Carbon::parse($date->toDateString() . ' ' . $schedule->end_time);
        $duration = $schedule->appointment_duration ?? $appointmentPeriod;
        $breaks = $schedule->breaks ?? [];

        // Get existing slots for this date to avoid duplicates
        $existingSlots = ScheduleSlot::where('doctor_id', $doctorId)
            ->where('clinic_id', $clinicId)
            ->where('date', $date->toDateString())
            ->pluck('start_time')
            ->map(function ($time) {
                return Carbon::parse($time)->format('H:i:s');
            })
            ->toArray();

        $currentSlotStart = $startTime->copy();
        $slots = [];

        while ($currentSlotStart->copy()->addMinutes($duration)->lte($endTime)) {
            $slotEnd = $currentSlotStart->copy()->addMinutes($duration);

            // Check if this slot overlaps with any break
            $isInBreak = false;
            foreach ($breaks as $break) {
                $breakStart = Carbon::parse($date->toDateString() . ' ' . $break['start']);
                $breakEnd = Carbon::parse($date->toDateString() . ' ' . $break['end']);

                if ($currentSlotStart->lt($breakEnd) && $slotEnd->gt($breakStart)) {
                    $isInBreak = true;
                    // Move to after the break
                    $currentSlotStart = $breakEnd->copy();
                    break;
                }
            }

            if (!$isInBreak) {
                $slotTime = $currentSlotStart->format('H:i:s');
                
                // Check if slot already exists
                if (!in_array($slotTime, $existingSlots)) {
                    $slots[] = [
                        'doctor_id' => $doctorId,
                        'clinic_id' => $clinicId,
                        'day_of_week' => $date->dayOfWeekIso,
                        'date' => $date->toDateString(),
                        'start_time' => $currentSlotStart->format('H:i:s'),
                        'end_time' => $slotEnd->format('H:i:s'),
                        'is_available' => true,
                        'status' => 'available',
                        'slot_type' => 'open',
                        'creation_method' => 'auto',
                        'schedule_id' => $schedule->id,
                        'override_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $currentSlotStart->addMinutes($duration);
            }
        }

        if (!empty($slots)) {
            ScheduleSlot::insert($slots);
            $slotsCreated += count($slots);
        }
    }

    /**
     * Generate slots from a custom override.
     */
    private function generateSlotsFromCustomOverride(
        $doctorId,
        $clinicId,
        Carbon $date,
        ScheduleOverride $override,
        $appointmentPeriod,
        &$slotsCreated
    ) {
        $customSlots = $override->custom_slots ?? [];

        // Get existing slots for this date to avoid duplicates
        $existingSlots = ScheduleSlot::where('doctor_id', $doctorId)
            ->where('clinic_id', $clinicId)
            ->where('date', $date->toDateString())
            ->pluck('start_time')
            ->map(function ($time) {
                return Carbon::parse($time)->format('H:i:s');
            })
            ->toArray();

        $slots = [];

        foreach ($customSlots as $customSlot) {
            $slotStart = Carbon::parse($date->toDateString() . ' ' . $customSlot['start']);
            $slotEnd = Carbon::parse($date->toDateString() . ' ' . $customSlot['end']);
            $slotTime = $slotStart->format('H:i:s');

            // Check if slot already exists
            if (!in_array($slotTime, $existingSlots)) {
                $slots[] = [
                    'doctor_id' => $doctorId,
                    'clinic_id' => $clinicId,
                    'day_of_week' => $date->dayOfWeekIso,
                    'date' => $date->toDateString(),
                    'start_time' => $slotStart->format('H:i:s'),
                    'end_time' => $slotEnd->format('H:i:s'),
                    'is_available' => true,
                    'status' => 'available',
                    'slot_type' => 'open',
                    'creation_method' => 'auto',
                    'schedule_id' => null,
                    'override_id' => $override->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($slots)) {
            ScheduleSlot::insert($slots);
            $slotsCreated += count($slots);
        }
    }

    /**
     * Create manual appointment slots for a specific date (method 2 - Manual scheduling).
     */
    public function createManualSlots(CreateManualSlotsRequest $request)
    {
        $validatedData = $request->validated();

        $connection = ClinicDoctor::where('clinic_id', $validatedData['clinic_id'])
            ->where('doctor_id', $validatedData['doctor_id'])
            ->first();

        if (!$connection) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor is not associated with the specified clinic',
            ], 404);
        }

        if ((int) $connection->method_id !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Manual slot creation is only allowed for method 2 (Manual scheduling)',
            ], 403);
        }

        try {
            $doctorId = $validatedData['doctor_id'];
            $clinicId = $validatedData['clinic_id'];
            $dateString = Carbon::parse($validatedData['date'])->toDateString();
            $dayOfWeek = Carbon::parse($validatedData['date'])->dayOfWeekIso;
            $replaceExisting = $validatedData['replace_existing'] ?? false;
            $slotsCreated = 0;

            DB::transaction(function () use (
                $doctorId,
                $clinicId,
                $dateString,
                $dayOfWeek,
                $validatedData,
                $replaceExisting,
                &$slotsCreated
            ) {
                if ($replaceExisting) {
                    ScheduleSlot::where('doctor_id', $doctorId)
                        ->where('clinic_id', $clinicId)
                        ->where('date', $dateString)
                        ->where('creation_method', 'manual')
                        ->delete();
                }

                $existingStarts = ScheduleSlot::where('doctor_id', $doctorId)
                    ->where('clinic_id', $clinicId)
                    ->where('date', $dateString)
                    ->pluck('start_time')
                    ->map(fn ($t) => Carbon::parse($t)->format('H:i:s'))
                    ->toArray();

                $toInsert = [];
                foreach ($validatedData['slots'] as $slot) {
                    $start = Carbon::parse($dateString . ' ' . $slot['start']);
                    $end = Carbon::parse($dateString . ' ' . $slot['end']);
                    $startTime = $start->format('H:i:s');
                    $endTime = $end->format('H:i:s');

                    if (in_array($startTime, $existingStarts)) {
                        continue;
                    }

                    $toInsert[] = [
                        'doctor_id' => $doctorId,
                        'clinic_id' => $clinicId,
                        'day_of_week' => $dayOfWeek,
                        'date' => $dateString,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'is_available' => true,
                        'status' => 'available',
                        'slot_type' => 'open',
                        'creation_method' => 'manual',
                        'schedule_id' => null,
                        'override_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $existingStarts[] = $startTime;
                }

                if (!empty($toInsert)) {
                    ScheduleSlot::insert($toInsert);
                    $slotsCreated = count($toInsert);
                }
            });

            $slots = ScheduleSlot::where('doctor_id', $doctorId)
                ->where('clinic_id', $clinicId)
                ->where('date', $dateString)
                ->where('creation_method', 'manual')
                ->orderBy('start_time')
                ->get(['id', 'date', 'start_time', 'end_time', 'status', 'creation_method']);

            return response()->json([
                'success' => true,
                'message' => 'Manual slots created successfully',
                'data' => [
                    'clinic_id' => $clinicId,
                    'doctor_id' => $doctorId,
                    'date' => $dateString,
                    'slots_created' => $slotsCreated,
                    'slots' => $slots,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create manual slots',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while creating slots',
            ], 500);
        }
    }

    /**
     * Add overrides [closed / custom]
     */
    public function addOverride(OverrideRequest $request)
    {
        $validatedData = $request->validated();

        // Check if doctor is associated with the clinic
        $connection = ClinicDoctor::where('clinic_id', $validatedData['clinic_id'])
                                    ->where('doctor_id', $validatedData['doctor_id'])
                                    ->first();

        if (!$connection) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor is not associated with the specified clinic'
            ], 404);
        }

        try {
            // Check if an override already exists for this date
            $existingOverride = ScheduleOverride::where('doctor_id', $validatedData['doctor_id'])
                ->where('clinic_id', $validatedData['clinic_id'])
                ->where('date', $validatedData['date'])
                ->first();

            if ($existingOverride) {
                // Update existing override
                $existingOverride->update([
                    'type' => $validatedData['type'],
                    'custom_slots' => $validatedData['type'] === 'custom' ? $validatedData['custom_slots'] : null,
                    'reason' => $validatedData['reason'] ?? null,
                    'updated_at' => now(),
                ]);

                $override = $existingOverride;
            } else {
                // Create new override
                $override = ScheduleOverride::create([
                    'doctor_id' => $validatedData['doctor_id'],
                    'clinic_id' => $validatedData['clinic_id'],
                    'date' => $validatedData['date'],
                    'type' => $validatedData['type'],
                    'custom_slots' => $validatedData['type'] === 'custom' ? $validatedData['custom_slots'] : null,
                    'reason' => $validatedData['reason'] ?? null,
                ]);
            }

            // Clear any existing slots for this date to regenerate them with the override
            ScheduleSlot::where('doctor_id', $validatedData['doctor_id'])
                ->where('clinic_id', $validatedData['clinic_id'])
                ->where('date', $validatedData['date'])
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Override ' . ($existingOverride ? 'updated' : 'added') . ' successfully',
                'data' => [
                    'id' => $override->id,
                    'clinic_id' => $override->clinic_id,
                    'doctor_id' => $override->doctor_id,
                    'date' => $override->date,
                    'type' => $override->type,
                    'custom_slots' => $override->custom_slots,
                    'reason' => $override->reason,
                    'created_at' => $override->created_at,
                    'updated_at' => $override->updated_at,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add/update override',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while saving the override'
            ], 500);
        }
    }

    /**
     * Get booking information for a specific doctor in a clinic
     * 
     * @param int $clinic_id
     * @param int $doctor_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBookingInfo($clinic_id, $doctor_id)
    {
        try {
            // Find the clinic-doctor relationship
            $clinicDoctor = ClinicDoctor::where('clinic_id', $clinic_id)
                                        ->where('doctor_id', $doctor_id)
                                        ->with(['clinic', 'doctor', 'method'])
                                        ->first();

            if (!$clinicDoctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor is not associated with the specified clinic'
                ], 404);
            }

            // Get the schedule to determine appointment duration
            $schedule = DoctorClinicSchedule::where('doctor_id', $doctor_id)
                                           ->where('clinic_id', $clinic_id)
                                           ->first();

            $appointmentDuration = $schedule ? $schedule->appointment_duration : $clinicDoctor->appointment_period;

            // Check if the doctor supports waiting list (based on queue settings)
            $supportsWaitingList = $clinicDoctor->queue ?? false;

            // Get waiting list settings if applicable
            $waitingListSettings = null;
            if ($supportsWaitingList) {
                // You could add logic here to get max capacity and current count
                $waitingListSettings = [
                    'max_capacity' => $clinicDoctor->queue_number ?? null, // Max capacity from clinic doctor settings
                    'current_count' => WaitingList::where('doctor_id', $doctor_id)
                                                 ->where('clinic_id', $clinic_id)
                                                 ->where('target_date', Carbon::today()->toDateString())
                                                 ->where('status', 'active')
                                                 ->count() // Current count of people in waiting list for today
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'doctor_id' => $clinicDoctor->doctor_id,
                    'clinic_id' => $clinicDoctor->clinic_id,
                    'appointment_method_id' => $clinicDoctor->method_id,
                    'appointment_duration' => $appointmentDuration,
                    'supports_waiting_list' => $supportsWaitingList,
                    'waiting_list_settings' => $waitingListSettings
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve booking information',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving booking information'
            ], 500);
        }
    }

    /**
     * Get available appointments for a specific doctor in a clinic
     * 
     * @param int $clinic_id
     * @param int $doctor_id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableAppointments($clinic_id, $doctor_id, Request $request)
    {
        try {
            // Validate inputs
            $clinicDoctor = ClinicDoctor::where('clinic_id', $clinic_id)
                                        ->where('doctor_id', $doctor_id)
                                        ->first();

            if (!$clinicDoctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor is not associated with the specified clinic'
                ], 404);
            }

            // Get the date from request or use today
            $requestedDate = $request->query('date');
            $selectedDate = null;
            if (!$requestedDate) {
                $selectedDate = Carbon::today()->toDateString();
            } else {
                // Validate the date format
                try {
                    $selectedDate = Carbon::parse($requestedDate)->toDateString();
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid date format. Please use YYYY-MM-DD format.'
                    ], 400);
                }
            }

            // Get available days for the month of the selected date (or current month)
            $availableDays = collect();
            $baseDate = Carbon::parse($selectedDate);
            $currentDate = $baseDate->copy()->startOfMonth();
            $endDate = $baseDate->copy()->endOfMonth();

            $today = Carbon::today();
            while ($currentDate->lte($endDate)) {
                if ($currentDate->lt($today)) {
                    $currentDate->addDay();
                    continue;
                }
                $dateStr = $currentDate->toDateString();
                
                // Check if there are available slots for this date
                $hasAvailableSlots = ScheduleSlot::where('doctor_id', $doctor_id)
                                                ->where('clinic_id', $clinic_id)
                                                ->where('date', $dateStr)
                                                ->available()
                                                ->exists();
                
                if ($hasAvailableSlots) {
                    $availableDays->push($dateStr);
                }
                
                $currentDate->addDay();
            }

            // If no date was requested and selected date has no slots, use first available date
            if (!$requestedDate && !$availableDays->contains($selectedDate)) {
                $selectedDate = $availableDays->first();
            }

            // Get appointments for the selected date
            $appointments = [];
            if ($selectedDate) {
                $slots = ScheduleSlot::where('doctor_id', $doctor_id)
                                   ->where('clinic_id', $clinic_id)
                                   ->where('date', $selectedDate)
                                   ->available()
                                   ->orderBy('start_time')
                                   ->get();

                $appointments = $slots->map(function ($slot) {
                    return [
                        'appointment_id' => $slot->id,
                        'start_time' => $slot->start_time,
                        'end_time' => $slot->end_time
                    ];
                })->toArray();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'available_days' => $availableDays->values()->toArray(),
                    'selected_day_data' => [
                        'date' => $selectedDate,
                        'appointments' => $appointments
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve available appointments',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving appointments'
            ], 500);
        }
    }

    /**
     * Submit an appointment request (either direct booking or request)
     * 
     * @param int $clinic_id
     * @param int $doctor_id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitAppointment($clinic_id, $doctor_id, Request $request)
    {
        $validated = $request->validate([
            'appointment_method_id' => 'required|integer',
            'patient_note' => 'nullable|string|max:500',
            // For direct booking (method 1)
            'selected_date' => 'required_if:appointment_method_id,1|date_format:Y-m-d|nullable',
            'selected_appointment_id' => 'required_if:appointment_method_id,1|integer|nullable',
            // For request booking (method 2)
            'preferred_date' => 'required_if:appointment_method_id,2|date_format:Y-m-d|nullable',
            'preferred_time' => 'required_if:appointment_method_id,2|date_format:H:i|nullable',
        ]);

        try {
            // Check if user is authenticated and is a patient
            $user = Auth::user();
            if (!$user || !$user->patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only patients can book appointments'
                ], 403);
            }

            $patientId = $user->patient->id;

            // Validate clinic-doctor relationship
            $clinicDoctor = ClinicDoctor::where('clinic_id', $clinic_id)
                                        ->where('doctor_id', $doctor_id)
                                        ->first();

            if (!$clinicDoctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor is not associated with the specified clinic'
                ], 404);
            }

            // Ensure requested method matches clinic settings
            if ((int) $clinicDoctor->method_id !== (int) $validated['appointment_method_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Requested appointment method is not supported by this doctor in this clinic'
                ], 400);
            }

            if ($validated['appointment_method_id'] == 1) {
                // Direct booking - book slot atomically to avoid double booking
                $appointment = DB::transaction(function () use ($validated, $doctor_id, $clinic_id, $patientId) {
                    $slot = ScheduleSlot::where('id', $validated['selected_appointment_id'])
                                      ->where('doctor_id', $doctor_id)
                                      ->where('clinic_id', $clinic_id)
                                      ->where('date', $validated['selected_date'])
                                      ->lockForUpdate()
                                      ->first();

                    if (!$slot || !$slot->isAvailableForBooking()) {
                        return null;
                    }

                    $appointment = Appointment::create([
                        'patient_id' => $patientId,
                        'doctor_id' => $doctor_id,
                        'clinic_id' => $clinic_id,
                        'schedule_slot_id' => $slot->id,
                        'date' => $validated['selected_date'],
                        'start_time' => $slot->start_time,
                        'end_time' => $slot->end_time,
                        'status' => Appointment::STATUS_BOOKED, // Initially booked
                        'type' => 'consultation',
                        'source' => 'patient_app',
                        'payment_status' => 'unpaid',
                        'patient_note' => $validated['patient_note'] ?? null
                    ]);

                    $slot->update([
                        'status' => 'booked',
                        'is_available' => false
                    ]);

                    return $appointment;
                });

                if (!$appointment) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected appointment slot is not available'
                    ], 409);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Appointment booked successfully',
                    'data' => [
                        'appointment_id' => $appointment->id,
                        'status' => $appointment->status,
                        'date' => $appointment->date,
                        'start_time' => $appointment->start_time,
                        'end_time' => $appointment->end_time
                    ]
                ], 201);

            } elseif ($validated['appointment_method_id'] == 2) {
                $schedule = DoctorClinicSchedule::where('doctor_id', $doctor_id)
                                               ->where('clinic_id', $clinic_id)
                                               ->first();
                $appointmentDuration = $schedule ? $schedule->appointment_duration : $clinicDoctor->appointment_period;
                $appointmentDuration = $appointmentDuration ?: 30;

                // Request booking - create appointment with pending status
                $appointment = Appointment::create([
                    'patient_id' => $patientId,
                    'doctor_id' => $doctor_id,
                    'clinic_id' => $clinic_id,
                    'date' => $validated['preferred_date'],
                    'start_time' => $validated['preferred_time'],
                    'end_time' => Carbon::parse($validated['preferred_time'])->addMinutes($appointmentDuration)->format('H:i:s'),
                    'status' => Appointment::STATUS_PENDING_APPROVAL, // Pending approval
                    'type' => 'consultation',
                    'source' => 'patient_app',
                    'payment_status' => 'unpaid',
                    'patient_note' => $validated['patient_note'] ?? null
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Appointment request submitted successfully',
                    'data' => [
                        'appointment_id' => $appointment->id,
                        'status' => $appointment->status,
                        'date' => $appointment->date,
                        'start_time' => $appointment->start_time,
                        'end_time' => $appointment->end_time
                    ]
                ], 201);

            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid appointment method ID'
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit appointment',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while submitting appointment'
            ], 500);
        }
    }

    /**
     * Cancel an appointment
     * 
     * @param int $appointment_id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelAppointment($appointment_id, Request $request)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
            'cancelled_by_comment' => 'nullable|string|max:500'
        ]);

        try {
            $appointment = Appointment::findOrFail($appointment_id);

            // Check authorization
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            // Determine if user is authorized to cancel
            $isPatient = $user->patient && $user->patient->id == $appointment->patient_id;
            $isDoctor = $user->doctor && $user->doctor->id == $appointment->doctor_id;
            $isAdminOrClinicManager = $user->hasRole(['admin', 'clinic_manager']) || 
                                     ($user->secretary && $user->secretary->clinic_id == $appointment->clinic_id);

            if (!$isPatient && !$isDoctor && !$isAdminOrClinicManager) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to cancel this appointment'
                ], 403);
            }

            // Check if appointment can be cancelled
            if (!$appointment->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This appointment cannot be cancelled'
                ], 400);
            }

            // Update appointment status
            $appointment->update([
                'status' => Appointment::STATUS_CANCELLED,
                'cancellation_reason' => $validated['reason'] ?? null,
                'cancelled_by' => $user->id,
                'cancelled_by_comment' => $validated['cancelled_by_comment'] ?? null
            ]);

            // If the appointment had a schedule slot, make it available again
            if ($appointment->scheduleSlot) {
                $appointment->scheduleSlot->update([
                    'status' => 'available',
                    'is_available' => true
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Appointment cancelled successfully',
                'data' => [
                    'appointment_id' => $appointment->id,
                    'status' => $appointment->status
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel appointment',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while cancelling appointment'
            ], 500);
        }
    }

    /**
     * Return aggregated consultation details for an appointment.
     *
     * @param int $appointment_id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function getConsultationDetails($appointment_id)
    {
        try {
            $appointment = Appointment::with([
                'doctor.user',
                'patient.user',
                'clinic',
            ])->find($appointment_id);

            if (!$appointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment not found',
                ], 404);
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            $isDoctor = $user->doctor && (int) $user->doctor->id === (int) $appointment->doctor_id;
            $isPatient = $user->patient && (int) $user->patient->id === (int) $appointment->patient_id;

            if (!$isDoctor && !$isPatient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to access this consultation',
                ], 403);
            }

            $visitRecord = VisitRecord::with([
                'diagnoses',
                'labRequests',
                'labResults',
                'prescriptions',
            ])->where('appointment_id', $appointment->id)->first();

            if (!$visitRecord) {
                return response()->noContent();
            }

            $payload = [
                'appointment_info' => [
                    'id' => $appointment->id,
                    'date' => $appointment->date ? Carbon::parse($appointment->date)->toDateString() : null,
                    'doctor_name' => $appointment->doctor?->full_name,
                    'patient_name' => $appointment->patient?->user?->full_name,
                    'clinic_name' => $appointment->clinic?->clinic_name,
                ],
                'general_notes' => $visitRecord->notes,
                'diagnoses' => $visitRecord->diagnoses->map(function ($diagnosis) {
                    return [
                        'condition_id' => $diagnosis->condition_id,
                        'condition_name' => $diagnosis->condition_name,
                        'classification' => $diagnosis->classification,
                        'notes' => $diagnosis->notes,
                    ];
                })->values(),
                'investigations' => [
                    'requests' => $visitRecord->labRequests->map(function ($request) {
                        return [
                            'test_id' => $request->test_id,
                            'test_name' => $request->test_name,
                            'status' => $request->status ?? 'pending',
                        ];
                    })->values(),
                    'uploads' => $visitRecord->labResults->map(function ($upload) {
                        return [
                            'file_url' => $upload->file_url ?: $upload->attachment_url,
                            'doctor_comment' => $upload->doctor_comment,
                        ];
                    })->values(),
                ],
                'prescriptions' => $visitRecord->prescriptions->map(function ($prescription) {
                    return [
                        'medicine_name' => $prescription->medication_name,
                        'dose' => $prescription->dose_description ?: $prescription->dosage,
                        'duration' => $prescription->duration,
                        'instructions' => $prescription->food_relation
                            ?: $prescription->special_instructions
                            ?: $prescription->instructions,
                    ];
                })->values(),
            ];

            return response()->json([
                'success' => true,
                'data' => $payload,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch consultation details',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while fetching consultation details',
            ], 500);
        }
    }

    /**
     * Mark appointment as attended/completed
     * 
     * @param int $clinic_id
     * @param int $appointment_id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAppointmentAsAttended($clinic_id, $appointment_id, Request $request)
    {
        try {
            $appointment = Appointment::where('id', $appointment_id)
                                    ->where('clinic_id', $clinic_id)
                                    ->first();

            if (!$appointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment not found'
                ], 404);
            }

            // Check authorization
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            // Determine if user is authorized to mark as attended
            $isDoctor = $user->doctor && $user->doctor->id == $appointment->doctor_id;
            $isPatient = $user->patient && $user->patient->id == $appointment->patient_id;
            $isAdminOrClinicManager = $user->hasRole(['admin', 'clinic_manager']) || 
                                     ($user->secretary && $user->secretary->clinic_id == $appointment->clinic_id);

            if (!$isDoctor && !$isPatient && !$isAdminOrClinicManager) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to mark this appointment as attended'
                ], 403);
            }

            if ($appointment->isCancelled() || $appointment->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This appointment cannot be marked as attended'
                ], 400);
            }

            // Update appointment status to completed
            $appointment->update([
                'status' => Appointment::STATUS_COMPLETED
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Appointment marked as attended successfully',
                'data' => [
                    'appointment_id' => $appointment->id,
                    'status' => $appointment->status
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark appointment as attended',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while marking appointment as attended'
            ], 500);
        }
    }

    /**
     * Confirm appointment initially (24 hours before)
     * 
     * @param int $clinic_id
     * @param int $appointment_id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmAppointmentInitial($clinic_id, $appointment_id, Request $request)
    {
        try {
            $appointment = Appointment::where('id', $appointment_id)
                                    ->where('clinic_id', $clinic_id)
                                    ->first();

            if (!$appointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment not found'
                ], 404);
            }

            $user = Auth::user();
            if (!$user || !$user->patient || $user->patient->id != $appointment->patient_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to confirm this appointment'
                ], 403);
            }

            // Check if appointment is in a state that can be confirmed
            if (!$appointment->isBooked()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment cannot be confirmed at this stage'
                ], 400);
            }

            // Update appointment status to confirmed
            $appointment->update([
                'status' => Appointment::STATUS_CONFIRMED
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Appointment confirmed successfully, we will remind you 2 hours before the appointment.',
                'data' => [
                    'appointment_id' => $appointment->id,
                    'status' => $appointment->status
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm appointment',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while confirming appointment'
            ], 500);
        }
    }

    /**
     * Confirm appointment finally (2 hours before)
     * 
     * @param int $clinic_id
     * @param int $appointment_id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmAppointmentFinal($clinic_id, $appointment_id, Request $request)
    {
        try {
            $appointment = Appointment::where('id', $appointment_id)
                                    ->where('clinic_id', $clinic_id)
                                    ->first();

            if (!$appointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment not found'
                ], 404);
            }

            $user = Auth::user();
            if (!$user || !$user->patient || $user->patient->id != $appointment->patient_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to confirm this appointment'
                ], 403);
            }

            // Check if appointment is in a state that can be confirmed
            if (!$appointment->isConfirmed() && !$appointment->isBooked()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment cannot be confirmed at this stage'
                ], 400);
            }

            // Update appointment status to final confirmation
            $appointment->update([
                'status' => Appointment::STATUS_FINAL_CONFIRMATION
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Final confirmation received. Thank you for confirming your attendance.',
                'data' => [
                    'appointment_id' => $appointment->id,
                    'status' => $appointment->status
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm appointment finally',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while confirming appointment finally'
            ], 500);
        }
    }

    /**
     * Finalize a doctor consultation and mark appointment as completed.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function finalizeConsultation(Request $request)
    {
        $validated = $request->validate([
            'metadata' => 'required|array',
            'metadata.appointment_id' => 'required|integer|exists:appointments,id',
            'metadata.patient_id' => 'required|integer|exists:patients,id',
            'metadata.doctor_id' => 'required|integer|exists:doctors,id',
            'metadata.clinic_id' => 'required|integer|exists:clinics,id',
            'metadata.session_start_time' => 'nullable|date',

            'general_notes' => 'nullable|string',

            'diagnoses' => 'nullable|array',
            'diagnoses.*.condition_id' => 'nullable|string|max:100',
            'diagnoses.*.condition_name' => 'required_with:diagnoses|string|max:255',
            'diagnoses.*.classification' => 'nullable|in:acute,chronic,suspected',
            'diagnoses.*.notes' => 'nullable|string',

            'investigations' => 'nullable|array',
            'investigations.requests' => 'nullable|array',
            'investigations.requests.*.test_id' => 'nullable|string|max:100',
            'investigations.requests.*.test_name' => 'required_with:investigations.requests|string|max:255',
            'investigations.requests.*.priority' => 'nullable|in:normal,urgent,stat',
            'investigations.requests.*.instructions' => 'nullable|string',

            'investigations.uploads' => 'nullable|array',
            'investigations.uploads.*.file_name' => 'nullable|string|max:255',
            'investigations.uploads.*.file_url' => 'nullable|url|max:2048',
            'investigations.uploads.*.file_type' => 'nullable|in:pdf,image',
            'investigations.uploads.*.file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'investigations.uploads.*.doctor_comment' => 'nullable|string',

            'prescriptions' => 'nullable|array',
            'prescriptions.*.medicine_name' => 'required_with:prescriptions|string|max:255',
            'prescriptions.*.total_quantity' => 'nullable|integer|min:1',
            'prescriptions.*.dose_description' => 'nullable|string|max:255',
            'prescriptions.*.daily_frequency' => 'nullable|integer|min:1',
            'prescriptions.*.hourly_interval' => 'nullable|integer|min:1',
            'prescriptions.*.food_relation' => 'nullable|string|max:100',
            'prescriptions.*.duration' => 'nullable|string|max:255',
            'prescriptions.*.special_instructions' => 'nullable|string',
        ]);

        try {
            $user = Auth::user();
            if (!$user || !$user->doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only doctors can finalize consultations',
                ], 403);
            }

            $metadata = $validated['metadata'];
            $authenticatedDoctorId = (int) $user->doctor->id;

            if ($authenticatedDoctorId !== (int) $metadata['doctor_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to finalize consultation for this doctor ID',
                ], 403);
            }

            $appointment = Appointment::with(['patient.user'])
                ->find($metadata['appointment_id']);

            if (!$appointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment not found',
                ], 404);
            }

            if ($appointment->isCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancelled appointments cannot be finalized',
                ], 400);
            }

            $metadataMatchesAppointment =
                (int) $appointment->doctor_id === (int) $metadata['doctor_id']
                && (int) $appointment->patient_id === (int) $metadata['patient_id']
                && (int) $appointment->clinic_id === (int) $metadata['clinic_id'];

            if (!$metadataMatchesAppointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Metadata does not match appointment ownership',
                ], 403);
            }

            DB::transaction(function () use ($validated, $metadata, $appointment, $request) {
                $lockedAppointment = Appointment::where('id', $appointment->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedAppointment || $lockedAppointment->isCancelled()) {
                    throw new \RuntimeException('Appointment cannot be finalized');
                }

                $medicalFile = MedicalFile::firstOrCreate([
                    'patient_id' => $metadata['patient_id'],
                ]);

                $visitRecord = VisitRecord::where('appointment_id', $lockedAppointment->id)->first();

                if (!$visitRecord) {
                    $visitRecord = new VisitRecord();
                    $visitRecord->appointment_id = $lockedAppointment->id;
                }

                $legacyDiagnosis = collect($validated['diagnoses'] ?? [])
                    ->pluck('condition_name')
                    ->filter()
                    ->implode(', ');

                $visitRecord->fill([
                    'medical_file_id' => $medicalFile->id,
                    'patient_id' => $metadata['patient_id'],
                    'doctor_id' => $metadata['doctor_id'],
                    'clinic_id' => $metadata['clinic_id'],
                    'visit_date' => $lockedAppointment->date ?? now()->toDateString(),
                    'session_start_time' => $metadata['session_start_time'] ?? null,
                    'diagnosis' => $legacyDiagnosis !== '' ? $legacyDiagnosis : null,
                    'notes' => $validated['general_notes'] ?? null,
                ]);

                $visitRecord->save();

                // Replace children for deterministic resubmission behavior.
                $visitRecord->diagnoses()->delete();
                $visitRecord->labRequests()->delete();
                $visitRecord->labResults()->delete();
                $visitRecord->prescriptions()->delete();

                foreach (($validated['diagnoses'] ?? []) as $diagnosis) {
                    VisitDiagnosis::create([
                        'visit_record_id' => $visitRecord->id,
                        'condition_id' => $diagnosis['condition_id'] ?? null,
                        'condition_name' => $diagnosis['condition_name'],
                        'classification' => $diagnosis['classification'] ?? null,
                        'notes' => $diagnosis['notes'] ?? null,
                    ]);
                }

                foreach (($validated['investigations']['requests'] ?? []) as $labRequest) {
                    LabRequest::create([
                        'visit_record_id' => $visitRecord->id,
                        'test_id' => $labRequest['test_id'] ?? null,
                        'test_name' => $labRequest['test_name'],
                        'priority' => $labRequest['priority'] ?? null,
                        'instructions' => $labRequest['instructions'] ?? null,
                        'status' => 'pending',
                    ]);
                }

                $uploadFiles = $request->file('investigations.uploads', []);
                foreach (($validated['investigations']['uploads'] ?? []) as $index => $upload) {
                    $file = null;
                    if (isset($uploadFiles[$index]['file'])) {
                        $file = $uploadFiles[$index]['file'];
                    } elseif (isset($uploadFiles[$index]) && $uploadFiles[$index] instanceof \Illuminate\Http\UploadedFile) {
                        $file = $uploadFiles[$index];
                    }

                    $fileUrl = $upload['file_url'] ?? null;
                    $fileType = $upload['file_type'] ?? null;
                    $fileName = $upload['file_name'] ?? null;

                    if ($file) {
                        $uploadResult = $this->imageKitService->upload(
                            $file,
                            "patients/{$metadata['patient_id']}/lab-results"
                        );
                        $fileUrl = $uploadResult['url'];
                        $fileType = $this->resolveFileType($file->getClientOriginalExtension());
                        $fileName = $fileName ?: $file->getClientOriginalName();
                    }

                    if (!$fileUrl) {
                        throw new \RuntimeException('Either file or file_url is required for investigation upload.');
                    }

                    if (!$fileType) {
                        $fileType = $this->resolveFileTypeFromUrl($fileUrl);
                    }

                    if (!$fileType) {
                        throw new \RuntimeException('file_type is required for investigation upload.');
                    }

                    LabResult::create([
                        'visit_record_id' => $visitRecord->id,
                        'test_type' => $fileType,
                        'result_data' => null,
                        'attachment_url' => $fileUrl,
                        'file_name' => $fileName,
                        'file_url' => $fileUrl,
                        'file_type' => $fileType,
                        'doctor_comment' => $upload['doctor_comment'] ?? null,
                    ]);
                }

                foreach (($validated['prescriptions'] ?? []) as $prescription) {
                    $newPrescription = Prescription::create([
                        'visit_record_id' => $visitRecord->id,
                        'medication_name' => $prescription['medicine_name'],
                        'dosage' => $prescription['dose_description'] ?? null,
                        'instructions' => $prescription['special_instructions'] ?? null,
                        'issued_at' => now(),
                        'total_quantity' => $prescription['total_quantity'] ?? null,
                        'dose_description' => $prescription['dose_description'] ?? null,
                        'daily_frequency' => $prescription['daily_frequency'] ?? null,
                        'hourly_interval' => $prescription['hourly_interval'] ?? null,
                        'food_relation' => $prescription['food_relation'] ?? null,
                        'duration' => $prescription['duration'] ?? null,
                        'special_instructions' => $prescription['special_instructions'] ?? null,
                    ]);

                    MedicationTracker::updateOrCreate(
                        ['prescription_id' => $newPrescription->id],
                        [
                            'patient_id' => $visitRecord->patient_id,
                            'doctor_id' => $visitRecord->doctor_id,
                            'status' => 'waiting_purchase',
                            'total_doses' => 0,
                            'taken_doses' => 0,
                            'start_at' => null,
                            'next_dose_at' => null,
                            'consecutive_missed_doses' => 0,
                            'non_compliant_at' => null,
                        ]
                    );
                }

                $lockedAppointment->update([
                    'status' => Appointment::STATUS_COMPLETED,
                ]);
            });

            $patientName = $appointment->patient?->user?->full_name;
            $message = $patientName
                ? "تم اصدار المعاينة للمريض {$patientName} بنجاح"
                : 'تم اصدار المعاينة بنجاح';

            return response()->json([
                'success' => true,
                'message' => $message,
            ], 200);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Appointment cannot be finalized',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to finalize consultation',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while finalizing consultation',
            ], 500);
        }
    }

    /**
     * Join waiting list for a specific doctor and date
     * 
     * @param int $clinic_id
     * @param int $doctor_id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function joinWaitingList($clinic_id, $doctor_id, Request $request)
    {
        $validated = $request->validate([
            'target_date' => 'required|date|after_or_equal:today',
            'patient_note' => 'nullable|string|max:500'
        ]);

        try {
            // Check if user is authenticated and is a patient
            $user = Auth::user();
            if (!$user || !$user->patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only patients can join waiting lists'
                ], 403);
            }

            $patientId = $user->patient->id;

            // Validate clinic-doctor relationship
            $clinicDoctor = ClinicDoctor::where('clinic_id', $clinic_id)
                                        ->where('doctor_id', $doctor_id)
                                        ->first();

            if (!$clinicDoctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor is not associated with the specified clinic'
                ], 404);
            }

            // Check if the doctor supports waiting lists
            if (!$clinicDoctor->queue) {
                return response()->json([
                    'success' => false,
                    'message' => 'This doctor does not support waiting lists'
                ], 400);
            }

            if (!is_null($clinicDoctor->queue_number)) {
                $currentCount = WaitingList::where('doctor_id', $doctor_id)
                                         ->where('clinic_id', $clinic_id)
                                         ->where('target_date', $validated['target_date'])
                                         ->whereIn('status', ['active', 'notified'])
                                         ->count();

                if ($currentCount >= (int) $clinicDoctor->queue_number) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Waiting list is full for the selected date'
                    ], 400);
                }
            }

            // Check if patient is already in the waiting list for this date
            $existingEntry = WaitingList::where('patient_id', $patientId)
                                      ->where('doctor_id', $doctor_id)
                                      ->where('clinic_id', $clinic_id)
                                      ->where('target_date', $validated['target_date'])
                                      ->whereIn('status', ['active', 'notified'])
                                      ->first();

            if ($existingEntry) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already in the waiting list for this date'
                ], 400);
            }

            // Calculate position in queue (based on current active entries for this date)
            $positionInQueue = WaitingList::where('doctor_id', $doctor_id)
                                        ->where('clinic_id', $clinic_id)
                                        ->where('target_date', $validated['target_date'])
                                        ->where('status', 'active')
                                        ->count() + 1;

            // Create waiting list entry
            $waitingListEntry = WaitingList::create([
                'patient_id' => $patientId,
                'doctor_id' => $doctor_id,
                'clinic_id' => $clinic_id,
                'target_date' => $validated['target_date'],
                'patient_note' => $validated['patient_note'] ?? null,
                'position_in_queue' => $positionInQueue,
                'status' => 'active'
            ]);

            return response()->json([
                'success' => true,
                'message' => "You have been registered in the waiting list for {$validated['target_date']} successfully. We will notify you if a spot becomes available.",
                'data' => [
                    'waiting_list_id' => $waitingListEntry->id,
                    'doctor_name' => $clinicDoctor->doctor->name ?? 'Unknown Doctor',
                    'clinic_name' => $clinicDoctor->clinic->name ?? 'Unknown Clinic',
                    'position_in_queue' => $positionInQueue
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to join waiting list',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while joining waiting list'
            ], 500);
        }
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
