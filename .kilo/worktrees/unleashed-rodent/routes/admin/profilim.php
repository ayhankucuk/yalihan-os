<?php

use Illuminate\Support\Facades\Route;

Route::prefix('taleplerim')->name('taleplerim.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\TalepController::class, 'index'])->name('index');
});

Route::prefix('raporlarim')->name('raporlarim.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ReportingController::class, 'raporlarim'])->name('index');
});

Route::prefix('profilim')->name('profilim.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ProfileController::class, 'index'])->name('index');
    Route::put('/', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('update');
});
