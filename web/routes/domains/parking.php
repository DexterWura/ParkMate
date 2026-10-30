<?php

use App\Domains\Parking\Controllers\MobileSpotController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [MobileSpotController::class, 'health']);
Route::get('/spots', [MobileSpotController::class, 'index']);
Route::get('/spots/{id}', [MobileSpotController::class, 'show']);
Route::post('/spots/{id}/reports', [MobileSpotController::class, 'report']);
Route::post('/spots', [MobileSpotController::class, 'store']);
Route::get('/search', [MobileSpotController::class, 'search']);
Route::post('/routes', [MobileSpotController::class, 'route']);
