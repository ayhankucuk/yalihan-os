<?php

use Illuminate\Support\Facades\Route;

Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'index'])->name('index');
    Route::post('/{adminNotification}/mark-read', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'markAsRead'])->name('mark-read');
    Route::post('/mark-all-read', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'markAllAsRead'])->name('mark-all-read');
    Route::get('/api', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'apiIndex'])->name('api.index');
    Route::get('/api/unread-count', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'apiUnreadCount'])->name('api.unread-count');
    // ✅ FIX (Oturum 39): Eksik route eklendi — dashboard + sidebar referansı
    Route::get('/settings', [\App\Http\Controllers\Admin\IntegrationsController::class, 'notificationSettings'])->name('settings');
    Route::post('/settings', [\App\Http\Controllers\Admin\IntegrationsController::class, 'updateNotificationSettings'])->name('settings.update');
});

Route::prefix('activity-events')->name('activity-events.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AdminActivityEventController::class, 'index'])->name('index');
    Route::get('/api', [\App\Http\Controllers\Admin\AdminActivityEventController::class, 'apiIndex'])->name('api.index');
    Route::get('/api/statistics', [\App\Http\Controllers\Admin\AdminActivityEventController::class, 'apiStatistics'])->name('api.statistics');
});
