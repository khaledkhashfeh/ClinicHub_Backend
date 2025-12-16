<?php
use App\Http\Controllers\GovernorateController;
use Illuminate\Support\Facades\Route;


Route::get('/governorates', [GovernorateController::class, 'index']);
Route::get('/governorates/{governorate}/districts', [GovernorateController::class, 'districts']);
Route::post('/governorates/{governorate}/districts', [GovernorateController::class, 'storeDistrict']);
