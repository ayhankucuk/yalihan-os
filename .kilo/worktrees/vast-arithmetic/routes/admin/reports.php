<?php

use Illuminate\Support\Facades\Route;

// Turkish alias for reports
Route::get('/raporlar', function () {
    return redirect()->route('admin.reports.index');
})->name('raporlar.redirect');

// Raporlama Sistemi
Route::prefix('/reports')->name('reports.')->group(function () {
    Route::get('/', function () {
        return view('admin.reports.index');
    })->name('index');
    Route::get('/kisiler', function () {
        return redirect()->route('admin.reports.kisiler');
    })->name('kisiler'); // Backward compatibility alias
    Route::get('/performance', [\App\Http\Controllers\Admin\ReportingController::class, 'performanceReports'])->name('performance');
    Route::post('/export/excel', [\App\Http\Controllers\Admin\ReportingController::class, 'exportExcel'])->name('export.excel');
    Route::post('/export/pdf', [\App\Http\Controllers\Admin\ReportingController::class, 'exportPdf'])->name('export.pdf');
});
