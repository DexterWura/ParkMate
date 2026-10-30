<?php

use App\Domains\Billing\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/initiate', [PaymentController::class, 'initiate']);
Route::get('/{reference}/status', [PaymentController::class, 'status']);
