<?php

use App\Http\Controllers\MedicalSpecializationController;
use Illuminate\Support\Facades\Route;

Route::get('/medical-specializations', [MedicalSpecializationController::class, 'index']);
Route::post('/medical-specializations', [MedicalSpecializationController::class, 'store']);
Route::put('/medical-specializations/{medicalSpecialization}', [MedicalSpecializationController::class, 'update']);
Route::delete('/medical-specializations/{medicalSpecialization}', [MedicalSpecializationController::class, 'destroy']);
