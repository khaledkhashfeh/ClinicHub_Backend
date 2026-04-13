<?php

use App\Http\Controllers\PublicClinicController;
use Illuminate\Support\Facades\Route;

// قائمة العيادات العامة — بدون توكن أو مع توكن مريض (للمفضلة)
Route::get('/v1/clinics', [PublicClinicController::class, 'index']);
Route::get('/v1/clinics/{id}', [PublicClinicController::class, 'show'])->whereNumber('id');
