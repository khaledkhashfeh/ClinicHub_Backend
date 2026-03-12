<?php

use App\Http\Controllers\FinanceController;
use Illuminate\Support\Facades\Route;

// Finance routes for clinics, doctors, and secretaries
Route::prefix('v1/finance')
    ->middleware('auth:api,clinic')
    ->group(function () {
        // Summary cards
        Route::get('/summary', [FinanceController::class, 'summary']);

        // Expenses list
        Route::get('/expenses', [FinanceController::class, 'expenses']);

        // Add and delete expense
        Route::post('/expenses', [FinanceController::class, 'storeExpense']);
        Route::delete('/expenses/{expense_id}', [FinanceController::class, 'deleteExpense']);

        // Categories CRUD
        Route::get('/categories', [FinanceController::class, 'categories']);
        Route::post('/categories', [FinanceController::class, 'storeCategory']);
        Route::patch('/categories/{category_id}', [FinanceController::class, 'updateCategory']);
        Route::delete('/categories/{category_id}', [FinanceController::class, 'deleteCategory']);
    });

