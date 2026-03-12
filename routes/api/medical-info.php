<?php

use App\Http\Controllers\MedicalSuggestionsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/medical-info')->group(function () {
    Route::get('/suggestions', [MedicalSuggestionsController::class, 'suggestions']);
});
