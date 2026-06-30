<?php

use App\Http\Controllers\Admin\ValidationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Validation Routes
|--------------------------------------------------------------------------
|
| Routes for real-time validation of form fields
|
*/

Route::middleware(['auth', 'admin', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // ✅ Real-time field validation widget (Context7: Unique name to avoid duplicate with admin.validate.field)
    Route::post('/validate-field', [ValidationController::class, 'validateField'])
        ->name('validation-widget.field');

    // Step-level validation for form wizard
    Route::post('/validate-step', [ValidationController::class, 'validateStep'])
        ->name('validate.step');
});
