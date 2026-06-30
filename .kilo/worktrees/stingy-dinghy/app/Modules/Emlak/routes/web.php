<?php

use App\Modules\Emlak\Controllers\FeatureController;
use Illuminate\Support\Facades\Route;

// Not: İlan rotaları ana Admin\IlanController tarafından yönetiliyor
// Route: /admin/ilanlar -> Admin\IlanController

Route::middleware(['web', 'auth', 'admin', 'role:admin'])->prefix('admin/module')->name('module.')->group(function () {
    // Özellikler yönetimi
    Route::resource('ozellikler', FeatureController::class);

    // Özellikler API rotaları
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/features/{type}', [FeatureController::class, 'getFeaturesByPropertyType'])
            ->name('features.by-property-type');
        Route::get('/feature-categories', [FeatureController::class, 'getFeatureCategories'])
            ->name('feature-categories');
    });

    // Özellikler API rotaları (Alternative paths)
    Route::prefix('api/ozellikler')->name('api.ozellikler.')->group(function () {
        Route::get('/property-type/{type}', [FeatureController::class, 'getFeaturesByPropertyType'])
            ->name('by-property-type');
        Route::get('/categories', [FeatureController::class, 'getFeatureCategories'])
            ->name('categories');
    });
});
