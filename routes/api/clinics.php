<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClinicController;
use App\Http\Controllers\InvitationController;

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

    // Additional clinic management routes that were missing
    Route::get('/', [ClinicController::class, 'index']); // List clinics (for admin or authorized users)
    Route::get('/{id}', [ClinicController::class, 'showById']); // Get specific clinic by ID
    Route::patch('/{id}/activate', [ClinicController::class, 'activate']); // Activate clinic
    Route::patch('/{id}/deactivate', [ClinicController::class, 'deactivate']); // Deactivate clinic

    // Clinic doctor management routes
    Route::get('/doctors', [ClinicController::class, 'getDoctors']); // Get doctors associated with clinic
    Route::post('/doctors', [ClinicController::class, 'addDoctor']); // Add doctor to clinic
    Route::delete('/doctors/{doctorId}', [ClinicController::class, 'removeDoctor']); // Remove doctor from clinic
    Route::put('/doctors/{doctorId}/primary', [ClinicController::class, 'setPrimaryDoctor']); // Set primary doctor
});

// Invitation routes that were in the wrong file
Route::prefix('clinics/invitations')->middleware('auth:clinic')->group(function () {
    Route::get('/', [InvitationController::class, 'getClinicInvitations']); // Get invitations sent by clinic
    Route::post('/send', [InvitationController::class, 'sendInvitation']); // Send invitation to doctor
    Route::patch('/{invitation_id}/cancel', [InvitationController::class, 'cancelInvitation']); // Cancel invitation
});

