<?php

use App\Http\Controllers\Api\V1\IlanVerticalDomainController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Vertical Domain Separation (Context7)
|--------------------------------------------------------------------------
|
| Context7 Standard: C7-SCHEMA-REFACTOR-2025-12-23
| Version: 1.0.0
|
| Bu route dosyası Vertical Domain Separation mimarisi için endpoint'leri tanımlar.
| Tüm endpoint'ler Context7Compliance middleware ile korunur.
|
| Base URL: /api/v1/ilanlar
|
*/

Route::middleware(['sab.compliance'])->prefix('ilanlar')->group(function () {
    // Turizm/Yazlık ilanları
    Route::get('/turizm', [IlanVerticalDomainController::class, 'getTurizmIlanlari'])
        ->name('api.ilanlar.turizm');

    // Arsa/Arazi ilanları
    Route::get('/arsa', [IlanVerticalDomainController::class, 'getArsaIlanlari'])
        ->name('api.ilanlar.arsa');

    // Ticari/İşyeri ilanları
    Route::get('/ticari', [IlanVerticalDomainController::class, 'getTicariIlanlari'])
        ->name('api.ilanlar.ticari');

    // Portal senkronize ilanlar
    Route::get('/portal-sync', [IlanVerticalDomainController::class, 'getPortalSyncIlanlari'])
        ->name('api.ilanlar.portal-sync');

    // İlan detay endpoint'leri
    Route::get('/{id}/full-details', [IlanVerticalDomainController::class, 'getIlanFullDetails'])
        ->name('api.ilanlar.full-details')
        ->where('id', '[0-9]+');

    Route::get('/{id}/by-domain', [IlanVerticalDomainController::class, 'getIlanByDomain'])
        ->name('api.ilanlar.by-domain')
        ->where('id', '[0-9]+');
});
