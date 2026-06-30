<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2\AuthController;

/**
 * Phase 4: V2 Authentication API Routes
 * 
 * Context7 Compliance: Token-based auth via Sanctum
 * - Public endpoints: register, login
 * - Protected endpoints: me, logout
 */

Route::prefix('auth')->name('api.auth.')->group(function () {
    // Public endpoints
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');

    // Protected endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    });
});
