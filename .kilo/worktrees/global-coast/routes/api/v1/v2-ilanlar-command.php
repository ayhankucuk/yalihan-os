<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Command\IlanCommandController;

/*
|--------------------------------------------------------------------------
| CQRS WRITE PATH (COMMAND) ROUTES - İLANLAR
|--------------------------------------------------------------------------
| SAB CQRS: Bu dosya SADECE mutasyon (POST, PUT, PATCH, DELETE) rotalarını içerir.
|
*/

Route::middleware(['auth:sanctum', 'tenant.context'])->prefix('command/ilanlar')->group(function () {
    Route::post('/', [IlanCommandController::class, 'store'])->name('api.command.ilanlar.store');
    Route::put('{id}', [IlanCommandController::class, 'update'])->name('api.command.ilanlar.update');
    Route::patch('{id}/durum', [IlanCommandController::class, 'updateStatus'])->name('api.command.ilanlar.updateStatus');
});
