<?php

use App\Http\Controllers\LookupController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/lookup')->group(function () {
    Route::get('/diagnosis-codes', [LookupController::class, 'diagnosisCodes']);
    Route::get('/names', [LookupController::class, 'names']);
});
