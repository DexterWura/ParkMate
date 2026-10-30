<?php

use App\Domains\Billing\Controllers\BillingController;
use Illuminate\Support\Facades\Route;

Route::post('/checkout', [BillingController::class, 'checkout']);
Route::get('/portal', [BillingController::class, 'portal']);
Route::get('/subscription', [BillingController::class, 'subscription']);
Route::post('/cancel', [BillingController::class, 'cancel']);
Route::post('/resume', [BillingController::class, 'resume']);
