<?php

use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

// Patient review routes
Route::middleware('auth:api')->group(function () {
    // Create review
    Route::post('/v1/reviews', [ReviewController::class, 'store']);

    // Check eligibility to review
    Route::get('/v1/reviews/check-eligibility', [ReviewController::class, 'checkEligibility']);
});

// Public review statistics
Route::get('/v1/reviews/stats/{target_type}/{target_id}', [ReviewController::class, 'stats']);

