<?php

use App\Http\Controllers\Api\YazlikKiralamaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Yazlık Kiralama API Routes
|--------------------------------------------------------------------------
|
| Context7 Standardı: C7-YAZLIK-KIRALAMA-API-2025-12-06
|
*/

Route::prefix('yazlik-kiralama')->name('api.yazlik-kiralama.')->group(function () {
    Route::get('/takvim/{ilan}', [YazlikKiralamaController::class, 'getCalendar'])->name('takvim');
    Route::post('/fiyat-hesapla', [YazlikKiralamaController::class, 'calculatePrice'])->name('fiyat-hesapla');
    Route::post('/musaitlik-kontrol', [YazlikKiralamaController::class, 'checkAvailability'])->name('musaitlik-kontrol');
    Route::post('/rezervasyon', [YazlikKiralamaController::class, 'createReservation'])->name('rezervasyon');

    Route::get('/fiyatlandirma/{ilan}', [YazlikKiralamaController::class, 'getPricing'])->name('fiyatlandirma.list');
    Route::post('/fiyatlandirma', [YazlikKiralamaController::class, 'createPricing'])->name('fiyatlandirma.create');
    Route::put('/fiyatlandirma/{id}', [YazlikKiralamaController::class, 'updatePricing'])->name('fiyatlandirma.update');
    Route::delete('/fiyatlandirma/{id}', [YazlikKiralamaController::class, 'deletePricing'])->name('fiyatlandirma.delete');
});
