<?php

use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientJourneyController;
use Illuminate\Support\Facades\Route;

// Patient Authentication Routes (Public)
Route::prefix('auth')->group(function () {
    Route::post('/login', [PatientController::class, 'login']);
    Route::post('/send-otp', [PatientController::class, 'sendOtp']);
    Route::post('/verify-otp', [PatientController::class, 'verifyOtp']);
    Route::post('/resend-otp', [PatientController::class, 'resendOtp']);
    Route::post('/register', [PatientController::class, 'register'])->middleware('auth:api'); // يحتاج token من verify-otp
});

// Protected Patient Routes
Route::middleware('auth:api')->group(function () {
    Route::put('/patients/{id}', [PatientController::class, 'update']);

    // Patient journey: lab tests
    Route::get('/v1/patient/lab-tests', [PatientJourneyController::class, 'getLabTests']);
    Route::post('/v1/patient/lab-tests/{id}/upload', [PatientJourneyController::class, 'uploadLabTestResult']);

    // Patient journey: medications
    Route::get('/v1/patient/medications', [PatientJourneyController::class, 'getMedications']);
    Route::patch('/v1/patient/medications/{id}/activate', [PatientJourneyController::class, 'activateMedication']);
    Route::post('/v1/patient/medications/{id}/track-dose', [PatientJourneyController::class, 'trackDose']);
});
