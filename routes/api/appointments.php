<?php

use App\Http\Controllers\AppointmentsController;
use Illuminate\Support\Facades\Route;

Route::post('/appointments/set-doctor-work-settings', [AppointmentsController::class, 'setDoctorWorkSettings']);
Route::post('/appointments/set-weekly-schedule', [AppointmentsController::class, 'setWeeklySchedule']);
Route::post('/appointments/generate-slots', [AppointmentsController::class, 'generateSlots']);
Route::post('/appointments/create-manual-slots', [AppointmentsController::class, 'createManualSlots']);
Route::post('/appointments/add-override', [AppointmentsController::class, 'addOverride']);