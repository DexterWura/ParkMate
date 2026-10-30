<?php

use App\Domains\SocialAccounts\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect']);
Route::get('/{provider}/callback', [SocialAuthController::class, 'callback']);
