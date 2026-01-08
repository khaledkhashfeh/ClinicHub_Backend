<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::post('notification/send-notification', [NotificationController::class, 'sendNotification']);