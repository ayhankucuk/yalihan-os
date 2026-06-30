<?php

use Illuminate\Support\Facades\Route;

    Route::prefix('/property-hub')->name('property-hub.')->group(function () {
        // Dashboard (Sprint 2: Modular Refactoring)
        Route::get('/', [\App\Http\Controllers\Admin\PropertyHub\DashboardController::class, 'index'])->name('index');


        // Features (Sprint 2: Modular Refactoring)
        Route::prefix('/features')->name('features.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PropertyHub\FeatureController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\PropertyHub\FeatureController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\PropertyHub\FeatureController::class, 'store'])->name('store');

            // Categories — MUST be before {feature} wildcard to prevent shadow routing
            Route::prefix('/categories')->name('categories.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\PropertyHub\FeatureController::class, 'categories'])->name('index');
            });

            // Wildcard routes — constrained to numeric IDs only
            Route::get('/{feature}', fn($feature) => redirect()->route('admin.property-hub.features.edit', $feature))->where('feature', '[0-9]+')->name('show');
            Route::get('/{feature}/edit', [\App\Http\Controllers\Admin\PropertyHub\FeatureController::class, 'edit'])->where('feature', '[0-9]+')->name('edit');
            Route::put('/{feature}', [\App\Http\Controllers\Admin\PropertyHub\FeatureController::class, 'update'])->where('feature', '[0-9]+')->name('update');
            Route::post('/{feature}/toggle', [\App\Http\Controllers\Admin\PropertyHub\FeatureController::class, 'toggle'])->where('feature', '[0-9]+')->name('toggle');
            Route::post('/{feature}/archive', [\App\Http\Controllers\Admin\PropertyHub\FeatureController::class, 'archive'])->where('feature', '[0-9]+')->name('archive');
            Route::delete('/{feature}', [\App\Http\Controllers\Admin\PropertyHub\FeatureController::class, 'destroy'])->where('feature', '[0-9]+')->name('destroy');
        });

        // Feature Packs (Sprint 2: Modular Refactoring)
        Route::prefix('/packs')->name('packs.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PropertyHub\PackController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\PropertyHub\PackController::class, 'store'])->name('store');
            Route::put('/{pack}', [\App\Http\Controllers\Admin\PropertyHub\PackController::class, 'update'])->name('update');
            Route::delete('/{pack}', [\App\Http\Controllers\Admin\PropertyHub\PackController::class, 'destroy'])->name('destroy');
            Route::post('/{pack}/apply', [\App\Http\Controllers\Admin\PropertyHub\PackController::class, 'apply'])->name('apply');
        });

        // Configuration Templates (V2 Authority: TemplateController)
        Route::prefix('/templates')->name('templates.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\TemplateController::class, 'index'])->name('index');
            Route::get('/{template}', [\App\Http\Controllers\Admin\TemplateController::class, 'show'])->where('template', '[0-9]+')->name('show');
            Route::put('/{template}', [\App\Http\Controllers\Admin\TemplateController::class, 'update'])->where('template', '[0-9]+')->name('update');
            Route::post('/assign', [\App\Http\Controllers\Admin\TemplateController::class, 'assignFeature'])->name('assign');
            Route::post('/unassign', [\App\Http\Controllers\Admin\TemplateController::class, 'removeFeature'])->name('unassign');
            Route::post('/{template}/sync', [\App\Http\Controllers\Admin\TemplateController::class, 'syncFeatures'])->where('template', '[0-9]+')->name('sync');
            Route::post('/bulk-assign', [\App\Http\Controllers\Admin\TemplateController::class, 'bulkAssign'])->name('bulk-assign');

            // Pivot & Legacy Redirects (Sprint 2: Modular Refactoring)
            Route::get('/pivot-assignments', [\App\Http\Controllers\Admin\PropertyHub\TemplateController::class, 'getPivotAssignments'])->name('pivot-assignments');
            Route::post('/pivot-assignments', [\App\Http\Controllers\Admin\PropertyHub\TemplateController::class, 'savePivotAssignments'])->name('save-pivot-assignments');
            Route::get('/edit', [\App\Http\Controllers\Admin\TemplateController::class, 'showFromQuery'])->name('edit');

            // Context7: AI Template Generator Routes (Throttled)
            Route::post('/{templateId}/ai-generate', [\App\Http\Controllers\Admin\AI\PropertyAIController::class, 'generateTemplate'])->name('ai-generate')->middleware('throttle:10,1');
            Route::post('/ai-suggest', [\App\Http\Controllers\Admin\PropertyHub\TemplateController::class, 'aiSuggestTemplate'])->name('ai-suggest')->middleware('throttle:10,1');
            Route::post('/ai-import', [\App\Http\Controllers\Admin\PropertyHub\TemplateController::class, 'store'])->name('ai-import')->middleware('throttle:20,1');

            // Advanced AI Features (Sprint 2: Modular Refactoring)
            Route::post('/ai-analyze-gaps', [\App\Http\Controllers\Admin\PropertyHub\FeatureController::class, 'aiAnalyzeGaps'])->name('ai-analyze-gaps')->middleware('throttle:10,1');
            Route::post('/ai-extract-features', [\App\Http\Controllers\Admin\PropertyHub\FeatureController::class, 'aiExtractFeatures'])->name('ai-extract-features')->middleware('throttle:10,1');
            Route::post('/apply-master', [\App\Http\Controllers\Admin\PropertyHub\TemplateController::class, 'applyMasterTemplate'])->name('apply-master');

            // Template AI Pipeline
            Route::post('/ai-pipeline/start', [\App\Http\Controllers\Admin\TemplateAiPipelineController::class, 'start'])->name('ai-pipeline.start')->middleware('throttle:10,1');
            Route::get('/ai-pipeline/{runUuid}/poll', [\App\Http\Controllers\Admin\TemplateAiPipelineController::class, 'poll'])->name('ai-pipeline.poll');

            // Template AI Design ("AI ile Tasarla" wizard)
            Route::post('/ai-design/start', [\App\Http\Controllers\Admin\TemplateAiDesignController::class, 'start'])->name('ai-design.start')->middleware('throttle:10,1');
            Route::get('/ai-design/{run}/poll', [\App\Http\Controllers\Admin\TemplateAiDesignController::class, 'poll'])->name('ai-design.poll');
            Route::post('/ai-design/apply', [\App\Http\Controllers\Admin\TemplateAiDesignController::class, 'apply'])->name('ai-design.apply')->middleware('throttle:10,1');
            Route::post('/ai-design/{audit}/rollback', [\App\Http\Controllers\Admin\TemplateAiDesignController::class, 'rollback'])->name('ai-design.rollback')->middleware('throttle:5,1');
            Route::get('/ai-design/history', [\App\Http\Controllers\Admin\TemplateAiDesignController::class, 'history'])->name('ai-design.history');
        });

        // 🎨 REDIRECT: V2 Legacy Alias → Unified Hub Templates
        Route::prefix('/yayin-tipi-sablonlari')->name('yayin-tipi-sablonlari.')->group(function () {
            Route::get('/', fn() => redirect()->route('admin.property-hub.templates.index'))->name('index');
            Route::get('/{template}', fn($t) => redirect()->route('admin.property-hub.templates.show', $t))->name('show');
        });

        // AI Suggestions (Sprint 2: Modular Refactoring)
        Route::get('/suggestions', [\App\Http\Controllers\Admin\PropertyHub\FeatureController::class, 'getSuggestions'])->name('suggestions.get');

        // Analytics (PHASE 6) (Sprint 2: Modular Refactoring)
        Route::get('/analytics', [\App\Http\Controllers\Admin\PropertyHub\DashboardController::class, 'analytics'])->name('analytics.index');

        // Export/Import & Search (Sprint 2: Modular Refactoring)
        Route::post('/export', [\App\Http\Controllers\Admin\PropertyHub\TemplateController::class, 'export'])->name('export');
        Route::post('/import', [\App\Http\Controllers\Admin\PropertyHub\TemplateController::class, 'import'])->name('import');
        Route::get('/search', [\App\Http\Controllers\Admin\PropertyHub\DashboardController::class, 'search'])->name('search');

        // Dependency Rules (visible_if, required_if, enabled_if)
        Route::prefix('/dependency-rules')->name('dependency-rules.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\DependencyRuleController::class, 'index'])->name('index');
            Route::put('/{assignmentId}', [\App\Http\Controllers\Admin\DependencyRuleController::class, 'update'])->name('update');
            Route::delete('/{assignmentId}', [\App\Http\Controllers\Admin\DependencyRuleController::class, 'destroy'])->name('destroy');
        });

        // ── Phase 4C: Governance Telemetry API (Redis gerçek zamanlı metrikler) ──
        Route::prefix('/observability')->name('observability.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\GovernanceObservabilityController::class, 'index'])->name('index');
            Route::get('/timeline', [\App\Http\Controllers\Admin\GovernanceObservabilityController::class, 'timeline'])->name('timeline');
            Route::get('/drift', [\App\Http\Controllers\Admin\GovernanceObservabilityController::class, 'drift'])->name('drift');
            Route::get('/incidents', [\App\Http\Controllers\Admin\GovernanceObservabilityController::class, 'incidents'])->name('incidents');
            // Phase 4C Redis metrik endpoint'leri
            Route::get('/metrics/daily', [\App\Http\Controllers\Admin\GovernanceObservabilityController::class, 'dailyMetrics'])->name('metrics.daily');
            Route::get('/metrics/hourly', [\App\Http\Controllers\Admin\GovernanceObservabilityController::class, 'hourlyTrend'])->name('metrics.hourly');
            Route::get('/metrics/health', [\App\Http\Controllers\Admin\GovernanceObservabilityController::class, 'healthScore'])->name('metrics.health');
        });

        // AI Field Suggestions (governance panel)
        Route::prefix('/field-suggestions')->name('field-suggestions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\FieldSuggestionController::class, 'index'])->name('index');
            Route::get('/{suggestion}', [\App\Http\Controllers\Admin\FieldSuggestionController::class, 'show'])->name('show');
            Route::post('/generate', [\App\Http\Controllers\Admin\FieldSuggestionController::class, 'generate'])->name('generate');
            Route::post('/{suggestion}/approve', [\App\Http\Controllers\Admin\FieldSuggestionController::class, 'approve'])->name('approve');
            Route::post('/{suggestion}/reject', [\App\Http\Controllers\Admin\FieldSuggestionController::class, 'reject'])->name('reject');
            Route::post('/{suggestion}/apply', [\App\Http\Controllers\Admin\FieldSuggestionController::class, 'apply'])->name('apply');
            Route::post('/{suggestion}/rollback', [\App\Http\Controllers\Admin\FieldSuggestionController::class, 'rollback'])->name('rollback');
        });
    });
