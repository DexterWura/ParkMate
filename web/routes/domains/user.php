<?php

use App\Domains\User\Controllers\AuthController;
use App\Domains\User\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

$authLimit = config('boilerplate.api.auth_rate_limit', 10);

Route::middleware("throttle:{$authLimit},1")->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::post('/change-password', [ProfileController::class, 'changePassword']);
    Route::post('/email/verification-notification', [ProfileController::class, 'resendVerification']);
});
