<?php

use App\Http\Controllers\GovernorateController;
use App\Http\Controllers\MedicalSpecializationController;
use Illuminate\Support\Facades\Route;

Route::get('/governorates', [GovernorateController::class, 'index']);
Route::get('/governorates/{governorate}/districts', [GovernorateController::class, 'districts']);
Route::post('/governorates/{governorate}/districts', [GovernorateController::class, 'storeDistrict']);

Route::get('/medical-specializations', [MedicalSpecializationController::class, 'index']);
Route::post('/medical-specializations', [MedicalSpecializationController::class, 'store']);
Route::put('/medical-specializations/{medicalSpecialization}', [MedicalSpecializationController::class, 'update']);
Route::delete('/medical-specializations/{medicalSpecialization}', [MedicalSpecializationController::class, 'destroy']);

