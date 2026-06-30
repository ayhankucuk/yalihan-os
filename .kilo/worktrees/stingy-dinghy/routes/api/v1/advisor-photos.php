<?php

use App\Http\Controllers\Api\V1\Admin\AdvisorPhotoController;
use Illuminate\Support\Facades\Route;

/**
 * Advisor Photo Intelligence Routes
 * Phase 5.3: Photo Analysis + Quality Scoring + Auto-Ordering
 */
Route::prefix('admin/advisors')->middleware(['auth', 'admin', 'role:admin'])->group(function () {
    Route::post('{advisorId}/photos/upload', [AdvisorPhotoController::class, 'upload']);
    Route::get('{advisorId}/photos', [AdvisorPhotoController::class, 'index']);
    Route::delete('{advisorId}/photos/{photoId}', [AdvisorPhotoController::class, 'destroy']);
});
