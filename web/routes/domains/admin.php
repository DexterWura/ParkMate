<?php

use App\Domains\Admin\Controllers\AuthController;
use App\Domains\Admin\Controllers\DashboardController;
use App\Domains\Admin\Controllers\ReportAdminController;
use App\Domains\Admin\Controllers\SettingsAdminController;
use App\Domains\Admin\Controllers\SpotAdminController;
use App\Domains\Admin\Controllers\UserAdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:8,1')
        ->name('login.submit');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/users', [UserAdminController::class, 'index'])->name('users.index');
        Route::post('/users', [UserAdminController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserAdminController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserAdminController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserAdminController::class, 'destroy'])->name('users.destroy');

        Route::get('/spots', [SpotAdminController::class, 'index'])->name('spots.index');
        Route::get('/spots/create', [SpotAdminController::class, 'create'])->name('spots.create');
        Route::post('/spots', [SpotAdminController::class, 'store'])->name('spots.store');
        Route::get('/spots/{spot}/edit', [SpotAdminController::class, 'edit'])->name('spots.edit');
        Route::put('/spots/{spot}', [SpotAdminController::class, 'update'])->name('spots.update');
        Route::delete('/spots/{spot}', [SpotAdminController::class, 'destroy'])->name('spots.destroy');
        Route::post('/spots/ingest', [SpotAdminController::class, 'ingest'])->name('spots.ingest');

        Route::get('/fee-rules', [\App\Domains\Admin\Controllers\FeeRuleAdminController::class, 'index'])->name('fee-rules.index');
        Route::post('/fee-rules', [\App\Domains\Admin\Controllers\FeeRuleAdminController::class, 'store'])->name('fee-rules.store');
        Route::put('/fee-rules/{feeRule}', [\App\Domains\Admin\Controllers\FeeRuleAdminController::class, 'update'])->name('fee-rules.update');
        Route::delete('/fee-rules/{feeRule}', [\App\Domains\Admin\Controllers\FeeRuleAdminController::class, 'destroy'])->name('fee-rules.destroy');

        Route::get('/reports', [ReportAdminController::class, 'index'])->name('reports.index');
        Route::delete('/reports/{report}', [ReportAdminController::class, 'destroy'])->name('reports.destroy');

        Route::get('/settings', [SettingsAdminController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsAdminController::class, 'update'])->name('settings.update');
    });
});
