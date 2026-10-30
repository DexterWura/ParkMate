<?php

use App\Domains\Parking\Controllers\MobileSpotController;
use Illuminate\Support\Facades\Route;

$rate = (int) config('boilerplate.api.rate_limit', 60);

Route::middleware("throttle:{$rate},1")->group(function () {
    Route::get('/health', [MobileSpotController::class, 'health']);
    Route::get('/config', [MobileSpotController::class, 'config']);
    Route::get('/providers', [MobileSpotController::class, 'providers']);
    Route::get('/spots/nearby', [MobileSpotController::class, 'nearby']);
    Route::get('/spots', [MobileSpotController::class, 'index']);
    Route::get('/spots/{id}', [MobileSpotController::class, 'show']);
    Route::get('/search', [MobileSpotController::class, 'search']);
    Route::post('/routes', [MobileSpotController::class, 'route']);
});

Route::middleware('throttle:30,1')->group(function () {
    Route::post('/spots/{id}/reports', [MobileSpotController::class, 'report']);
});

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/spots', [MobileSpotController::class, 'store']);
});
