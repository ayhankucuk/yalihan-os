<?php

use App\Http\Controllers\Api\V2\DraftController;
use Illuminate\Support\Facades\Route;

/**
 * V2 AI Drafts API Routes
 * 
 * Context7: All field names are canonical (Context7 compliant)
 * Authentication: Laravel Sanctum tokens required
 * Versioning: /api/v1/drafts
 * 
 * Note: Drafts are created from AI, so write operations require auth
 */

Route::prefix('drafts')->middleware('auth:sanctum')->group(function () {
    // List all drafts for authenticated user
    Route::get('/', [DraftController::class, 'index'])->name('drafts.index');

    // Get specific draft
    Route::get('{id}', [DraftController::class, 'show'])->name('drafts.show');

    // Create new draft (from AI analysis or manual)
    Route::post('/', [DraftController::class, 'store'])->name('drafts.store');

    // Update draft content
    Route::put('{id}', [DraftController::class, 'update'])->name('drafts.update');

    // Delete draft
    Route::delete('{id}', [DraftController::class, 'destroy'])->name('drafts.destroy');

    // Convert draft to published listing
    Route::patch('{id}/publish', [DraftController::class, 'publish'])->name('drafts.publish');

    // Approve/reject draft (for admin)
    Route::patch('{id}/approve', [DraftController::class, 'approve'])
        ->middleware('can:approve-drafts')
        ->name('drafts.approve');
});
