<?php

use App\Http\Controllers\AdminManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Management API Routes
|--------------------------------------------------------------------------
|
| Routes لإدارة المستخدمين الذين لديهم صلاحيات Admin
|
*/

// تسجيل دخول Admin (عام - بدون مصادقة)
Route::post('/admin/login', [AdminManagementController::class, 'login']);

// Admin Protected Routes
Route::middleware('admin')->group(function () {
    // Doctor Approval Routes
    Route::prefix('admin/doctors')->group(function () {
        Route::get('/pending', [AdminManagementController::class, 'getPendingDoctors']);
        Route::put('/{id}/approve', [AdminManagementController::class, 'approveDoctor']);
        Route::put('/{id}/reject', [AdminManagementController::class, 'rejectDoctor']);
    });

    // Clinic Approval Routes
    Route::prefix('admin/clinics')->group(function () {
        Route::get('/pending', [AdminManagementController::class, 'getPendingClinics']);
        Route::put('/{id}/approve', [AdminManagementController::class, 'approveClinic']);
        Route::put('/{id}/reject', [AdminManagementController::class, 'rejectClinic']);
    });

    // Secretary Approval Routes
    Route::prefix('admin/secretaries')->group(function () {
        Route::get('/pending', [AdminManagementController::class, 'getPendingSecretaries']);
        Route::put('/{id}/approve', [AdminManagementController::class, 'approveSecretary']);
        Route::put('/{id}/reject', [AdminManagementController::class, 'rejectSecretary']);
    });
});