<?php

use App\Domains\Install\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallController::class, 'index'])->name('install.index');
Route::post('/install', [InstallController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('install.store');
