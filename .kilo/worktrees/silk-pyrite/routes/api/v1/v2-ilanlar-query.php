<?php

use App\Http\Controllers\Api\Query\IlanQueryController;
use Illuminate\Support\Facades\Route;

/**
 * V2 Ilanlar (Listings) CQRS Query API Routes
 *
 * SAB Kural #1: Tenant Isolation protected via auth:sanctum + SetTenantContext middleware
 * Versioning: /api/v1/query/ilanlar
 */

Route::middleware(['auth:sanctum', 'tenant.context'])->prefix('query/ilanlar')->group(function () {
    Route::get('/', [IlanQueryController::class, 'index'])->name('api.query.ilanlar.index');
    Route::get('{id}', [IlanQueryController::class, 'show'])->name('api.query.ilanlar.show');
});
