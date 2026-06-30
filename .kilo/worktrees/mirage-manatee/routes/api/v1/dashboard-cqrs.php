<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardCqrsController;

/*
|--------------------------------------------------------------------------
| Dashboard CQRS API Routes (Context7)
|--------------------------------------------------------------------------
|
| Exposing read-only data from the projection tables without touching
| the main Core database directly. Built for <300ms SLA.
|
*/

// Fix #80 (2026-05-15): auth:sanctum eklendi — dashboard KPI ve listing verileri korunuyor
Route::middleware(['auth:sanctum'])->prefix('dashboard')->group(function () {
    Route::get('/kpis', [DashboardCqrsController::class, 'getKpis']);
    Route::get('/listings', [DashboardCqrsController::class, 'getListings']);
    Route::get('/activity', [DashboardCqrsController::class, 'getActivity']);
    Route::get('/health', [DashboardCqrsController::class, 'health']);
    Route::get('/leads-trend', [DashboardCqrsController::class, 'getLeadsTrend']);
});

