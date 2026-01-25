<?php

namespace App\Http\Controllers;

use App\Http\Requests\Appointment\DoctorWorkSettingsRequest;
use App\Http\Requests\Appointment\WeeklyScheduleRequest;
use App\Models\ClinicDoctor;
use App\Models\DoctorClinicSchedule;
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
}
