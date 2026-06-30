<?php

use App\Modules\Analitik\Controllers\API\DashboardApiController;
use App\Modules\Analitik\Controllers\API\IstatistikApiController;
use App\Modules\Analitik\Controllers\API\RaporApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/analitik')->name('api.analitik.')->middleware(['auth:sanctum'])->group(function () {

    // Dashboard API
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [DashboardApiController::class, 'index'])->name('index');
        Route::get('/overview', [DashboardApiController::class, 'overview'])->name('overview');
        Route::get('/charts', [DashboardApiController::class, 'charts'])->name('charts');
        Route::get('/recent-activities', [DashboardApiController::class, 'recentActivities'])->name('recent-activities');
    });

    // Raporlar API
    Route::prefix('raporlar')->name('raporlar.')->group(function () {
        Route::get('/', [RaporApiController::class, 'index'])->name('index');
        Route::get('/ilan', [RaporApiController::class, 'ilanRaporu'])->name('ilan');
        Route::get('/satis', [RaporApiController::class, 'satisRaporu'])->name('satis');
        Route::get('/finans', [RaporApiController::class, 'finansRaporu'])->name('finans');
        Route::get('/musteri', [RaporApiController::class, 'musteriRaporu'])->name('musteri');
        Route::get('/performans', [RaporApiController::class, 'performansRaporu'])->name('performans');
        Route::post('/export', [RaporApiController::class, 'export'])->name('export');
    });

    // İstatistikler API
    Route::prefix('istatistikler')->name('istatistikler.')->group(function () {
        Route::get('/', [IstatistikApiController::class, 'index'])->name('index');
        Route::get('/genel', [IstatistikApiController::class, 'genel'])->name('genel');
        Route::get('/ilan', [IstatistikApiController::class, 'ilan'])->name('ilan');
        Route::get('/satis', [IstatistikApiController::class, 'satis'])->name('satis');
        Route::get('/finans', [IstatistikApiController::class, 'finans'])->name('finel');
        Route::get('/musteri', [IstatistikApiController::class, 'musteri'])->name('musteri');
        Route::get('/trends', [IstatistikApiController::class, 'trends'])->name('trends');
    });
});
