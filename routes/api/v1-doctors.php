<?php

use App\Http\Controllers\PublicDoctorController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/doctors', [PublicDoctorController::class, 'index']);
Route::get('/v1/doctors/{id}', [PublicDoctorController::class, 'show'])->whereNumber('id');
