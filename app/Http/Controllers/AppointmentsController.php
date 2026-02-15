<?php

namespace App\Http\Controllers;

use App\Http\Requests\Appointment\DoctorWorkSettingsRequest;
use App\Http\Requests\Appointment\GenerateSlotsRequest;
use App\Http\Requests\Appointment\WeeklyScheduleRequest;
use App\Http\Requests\Appointment\OverrideRequest;
use App\Http\Requests\Appointment\CreateManualSlotsRequest;
use App\Models\ClinicDoctor;
use App\Models\DoctorClinicSchedule;
use App\Models\ScheduleOverride;
use App\Models\ScheduleSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentsController extends Controller
{
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
}
