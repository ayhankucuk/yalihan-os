<?php

use App\Modules\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::middleware('web')->group(function () {
    // Login Routes
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // User Management (Admin only)
    Route::middleware(['auth', 'admin', 'role:admin'])->prefix('admin')->group(function () {
        Route::resource('users', AuthController::class);
        Route::resource('kullanicilar', AuthController::class)->names([
            'index' => 'admin.kullanicilar.index',
            'create' => 'admin.kullanicilar.create',
            'store' => 'admin.kullanicilar.store',
            'show' => 'admin.kullanicilar.show',
            'edit' => 'admin.kullanicilar.edit',
            'update' => 'admin.kullanicilar.update',
            'destroy' => 'admin.kullanicilar.destroy',
        ]);
    });
});
