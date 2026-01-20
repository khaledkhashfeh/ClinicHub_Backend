<?php

use App\Http\Controllers\AppointmentsController;
use Illuminate\Support\Facades\Route;

Route::post('/appointments/set-doctor-work-settings', [AppointmentsController::class, 'setDoctorWorkSettings']);
Route::post('/appointments/set-weekly-schedule', [AppointmentsController::class, 'setWeeklySchedule']);