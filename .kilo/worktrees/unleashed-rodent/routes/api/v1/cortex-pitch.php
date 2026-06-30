<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PitchController;

/*
|--------------------------------------------------------------------------
| Cortex Pitch Routes
|--------------------------------------------------------------------------
|
| Handles sharing and management of AI generated pitches.
|
*/

// Fix #80 (2026-05-15): auth:sanctum eklendi
Route::middleware(['auth:sanctum'])->prefix('cortex/pitch')->group(function () {
    Route::get('{noteId}/whatsapp', [PitchController::class, 'shareToWhatsapp'])->name('api.cortex.pitch.whatsapp');
});
