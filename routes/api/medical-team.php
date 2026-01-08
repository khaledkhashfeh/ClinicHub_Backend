<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// Medical Team Authentication Routes (Doctors and Secretaries)
Route::prefix('medical-team')->group(function () {
    Route::post('login', [UserController::class, 'Login']);
    Route::post('logout', [UserController::class, 'logout'])->middleware('auth:api');
});
