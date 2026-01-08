<?php

use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

// Patient Authentication Routes (Public)
Route::prefix('auth')->group(function () {
    Route::post('/login', [PatientController::class, 'login']);
    Route::post('/send-otp', [PatientController::class, 'sendOtp']);
    Route::post('/verify-otp', [PatientController::class, 'verifyOtp']);
    Route::post('/resend-otp', [PatientController::class, 'resendOtp']);
    Route::post('/register', [PatientController::class, 'register']);
});

// Protected Patient Routes
Route::middleware('auth:api')->group(function () {
    Route::put('/patients/{id}', [PatientController::class, 'update']);
});

