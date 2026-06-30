<?php

use App\Http\Controllers\Admin\AddressManagementController;
use App\Http\Controllers\Admin\LocationController;
use App\Modules\Admin\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Admin Routes
Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard - Ana dashboard ile çakışmayı önlemek için kaldırıldı
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Address Management (Context7 Compliant)
    Route::get('/address-management', [AddressManagementController::class, 'index'])->name('address-management.index');

    // Locations (EmlakLoc v4.0)
    // Route::resource('locations', LocationController::class); // Duplicate route - already defined in routes/admin.php
    // Route::post('locations/{location}/toggle', [LocationController::class, 'toggle'])->name('locations.toggle');

    // API Routes for locations
    Route::prefix('api/locations')->name('api.locations.')->group(function () {
        Route::get('/', [LocationController::class, 'apiIndex'])->name('index');
        Route::get('/search', [LocationController::class, 'apiSearch'])->name('search');
        Route::get('/hierarchy/{parent_id?}', [LocationController::class, 'apiHierarchy'])->name('hierarchy');
        Route::get('/popular', [LocationController::class, 'apiPopular'])->name('popular');
        Route::get('/coordinates/{location}', [LocationController::class, 'apiCoordinates'])->name('coordinates');
    });

    // İlanlar (Resource Controller)
    // Route::resource('/ilanlar', IlanController::class); // Modül oluşturulunca eklenecek

    // Müşteriler (Resource Controller)
    // Route::resource('/kisiler', KisiController::class); // Modül oluşturulunca eklenecek

    // Talepler (Resource Controller)
    // Route::resource('/talepler', TalepController::class); // Modül oluşturulunca eklenecek
});
