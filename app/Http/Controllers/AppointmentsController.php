<?php

namespace App\Http\Controllers;

use App\Http\Requests\Appointment\DoctorWorkSettingsRequest;
use App\Models\ClinicDoctor;
use Illuminate\Http\Request;
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
}
