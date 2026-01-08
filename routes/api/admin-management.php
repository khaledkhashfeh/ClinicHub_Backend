<?php

use App\Http\Controllers\AdminManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Management API Routes
|--------------------------------------------------------------------------
|
| Routes لإدارة المستخدمين الذين لديهم صلاحيات Admin
|
*/

// تسجيل دخول Admin (عام - بدون مصادقة)
Route::post('/admin/login', [AdminManagementController::class, 'login']);
