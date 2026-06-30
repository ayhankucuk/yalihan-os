<?php

use Illuminate\Support\Facades\Route;

// 🎯 Property Type Manager (Refactored - Phase 2.2)
// ✅ Split into 3 controllers: PropertyType, FieldDependency, FeatureAssignment
Route::prefix('/property-type-manager')->name('property_types.')->group(function () {
    // PropertyTypeController - CRUD Operations
    Route::get('/', [\App\Http\Controllers\Admin\PropertyTypeController::class, 'index'])->name('index');

    // FieldDependencyController - Field Dependencies (MUST BE BEFORE {kategoriId} wildcard)
    Route::get('/{kategoriId}/field-dependencies', [\App\Http\Controllers\Admin\FieldDependencyController::class, 'index'])->name('field_dependencies');
    Route::post('/{kategoriId}/field-dependencies', [\App\Http\Controllers\Admin\FieldDependencyController::class, 'store'])->name('field_dependencies.store');
    Route::put('/{kategoriId}/field-dependencies/{fieldId}', [\App\Http\Controllers\Admin\FieldDependencyController::class, 'update'])->name('field_dependencies.update');
    Route::delete('/{kategoriId}/field-dependencies/{fieldId}', [\App\Http\Controllers\Admin\FieldDependencyController::class, 'destroy'])->name('field_dependencies.destroy');
    Route::post('/toggle-field-dependency', [\App\Http\Controllers\Admin\FieldDependencyController::class, 'toggle'])->name('toggle_field_dependency');
    Route::post('/update-field-sequence', [\App\Http\Controllers\Admin\FieldDependencyController::class, 'updateSequence'])->name('update_field_sequence');

    // PropertyTypeController - Show (Wildcard route AFTER specific routes)
    Route::get('/{kategoriId}', [\App\Http\Controllers\Admin\PropertyTypeController::class, 'show'])->name('show');
    Route::post('/{kategoriId}/yayin-tipi', [\App\Http\Controllers\Admin\PropertyTypeController::class, 'createYayinTipi'])->name('create_yayin_tipi');
    Route::delete('/{kategoriId}/yayin-tipi/{yayinTipiId}', [\App\Http\Controllers\Admin\PropertyTypeController::class, 'destroyYayinTipi'])->name('destroy_yayin_tipi');
    Route::delete('/{kategoriId}/alt-kategori/{altKategoriId}', [\App\Http\Controllers\Admin\PropertyTypeController::class, 'destroyAltKategori'])->name('destroy_alt_kategori');
    Route::post('/{kategoriId}/toggle-yayin-tipi', [\App\Http\Controllers\Admin\PropertyTypeController::class, 'toggleYayinTipi'])->name('toggle_yayin_tipi');
    Route::post('/{kategoriId}/update-yayin-tipi-sequence', [\App\Http\Controllers\Admin\PropertyTypeController::class, 'updateYayinTipiSequence'])->name('update_yayin_tipi_sequence');

    // FeatureAssignmentController - Feature Assignments
    Route::post('/property-type/{propertyTypeId}/assign-feature', [\App\Http\Controllers\Admin\FeatureAssignmentController::class, 'assign'])->name('assign_feature');
    Route::delete('/property-type/{propertyTypeId}/unassign-feature', [\App\Http\Controllers\Admin\FeatureAssignmentController::class, 'unassign'])->name('unassign_feature');
    Route::post('/property-type/{propertyTypeId}/sync-features', [\App\Http\Controllers\Admin\FeatureAssignmentController::class, 'sync'])->name('sync_features');
    Route::post('/toggle-feature-assignment', [\App\Http\Controllers\Admin\FeatureAssignmentController::class, 'toggleAssignment'])->name('toggle_feature_assignment');
    Route::put('/feature-assignment/{assignmentId}', [\App\Http\Controllers\Admin\FeatureAssignmentController::class, 'updateAssignment'])->name('update_feature_assignment');
    Route::post('/toggle-feature', [\App\Http\Controllers\Admin\FeatureAssignmentController::class, 'toggleFeature'])->name('toggle_feature');
    Route::post('/{kategoriId}/bulk-save', [\App\Http\Controllers\Admin\FeatureAssignmentController::class, 'bulkSave'])->name('bulk_save');

    // 🆕 PHASE 3: Publication-Type Feature Suggestions (Context7: C7-FEATURE-SUGGESTIONS-API-2026-01-05)
    Route::get('/property-type/{propertyTypeId}/feature-suggestions', [\App\Http\Controllers\Admin\FeatureAssignmentController::class, 'getFeatureSuggestions'])->name('feature_suggestions');
});

// ❌ REMOVED: Legacy features-management routes (replaced by UPS Property Type Manager)
// ✅ REDIRECT: All features-management → property-type-manager
// ✅ Note: Info mesajları kaldırıldı - Property Type Manager zaten ana sayfa
Route::prefix('/features-management')->name('features-management.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.property_types.index');
    })->name('index');

    Route::get('/categories', function () {
        return redirect()->route('admin.property_types.index');
    })->name('categories.index');

    Route::get('/categories/{id}', function ($id) {
        return redirect()->route('admin.property_types.show', $id);
    })->name('categories.show');
});
