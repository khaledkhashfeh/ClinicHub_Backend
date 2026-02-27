<?php

use App\Http\Controllers\AppointmentsController;
use Illuminate\Support\Facades\Route;

// Existing routes
Route::post('/appointments/set-doctor-work-settings', [AppointmentsController::class, 'setDoctorWorkSettings']);
Route::post('/appointments/set-weekly-schedule', [AppointmentsController::class, 'setWeeklySchedule']);
Route::post('/appointments/generate-slots', [AppointmentsController::class, 'generateSlots']);
Route::post('/appointments/create-manual-slots', [AppointmentsController::class, 'createManualSlots']);
Route::post('/appointments/add-override', [AppointmentsController::class, 'addOverride']);

// New patient portal appointment booking routes
Route::prefix('appointments/{clinic_id}/patients/doctors/{doctor_id}')->group(function () {
    Route::get('/booking-info', [AppointmentsController::class, 'getBookingInfo']);
    Route::get('/available-appointments', [AppointmentsController::class, 'getAvailableAppointments']);
    Route::post('/submit', [AppointmentsController::class, 'submitAppointment']);
    Route::post('/waiting-list', [AppointmentsController::class, 'joinWaitingList']);
});

// Appointment management routes
Route::post('/v1/appointments/{appointment_id}/cancel', [AppointmentsController::class, 'cancelAppointment']);
Route::post('/v1/clinics/{clinic_id}/appointments/{appointment_id}/mark-attended', [AppointmentsController::class, 'markAppointmentAsAttended']);
Route::post('/v1/clinics/{clinic_id}/appointments/{appointment_id}/confirm-initial', [AppointmentsController::class, 'confirmAppointmentInitial']);
Route::post('/v1/clinics/{clinic_id}/appointments/{appointment_id}/confirm-final', [AppointmentsController::class, 'confirmAppointmentFinal']);