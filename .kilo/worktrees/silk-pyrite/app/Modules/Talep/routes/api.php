<?php

use Illuminate\Support\Facades\Route;

// ✅ NOTE: Talep modülü route'ları TalepAnaliz modülüne taşındı
// TalepController artık TalepAnaliz modülünde kullanılıyor
// Bu route'lar gelecekte TalepAnaliz modülüne entegre edilebilir
// use App\Modules\Talep\Controllers\TalepController;

// Route::prefix('api/talepler')->name('api.talepler.')->group(function () {
//     Route::get('/', [TalepController::class, 'apiIndex'])->name('index');
//     Route::post('/', [TalepController::class, 'apiStore'])->name('store');
//     Route::get('/{talep}', [TalepController::class, 'apiShow'])->name('show');
//     Route::put('/{talep}', [TalepController::class, 'apiUpdate'])->name('update');
//     Route::delete('/{talep}', [TalepController::class, 'apiDestroy'])->name('destroy');
// });
