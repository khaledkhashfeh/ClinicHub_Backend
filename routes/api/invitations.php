<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvitationController;

// Routes for available doctors search
Route::get('/available-doctors', [InvitationController::class, 'availableDoctors']);

// Routes for clinic invitations (protected)
Route::prefix('clinics/invitations')->middleware('auth:clinic')->group(function () {
    Route::post('/send', [InvitationController::class, 'sendInvitation']);
});

// Routes for doctor invitations (protected)
Route::prefix('doctor/invitations')->middleware('auth:api')->group(function () {
    Route::patch('/{invitation_id}', [InvitationController::class, 'respondToInvitation']);
});