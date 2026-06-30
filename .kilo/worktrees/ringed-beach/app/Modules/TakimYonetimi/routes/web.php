<?php

use App\Modules\TakimYonetimi\Controllers\Admin\GorevController;
use App\Modules\TakimYonetimi\Controllers\Admin\ProjeController;
use App\Modules\TakimYonetimi\Controllers\Admin\TakimController;
use App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController;
use Illuminate\Support\Facades\Route;

// Takım Yönetimi Admin Routes
Route::middleware(['web', 'auth', 'admin', 'role:admin'])->prefix('admin/takim-yonetimi')->name('admin.takim.')->group(function () {

    // Takım Yönetimi
    Route::get('takimlar', [TakimController::class, 'index'])->name('takimlar.index');
    Route::get('takimlar/create', [TakimController::class, 'create'])->name('takimlar.create');
    Route::post('takimlar', [TakimController::class, 'store'])->name('takimlar.store');
    Route::get('takimlar/{takim}', [TakimController::class, 'show'])->name('takimlar.show');
    Route::get('takimlar/{takim}/edit', [TakimController::class, 'edit'])->name('takimlar.edit');
    Route::put('takimlar/{takim}', [TakimController::class, 'update'])->name('takimlar.update');
    Route::delete('takimlar/{takim}', [TakimController::class, 'destroy'])->name('takimlar.destroy');

    // Takım Üye İşlemleri
    Route::post('uye-ekle', [TakimController::class, 'addMember'])->name('takimlar.uye-ekle');
    Route::post('takim/uye-ekle', [TakimController::class, 'addMember'])->name('takim-yonetimi.takim.uye-ekle'); // Alias for backward compatibility
    Route::post('uye-cikar', [TakimController::class, 'removeMember'])->name('takimlar.uye-cikar');

    // Takım Performans
    Route::get('takimlar/{takim}/performans', [TakimController::class, 'performans'])->name('takimlar.performans');
    Route::get('performans', [TakimController::class, 'performans'])->name('performans');

    // Kanban Board
    Route::get('board', [TakimController::class, 'board'])->name('board');

    // Görev Yönetimi
    Route::get('gorevler', [GorevController::class, 'index'])->name('gorevler.index');
    Route::get('gorevler/create', [GorevController::class, 'create'])->name('gorevler.create');
    Route::post('gorevler', [GorevController::class, 'store'])->name('gorevler.store');
    Route::get('gorevler/{gorev}', [GorevController::class, 'show'])->name('gorevler.show');
    Route::get('gorevler/{gorev}/edit', [GorevController::class, 'edit'])->name('gorevler.edit');
    Route::put('gorevler/{gorev}', [GorevController::class, 'update'])->name('gorevler.update');
    Route::delete('gorevler/{gorev}', [GorevController::class, 'destroy'])->name('gorevler.destroy');

    // Görev İşlemleri
    Route::get('gorevler/board', [GorevController::class, 'board'])->name('gorevler.board');
    Route::post('gorevler/{gorev}/atama', [GorevController::class, 'atama'])->name('gorevler.atama');
    Route::post('gorevler/{gorev}/durum-guncelle', [GorevController::class, 'updateStatus'])->name('gorevler.durum-guncelle');
    Route::get('gorevler/{gorev}/rapor', [GorevController::class, 'rapor'])->name('gorevler.rapor');
    Route::get('gorevler/{gorev}/gecmis', [GorevController::class, 'gecmis'])->name('gorevler.gecmis');

    // Proje Yönetimi
    Route::get('projeler', [ProjeController::class, 'index'])->name('projeler.index');
    Route::get('projeler/create', [ProjeController::class, 'create'])->name('projeler.create');
    Route::post('projeler', [ProjeController::class, 'store'])->name('projeler.store');
    Route::get('projeler/{proje}', [ProjeController::class, 'show'])->name('projeler.show');
    Route::get('projeler/{proje}/edit', [ProjeController::class, 'edit'])->name('projeler.edit');
    Route::put('projeler/{proje}', [ProjeController::class, 'update'])->name('projeler.update');
    Route::delete('projeler/{proje}', [ProjeController::class, 'destroy'])->name('projeler.destroy');

    // Proje İşlemleri
    Route::post('projeler/{proje}/gorev-ekle', [ProjeController::class, 'addTask'])->name('projeler.gorev-ekle');
    Route::get('projeler/{proje}/gorevler', [ProjeController::class, 'gorevler'])->name('projeler.gorevler');
    Route::get('projeler/{proje}/rapor', [ProjeController::class, 'rapor'])->name('projeler.rapor');
    Route::post('projeler/{proje}/progress-guncelle', [ProjeController::class, 'updateProgress'])->name('projeler.progress-guncelle');
    Route::post('projeler/{proje}/durum-guncelle', [ProjeController::class, 'updateStatus'])->name('projeler.durum-guncelle');

    // Raporlar ve İstatistikler
    Route::get('raporlar', [GorevController::class, 'raporlar'])->name('raporlar');
    Route::get('istatistikler', [GorevController::class, 'istatistikler'])->name('istatistikler');
    Route::get('dashboard', [GorevController::class, 'dashboard'])->name('dashboard');


});
