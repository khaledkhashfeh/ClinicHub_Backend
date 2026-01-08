<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DoctorController;

// Doctor Authentication Routes
Route::prefix('doctor')->group(function () {
    Route::post('login', [DoctorController::class, 'login']);
    Route::post('register-request', [DoctorController::class, 'registerRequest']);
    Route::post('verify-phone', [DoctorController::class, 'verifyPhone']);
    Route::post('resend-otp', [DoctorController::class, 'resendOtp']);
    Route::post('logout', [DoctorController::class, 'logout'])->middleware('auth:api');
});

// Doctor Protected Routes
Route::prefix('doctors')
->middleware('auth:api')
->group(function () {
    Route::put('/', [DoctorController::class, 'update']);
    Route::get('profile', [DoctorController::class, 'profile']);
});
