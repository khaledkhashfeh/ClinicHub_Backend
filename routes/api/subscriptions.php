<?php

use App\Http\Controllers\SubscriptionPlanController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

// ===========================================
// Subscription Plans Routes (Public)
// ===========================================

Route::prefix('subscription-plans')->group(function () {
    // جلب جميع الخطط (مع إمكانية التصفية)
    Route::get('/', [SubscriptionPlanController::class, 'index']);
    
    // جلب خطة محددة
    Route::get('/{id}', [SubscriptionPlanController::class, 'show']);
});

// ===========================================
// My Subscription Route (Protected)
// ===========================================

Route::middleware('auth:api')->group(function () {
    // جلب اشتراكي الحالي
    Route::get('/my-subscription', [SubscriptionController::class, 'mySubscription']);
});

// ===========================================
// Admin Routes (Protected + Admin Only)
// ===========================================

Route::prefix('admin')->middleware(['auth:api', 'role:admin'])->group(function () {
    
    // إدارة الخطط
    Route::prefix('subscription-plans')->group(function () {
        Route::post('/', [SubscriptionPlanController::class, 'store']);
        Route::put('/{id}', [SubscriptionPlanController::class, 'update']);
        Route::delete('/{id}', [SubscriptionPlanController::class, 'destroy']);
    });
    
    // إدارة الاشتراكات
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index']);
        Route::post('/', [SubscriptionController::class, 'store']);
        Route::patch('/{id}', [SubscriptionController::class, 'update']);
    });
});

