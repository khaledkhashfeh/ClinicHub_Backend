<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClinicController;

// Clinic Authentication Routes
Route::prefix('clinic')->group(function () {
    Route::post('login', [ClinicController::class, 'login']);
    Route::post('logout', [ClinicController::class, 'logout'])->middleware('auth:clinic');

    // OTP Routes
    Route::post('send-otp', [ClinicController::class, 'sendOtp']);
    Route::post('verify-otp', [ClinicController::class, 'verifyOtp']);
});

// Clinic Registration Route (Public)
Route::post('clinics', [ClinicController::class, 'register']);

// Clinic Protected Routes
Route::prefix('clinics')->middleware('auth:clinic')->group(function () {
    Route::get('profile', [ClinicController::class, 'show']);
    Route::put('profile', [ClinicController::class, 'update']);
    Route::delete('profile', [ClinicController::class, 'destroy']);
});

