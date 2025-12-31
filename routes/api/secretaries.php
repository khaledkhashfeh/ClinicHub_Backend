<?php

use App\Http\Controllers\SecretaryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/secretaries', [SecretaryController::class, 'createSecretary']);
Route::post('/secretaries/login', [UserController::class, 'Login']);