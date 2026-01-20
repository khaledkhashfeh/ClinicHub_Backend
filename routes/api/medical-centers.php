<?php

use App\Http\Controllers\MedicalCenterController;
use Illuminate\Support\Facades\Route;

// Medical Center Authentication Routes (Public)
Route::prefix('center')->group(function () {
    Route::post('/login', [MedicalCenterController::class, 'login']);
    Route::post('/register-request', [MedicalCenterController::class, 'registerRequest']);
});

// Protected Medical Center Routes
Route::middleware('auth:medical_center')->group(function () {
    Route::prefix('centers')->group(function () {
        Route::put('/{id}', [MedicalCenterController::class, 'update']);
        Route::delete('/{id}', [MedicalCenterController::class, 'destroy']);
    });
});

