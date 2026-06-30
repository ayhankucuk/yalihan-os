<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\BulkKisiController;
use Illuminate\Support\Facades\Route;

// Kullanıcılar - Full Resource Controller
Route::resource('/kullanicilar', UserController::class, [
    'except' => ['show'],
]);

// Bulk Kisi Management Routes (Context7)
// Registered at the same level as admin group, but we can declare it here.
// Note: Bulk Kisi routes in admin.php had a separate admin/bulk-kisi prefix with midleware.
// But if required inside the main admin group, we can define them as prefix('/bulk-kisi').
// Let's declare it inside the group prefix:
Route::prefix('/bulk-kisi')->name('bulk-kisi.')->group(function () {
    Route::get('/', [BulkKisiController::class, 'index'])->name('index');
    Route::get('/create', [BulkKisiController::class, 'create'])->name('create');
    Route::post('/store', [BulkKisiController::class, 'store'])->name('store');
    Route::get('/edit', [BulkKisiController::class, 'edit'])->name('edit');
    Route::put('/update', [BulkKisiController::class, 'update'])->name('update');
    Route::delete('/destroy', [BulkKisiController::class, 'destroy'])->name('destroy');
    Route::get('/export', [BulkKisiController::class, 'export'])->name('export');
    Route::post('/import', [BulkKisiController::class, 'import'])->name('import');
});
