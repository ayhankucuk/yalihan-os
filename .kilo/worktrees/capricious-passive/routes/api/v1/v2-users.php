<?php

use App\Http\Controllers\Api\V2\UserController;
use App\Http\Controllers\Api\V2\AuthController;
use Illuminate\Support\Facades\Route;

/**
 * V2 Users & Auth API Routes
 * 
 * Context7: All field names are canonical (Context7 compliant)
 * - User management: /api/v1/users
 * - Authentication: /api/v1/auth
 * Authentication: Laravel Sanctum tokens required
 */

// 🔐 Authentication Routes (Public)
Route::prefix('auth')->group(function () {
    // Login - Get Sanctum token (aktiflik_durumu checked)
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
    
    // Get authenticated user profile
    Route::get('me', [AuthController::class, 'me'])
        ->middleware('auth:sanctum')
        ->name('auth.me');

    // Logout - Revoke Sanctum token
    Route::post('logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum')
        ->name('auth.logout');
});

// 👥 User Management Routes
Route::prefix('users')->group(function () {
    // Public endpoints (list & view)
    Route::get('/', [UserController::class, 'index'])->name('api.users.index');
    Route::get('{id}', [UserController::class, 'show'])->name('api.users.show');

    // Protected endpoints (CRUD operations)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [UserController::class, 'store'])->name('api.users.store');
        Route::put('{id}', [UserController::class, 'update'])->name('api.users.update');
        Route::delete('{id}', [UserController::class, 'destroy'])->name('api.users.destroy');
    });
});
