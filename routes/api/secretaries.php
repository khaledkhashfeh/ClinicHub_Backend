<?php

use App\Http\Controllers\SecretaryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/secretaries', [SecretaryController::class, 'createSecretary']);
Route::post('/secretaries/login', [UserController::class, 'Login']);
Route::post('/secretaries/updateAccounte', [SecretaryController::class, 'editSecretary']);

// Additional secretary routes that were missing
Route::middleware('auth:api')->group(function () {
    Route::get('/secretaries/profile', [SecretaryController::class, 'getProfile']);
    Route::put('/secretaries/profile', [SecretaryController::class, 'updateProfile']);
});