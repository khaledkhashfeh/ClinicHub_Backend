<?php

use App\Http\Controllers\PublicMedicalCenterController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/medical-centers', [PublicMedicalCenterController::class, 'index']);
Route::get('/v1/medical-centers/{id}', [PublicMedicalCenterController::class, 'show'])->whereNumber('id');
