<?php

use App\Http\Controllers\Admin\AnalyticsDashboardController;
use App\Http\Controllers\Admin\CRMController;
use App\Http\Controllers\Admin\IntelligenceDashboardController;
use App\Http\Controllers\Admin\KisiController;
use App\Http\Controllers\Admin\TalepController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\IlanAnalizController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

// (Legacy) Wizard test routes kaldırıldı

Route::middleware(['web', 'auth', 'verified', 'role:admin', 'sab.write.guard'])->prefix('admin')->name('admin.')->group(function () {
    // AI Command Center Dashboard routes moved to routes/ai-advanced.php
    // (Keeping admin.ai prefix group for backward compatibility if needed)

    // Main dashboard route - Context7: Controller kullanımı (kod tekrarı önlendi)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Context7: admin.dashboard.index alias (birçok view'da kullanılıyor)
    Route::get('/dashboard/index', [DashboardController::class, 'index'])->name('dashboard.index');
    // Context7: API endpoint for dashboard stats (Quality Gate Fix)
    Route::get('/dashboard/stats', [DashboardController::class, 'getDashboardStats'])->name('dashboard.stats');

    // Context7: Agent Productivity Dashboard (Stage 7)
    Route::get('/dashboard/agent', [\App\Http\Controllers\Admin\Dashboard\AgentDashboardController::class, 'index'])->name('dashboard.agent');

    // Phase 17: Investor Dashboard (CQRS Read Model — Read Only)
    Route::get('/dashboard/investor', [\App\Http\Controllers\Admin\InvestorDashboardController::class, 'index'])->name('dashboard.investor');


    // Dashboard aliases for backward compatibility
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    })->name('index');

    // Intelligence Dashboard Routes (Context7: Strategic Intelligence)
    Route::prefix('/intelligence')->name('intelligence.')->group(function () {
        Route::get('/opportunity-board', [IntelligenceDashboardController::class, 'opportunityBoard'])
            ->name('opportunity-board');
        Route::get('/opportunities', [IntelligenceDashboardController::class, 'apiOpportunities'])
            ->name('opportunities');
        Route::get('/action-score/{kisiId}', [IntelligenceDashboardController::class, 'apiActionScore'])
            ->name('action-score');
    });

    // Context7 Analytics Dashboard Routes
    // Main Revenue Dashboard (Direct Access)
    Route::get('/cortex', [\App\Http\Controllers\Admin\CortexAnalyticsController::class, 'index'])->name('cortex');

    // Architecture route
    // Analytics routes
    Route::prefix('/analytics')->name('analytics.')->group(function () {

        // Phase 19.3: Visibility Metrics Dashboard
        Route::get('/visibility', [\App\Http\Controllers\Admin\VisibilityController::class, 'index'])->name('visibility.index');
        Route::get('/visibility/{id}', [\App\Http\Controllers\Admin\VisibilityController::class, 'show'])->whereNumber('id')->name('visibility.show');

        // Project Health Dashboard
        Route::get('/context7', [AnalyticsDashboardController::class, 'index'])->name('context7');

        // Phase 19.4: AI Prompt Governance Telemetry
        Route::get('/ai-governance', [\App\Http\Controllers\Admin\AIGovernanceController::class, 'index'])->name('ai-governance');

        // ✅ Phase 4: Governance Command Center (GCC)
        Route::get('/command-center', \App\Http\Livewire\Admin\GovernanceCommandCenter::class)->name('governance.command-center');

        // ✅ Phase 4C: Governance Health Dashboard
        Route::get('/governance-dashboard', \App\Http\Livewire\Admin\GovernanceDashboard::class)->name('governance.dashboard');
    });



    // ✅ Frontend Telemetry Route (Observability Layer - Phase: Production Monitoring)
    // 🔒 Rate limited: 60 req/min per user (prevents log flooding from frontend loops)
    Route::post('/telemetry', [\App\Http\Controllers\Admin\AdminTelemetryController::class, 'store'])
        ->name('telemetry.store')
        ->middleware('throttle:60,1');

    // ✅ Copilot API — Context-aware insights endpoint
    Route::post('/copilot/insights', [\App\Http\Controllers\Admin\CopilotController::class, 'insights'])
        ->name('copilot.insights')
        ->middleware('throttle:30,1');

    // ✅ Copilot Action API — Active AI assistant for wizard
    Route::prefix('/copilot/actions')->name('copilot.actions.')->middleware('throttle:20,1')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\V1\WizardCopilotActionController::class, 'generate'])->name('generate');
        Route::post('/apply', [\App\Http\Controllers\Api\V1\WizardCopilotActionController::class, 'apply'])->name('apply');
        Route::post('/undo', [\App\Http\Controllers\Api\V1\WizardCopilotActionController::class, 'undo'])->name('undo');
        Route::post('/reject', [\App\Http\Controllers\Api\V1\WizardCopilotActionController::class, 'reject'])->name('reject');
    });

/*
    Route::prefix('/yayin-tipleri')->name('yayin-tipleri.')->group(function () {
        // ✅ CORRECTED: Yayın tipleri endpoint (was in FeatureController)
        Route::get('/{kategoriId}/liste', [\App\Http\Controllers\Admin\PropertyTypeController::class, 'getYayinTipleri'])->name('liste');
    });
*/

    // ✅ Yayın Tipi Yöneticisi artık PropertyTypeManagerController kullanıyor
    // Eski route kaldırıldı: /yayin-tipi-yoneticisi → /property-type-manager
    // Context7 Compliance: Tek route kullanımı (2025-11-11)

    // ✅ Backward compatibility: property-type-manage → property-type-manager
    // Unified Property Hub Routes (Context7)
    Route::prefix('/property-hub')->name('property-hub.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PropertyHubController::class, 'index'])->name('index');


        // Features
        Route::prefix('/features')->name('features.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PropertyHubController::class, 'features'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\PropertyHubController::class, 'createFeature'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\PropertyHubController::class, 'storeFeature'])->name('store');

            // Categories — MUST be before {feature} wildcard to prevent shadow routing
            Route::prefix('/categories')->name('categories.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\PropertyHubController::class, 'featureCategories'])->name('index');
            });

            // Wildcard routes — constrained to numeric IDs only
            Route::get('/{feature}', fn($feature) => redirect()->route('admin.property-hub.features.edit', $feature))->where('feature', '[0-9]+')->name('show');
            Route::get('/{feature}/edit', [\App\Http\Controllers\Admin\PropertyHubController::class, 'editFeature'])->where('feature', '[0-9]+')->name('edit');
            Route::put('/{feature}', [\App\Http\Controllers\Admin\PropertyHubController::class, 'updateFeature'])->where('feature', '[0-9]+')->name('update');
            Route::post('/{feature}/toggle', [\App\Http\Controllers\Admin\PropertyHubController::class, 'toggleFeature'])->where('feature', '[0-9]+')->name('toggle');
            Route::post('/{feature}/archive', [\App\Http\Controllers\Admin\PropertyHubController::class, 'archiveFeature'])->where('feature', '[0-9]+')->name('archive');
            Route::delete('/{feature}', [\App\Http\Controllers\Admin\PropertyHubController::class, 'destroyFeature'])->where('feature', '[0-9]+')->name('destroy');
        });

        // Feature Packs
        Route::prefix('/packs')->name('packs.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PropertyHubController::class, 'packs'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\PropertyHubController::class, 'storePack'])->name('store');
            Route::put('/{pack}', [\App\Http\Controllers\Admin\PropertyHubController::class, 'updatePack'])->name('update');
            Route::delete('/{pack}', [\App\Http\Controllers\Admin\PropertyHubController::class, 'destroyPack'])->name('destroy');
            Route::post('/{pack}/apply', [\App\Http\Controllers\Admin\PropertyHubController::class, 'applyPack'])->name('apply');
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

            // Pivot & Legacy Redirects
            Route::get('/pivot-assignments', [\App\Http\Controllers\Admin\PropertyHubController::class, 'getPivotAssignments'])->name('pivot-assignments');
            Route::post('/pivot-assignments', [\App\Http\Controllers\Admin\PropertyHubController::class, 'savePivotAssignments'])->name('save-pivot-assignments');
            Route::get('/edit', [\App\Http\Controllers\Admin\TemplateController::class, 'showFromQuery'])->name('edit');

            // Context7: AI Template Generator Routes (Throttled)
            Route::post('/{templateId}/ai-generate', [\App\Http\Controllers\Admin\AI\PropertyAIController::class, 'generateTemplate'])->name('ai-generate')->middleware('throttle:10,1');
            Route::post('/ai-suggest', [\App\Http\Controllers\Admin\AI\PropertyAIController::class, 'suggestTemplate'])->name('ai-suggest')->middleware('throttle:10,1');
            Route::post('/ai-import', [\App\Http\Controllers\Admin\PropertyHubController::class, 'storeTemplateStructure'])->name('ai-import')->middleware('throttle:20,1');

            // Advanced AI Features
            Route::post('/ai-analyze-gaps', [\App\Http\Controllers\Admin\AI\PropertyAIController::class, 'analyzeGaps'])->name('ai-analyze-gaps')->middleware('throttle:10,1');
            Route::post('/ai-extract-features', [\App\Http\Controllers\Admin\AI\PropertyAIController::class, 'extractFeatures'])->name('ai-extract-features')->middleware('throttle:10,1');
            Route::post('/apply-master', [\App\Http\Controllers\Admin\PropertyHubController::class, 'applyMasterTemplate'])->name('apply-master');

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

        // AI Suggestions
        Route::get('/suggestions', [\App\Http\Controllers\Admin\PropertyHubController::class, 'getSuggestions'])->name('suggestions.get');

        // Analytics (PHASE 6)
        Route::get('/analytics', [\App\Http\Controllers\Admin\PropertyHubController::class, 'analytics'])->name('analytics.index');

        // Export/Import & Search
        Route::post('/export', [\App\Http\Controllers\Admin\PropertyHubController::class, 'export'])->name('export');
        Route::post('/import', [\App\Http\Controllers\Admin\PropertyHubController::class, 'import'])->name('import');
        Route::get('/search', [\App\Http\Controllers\Admin\PropertyHubController::class, 'search'])->name('search');

        // Dependency Rules (visible_if, required_if, enabled_if)
        Route::prefix('/dependency-rules')->name('dependency-rules.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\DependencyRuleController::class, 'index'])->name('index');
            Route::put('/{assignmentId}', [\App\Http\Controllers\Admin\DependencyRuleController::class, 'update'])->name('update');
            Route::delete('/{assignmentId}', [\App\Http\Controllers\Admin\DependencyRuleController::class, 'destroy'])->name('destroy');
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

    // Yalihan Bekçi Monitoring Dashboard
    Route::prefix('/yalihan-bekci')->name('yalihan-bekci.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\YalihanBekciController::class, 'index'])->name('index');
        Route::get('/live-data', [\App\Http\Controllers\Admin\YalihanBekciController::class, 'liveData'])->name('live-data');
        Route::post('/run-check', [\App\Http\Controllers\Admin\YalihanBekciController::class, 'runCheck'])->name('run-check');
    });

    // SAB Governance Dashboard (Read-Only Observability)
    Route::prefix('/governance')->name('governance.')->group(function () {
        // Phase 4C: Governance Telemetry Dashboard (Week 3)
        Route::get('/telemetry', \App\Http\Livewire\Admin\GovernanceDashboard::class)->name('telemetry');

        Route::get('/', [\App\Http\Controllers\Admin\GovernanceController::class, 'dashboard'])->name('dashboard');

        // UPS Feature Health Matrix — integrated under governance umbrella
        Route::get('/feature-health', [\App\Http\Controllers\Admin\UpsGovernanceController::class, 'index'])->name('feature-health');
        Route::post('/feature-health/generate-proposals', [\App\Http\Controllers\Admin\UpsGovernanceController::class, 'generateHealthProposals'])->name('feature-health.generate-proposals');

        // SAB2: Cortex Decision Engine — Review Queue + Approval UI
        Route::get('/review-queue', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'reviewQueue'])->name('review-queue');
        Route::get('/decisions/{decision}', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'show'])->name('decisions.show');
        Route::post('/decisions/{decision}/approve', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'approve'])->name('decisions.approve');
        Route::post('/decisions/{decision}/reject', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'reject'])->name('decisions.reject');
        Route::get('/decision-history', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'history'])->name('decision-history');
        Route::post('/scan', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'scan'])->name('scan');

        // SAB3: Decision Safety Layer — Rollback, Suppression, Override
        Route::post('/decisions/{decision}/rollback', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'rollback'])->name('decisions.rollback');
        Route::post('/decisions/{decision}/suppress', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'suppress'])->name('decisions.suppress');
        Route::post('/decisions/{decision}/override', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'override'])->name('decisions.override');
        Route::get('/suppressions', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'suppressionList'])->name('suppression-list');
        Route::delete('/suppressions/{suppression}', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'removeSuppression'])->name('suppressions.remove');

        // SAB4: Multi-Agent Intelligence Center
        Route::get('/intelligence-center', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'intelligenceCenter'])->name('intelligence-center');
        Route::post('/suggestions/{suggestion}/approve', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'approveSuggestion'])->name('suggestions.approve');
        Route::post('/suggestions/{suggestion}/reject', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'rejectSuggestion'])->name('suggestions.reject');

        // SAB5: Operator Intelligence — Behavior Control
        Route::post('/behavior/toggle-safe-mode', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'toggleSafeMode'])->name('behavior.toggle-safe-mode');
        Route::post('/behavior/update', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'updateBehavior'])->name('behavior.update');

        // SAB6: Controlled Autonomy
        Route::get('/autonomy', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'autonomyPanel'])->name('autonomy-panel');
        Route::post('/autonomy/level', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'updateAutonomyLevel'])->name('autonomy.update-level');
        Route::post('/autonomy/pause', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'pauseSystem'])->name('autonomy.pause');
        Route::post('/autonomy/resume', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'resumeSystem'])->name('autonomy.resume');
        Route::post('/autonomy/toggle-dry-run', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'toggleDryRun'])->name('autonomy.toggle-dry-run');
        Route::post('/autonomy/budget', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'updateActionBudget'])->name('autonomy.update-budget');

        // SAB8: Decision → Action → Feedback Loop
        Route::get('/action-dashboard', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'actionDashboard'])->name('action-dashboard');
        Route::post('/decisions/{decision}/record-result', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'recordResult'])->name('decisions.record-result')->middleware('throttle:20,1');
        Route::post('/decisions/{decision}/feedback', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'addFeedback'])->name('decisions.feedback')->middleware('throttle:20,1');
        Route::post('/decisions/{decision}/simulate', [\App\Http\Controllers\Admin\DecisionEngineController::class, 'simulateAction'])->name('decisions.simulate')->middleware('throttle:10,1');
    });

    // AI Automation & Integrations Routes
    // Context7: C7-AI-AUTOMATION-INTEGRATION-2025-12-19
    Route::prefix('/integrations')->name('integrations.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\IntegrationsController::class, 'index'])->name('index');
        Route::put('/{integration}', [\App\Http\Controllers\Admin\IntegrationsController::class, 'update'])->name('update');
        Route::post('/{integration}/test', [\App\Http\Controllers\Admin\IntegrationsController::class, 'test'])->name('test');

        // n8n Workflow Management
        Route::get('/n8n-workflows', [\App\Http\Controllers\Admin\IntegrationsController::class, 'n8nWorkflows'])->name('n8n-workflows');
    });

    // Voice Search Settings
    Route::prefix('/voice-search')->name('voice-search.')->group(function () {
        Route::get('/settings', [\App\Http\Controllers\Admin\IntegrationsController::class, 'voiceSearchSettings'])->name('settings');
        Route::post('/settings', [\App\Http\Controllers\Admin\IntegrationsController::class, 'updateVoiceSearchSettings'])->name('settings.update');
    });

    // Notification Settings
    Route::prefix('/notifications')->name('notifications.')->group(function () {
        Route::get('/settings', [\App\Http\Controllers\Admin\IntegrationsController::class, 'notificationSettings'])->name('settings');
        Route::post('/settings', [\App\Http\Controllers\Admin\IntegrationsController::class, 'updateNotificationSettings'])->name('settings.update');
    });

    // Performance routes

    // Alias routes for backwards compatibility
    Route::get('/', function () {
        return redirect()->route('admin.dashboard.index');
    })->name('root');

    // Site/Apartman Management Routes
    // Route::resource('/site-apartman', \App\Http\Controllers\Admin\SiteApartmanController::class);
    Route::get('/site-apartman', [\App\Http\Controllers\Admin\SiteApartmanController::class, 'index'])->name('site-apartman.index');
    Route::get('/site-apartman/create', [\App\Http\Controllers\Admin\SiteApartmanController::class, 'create'])->name('site-apartman.create');
    Route::post('/site-apartman', [\App\Http\Controllers\Admin\SiteApartmanController::class, 'store'])->name('site-apartman.store');
    Route::get('/site-apartman/{id}/edit', [\App\Http\Controllers\Admin\SiteApartmanController::class, 'edit'])->name('site-apartman.edit');
    Route::put('/site-apartman/{id}', [\App\Http\Controllers\Admin\SiteApartmanController::class, 'update'])->name('site-apartman.update');
    Route::delete('/site-apartman/{id}', [\App\Http\Controllers\Admin\SiteApartmanController::class, 'destroy'])->name('site-apartman.destroy');

    Route::get('/site-apartman/{id}/ilceler', [\App\Http\Controllers\Admin\SiteApartmanController::class, 'getIlceler'])->name('site-apartman.ilceler');
    Route::get('/site-apartman/{id}/mahalleler', [\App\Http\Controllers\Admin\SiteApartmanController::class, 'getMahalleler'])->name('site-apartman.mahalleler');
    Route::get('/site-apartman/search', [\App\Http\Controllers\Admin\SiteApartmanController::class, 'search'])->name('site-apartman.search');

    // Anahtar Yönetimi Routes
    Route::resource('/anahtar-yonetimi', \App\Http\Controllers\Admin\AnahtarYonetimiController::class);
    Route::patch('/anahtar-yonetimi/{id}/durum', [\App\Http\Controllers\Admin\AnahtarYonetimiController::class, 'updateDurum'])->name('anahtar-yonetimi.durum');
    Route::post('/anahtar-yonetimi/{id}/deliver', [\App\Http\Controllers\Admin\AnahtarYonetimiController::class, 'deliver'])->name('anahtar-yonetimi.deliver');

    // Blog routes
    Route::prefix('/blog')->name('blog.')->group(function () {
        // Blog Dashboard
        Route::get('/', [\App\Http\Controllers\Admin\BlogController::class, 'index'])->name('index');

        // Blog Posts
        Route::resource('/posts', \App\Http\Controllers\Admin\BlogController::class)->parameters(['posts' => 'post']);

        // Blog Post Actions
        Route::post('/posts/{post}/publish', [\App\Http\Controllers\Admin\BlogController::class, 'publish'])->name('posts.publish');
        Route::post('/posts/{post}/unpublish', [\App\Http\Controllers\Admin\BlogController::class, 'unpublish'])->name('posts.unpublish');
        Route::post('/posts/{post}/feature', [\App\Http\Controllers\Admin\BlogController::class, 'feature'])->name('posts.feature');
        Route::post('/posts/{post}/stick', [\App\Http\Controllers\Admin\BlogController::class, 'stick'])->name('posts.stick');

        // Blog Categories
        Route::prefix('/categories')->name('categories.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\BlogController::class, 'categories'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\BlogController::class, 'createCategory'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\BlogController::class, 'storeCategory'])->name('store');
            Route::get('/{category}/edit', [\App\Http\Controllers\Admin\BlogController::class, 'editCategory'])->name('edit');
            Route::put('/{category}', [\App\Http\Controllers\Admin\BlogController::class, 'updateCategory'])->name('update');
            Route::delete('/{category}', [\App\Http\Controllers\Admin\BlogController::class, 'destroyCategory'])->name('destroy');
            Route::post('/{category}/toggle', [\App\Http\Controllers\Admin\BlogController::class, 'toggleCategory'])->name('toggle');
        });

        // Blog Tags
        Route::prefix('/tags')->name('tags.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\BlogController::class, 'tags'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\BlogController::class, 'createTag'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\BlogController::class, 'storeTag'])->name('store');
            Route::get('/{tag}/edit', [\App\Http\Controllers\Admin\BlogController::class, 'editTag'])->name('edit');
            Route::put('/{tag}', [\App\Http\Controllers\Admin\BlogController::class, 'updateTag'])->name('update');
            Route::delete('/{tag}', [\App\Http\Controllers\Admin\BlogController::class, 'destroyTag'])->name('destroy');
            Route::patch('/{tag}/toggle', [\App\Http\Controllers\Admin\BlogController::class, 'toggleTag'])->name('toggle');
        });

        // Blog Comments
        Route::prefix('/comments')->name('comments.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\BlogController::class, 'comments'])->name('index');
            Route::post('/{comment}/approve', [\App\Http\Controllers\Admin\BlogController::class, 'approveComment'])->name('approve');
            Route::post('/{comment}/reject', [\App\Http\Controllers\Admin\BlogController::class, 'rejectComment'])->name('reject');
            Route::post('/{comment}/spam', [\App\Http\Controllers\Admin\BlogController::class, 'markCommentAsSpam'])->name('spam');
        });

        // Blog Analytics
        Route::get('/analytics', [\App\Http\Controllers\Admin\BlogController::class, 'analytics'])->name('analytics');
        Route::post('/clear-cache', [\App\Http\Controllers\Admin\BlogController::class, 'clearSidebarCache'])->name('clear-cache');
    });

    // DEBUG: Test auth durum
    Route::get('/auth-test', function () {
        return response()->json([
            'authenticated' => Auth::check(),
            'user' => Auth::check() ? Auth::user()->email : null,
            'session_id' => session()->getId(),
            'session_keys' => array_keys(session()->all()),
        ]);
    })->name('auth.test');

    // Legacy/alias routes for Context7 menu compatibility
    // AI Redirect Routes - REMOVED (Controller deleted, functionality moved to AI Settings)

    Route::get('/analytics-redirect', function () {
        return redirect()->route('admin.analytics.index');
    })->name('analytics.redirect');

    // Turkish alias for reports
    Route::get('/raporlar', function () {
        return redirect()->route('admin.reports.index');
    })->name('raporlar.redirect');

    // Legacy redirect for my-listings (Context7 rename)
    Route::get('/my-listings', function () {
        return redirect()->route('admin.ilanlarim.index');
    })->name('my-listings.redirect');

    // CRM legacy aliases
    Route::get('/customers', function () {
        return redirect()->route('admin.crm.customers.index');
    })->name('customers.redirect');

    // "Müşteriler" Turkish aliases → Kişiler CRUD
    // Context7: GET istekleri için redirect kullanılabilir, POST/PUT/DELETE için route forwarding yapılmalı
    // ✅ FIXED: Legacy aliases (2025-12-27) - kisiler.show çakışması çözüldü
    // ✅ FIXED: Infinite redirect loop removed. These redirects shadowed the actual routes.
    // Route::get('/kisiler', function () {
    //    return redirect()->route('admin.kisiler.index');
    // })->name('kisiler.index.legacy');
    // Route::get('/kisiler/create', function () {
    //    return redirect()->route('admin.kisiler.create');
    // })->name('kisiler.create.legacy');
    // Route::get('/kisiler/{id}', function ($id) {
    //    return redirect()->route('admin.kisiler.show', ['kisi' => $id]);
    // })->whereNumber('id')->name('kisiler.show.legacy');
    // Route::get('/kisiler/{id}/edit', function ($id) {
    //    return redirect()->route('admin.kisiler.edit', ['kisi' => $id]);
    // })->whereNumber('id')->name('kisiler.edit.legacy');

    // Context7: POST/PUT/DELETE için route forwarding (redirect yerine)
    Route::post('/kisiler', [KisiController::class, 'store'])->name('admin.kisiler.store.legacy');
    Route::put('/kisiler/{kisi}', [KisiController::class, 'update'])->whereNumber('kisi')->name('admin.kisiler.update.legacy');
    Route::delete('/kisiler/{kisi}', [KisiController::class, 'destroy'])->whereNumber('kisi')->name('admin.kisiler.destroy.legacy');

    // Analitik İstatistikler Routes
    Route::prefix('/analitik/istatistikler')->name('analitik.istatistikler.')->group(function () {
        Route::get('/', [\App\Modules\Analitik\Controllers\Admin\IstatistikController::class, 'index'])->name('index');
        Route::get('/genel', [\App\Modules\Analitik\Controllers\Admin\IstatistikController::class, 'genel'])->name('genel');
        Route::get('/ilan', [\App\Modules\Analitik\Controllers\Admin\IstatistikController::class, 'ilan'])->name('ilan');
        Route::get('/satis', [\App\Modules\Analitik\Controllers\Admin\IstatistikController::class, 'satis'])->name('satis');
        Route::get('/finans', [\App\Modules\Analitik\Controllers\Admin\IstatistikController::class, 'finans'])->name('finans');
        Route::get('/musteri', [\App\Modules\Analitik\Controllers\Admin\IstatistikController::class, 'musteri'])->name('musteri');
    });

    // Sales (Satışlar) redirects → Fixed to use existing route
    Route::get('/satislar/create', function () {
        return redirect()->route('admin.analitik.istatistikler.satis');
    })->name('satislar.create');

    // İlanlarım (My Listings)
    Route::prefix('/ilanlarim')->name('ilanlarim.')->group(function () {
        Route::get('/ai-analysis', [\App\Http\Controllers\Admin\MyListingsController::class, 'aiAnalysis'])->name('ai-analysis');
        Route::get('/', [\App\Http\Controllers\Admin\MyListingsController::class, 'index'])->name('index');
        Route::post('/search', [\App\Http\Controllers\Admin\MyListingsController::class, 'search'])->name('search');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\MyListingsController::class, 'bulkAction'])->name('bulk.action');
        Route::get('/stats', [\App\Http\Controllers\Admin\MyListingsController::class, 'getStats'])->name('stats');
        Route::get('/export', [\App\Http\Controllers\Admin\MyListingsController::class, 'export'])->name('export');
    });

    // İlan Yönetimi
    // Context7 Smart İlan System Routes

    // ✅ SPRINT 3: Type Configuration API for Wizard
    Route::get('/api/type-config/{yayinTipiId}', [\App\Http\Controllers\Admin\IlanCrudController::class, 'getTypeConfig'])
        ->name('api.type-config');

    // ✅ REFACTORED: IlanController split into specialized controllers (2025-12-23)
    // CRUD Operations → IlanCrudController
    // 🎯 WIZARD: Yeni İlan Ekleme (3 Adımlı) - Resource route'dan ÖNCE tanımlanmalı
    Route::get('/ilanlar/create-wizard', [\App\Http\Controllers\Admin\IlanCrudController::class, 'create'])->name('ilanlar.create-wizard');
    Route::post('/ilanlar/ai-price-recommendation', [\App\Http\Controllers\Admin\IlanCrudController::class, 'getAiPriceRecommendation'])->name('ilanlar.ai-price');

    // ✅ FIXED: Search/Filter routes placed BEFORE resource to avoid {ilan} wildcard collision
    Route::get('/ilanlar/search', [\App\Http\Controllers\Admin\IlanSearchController::class, 'search'])->name('ilanlar.search');
    Route::get('/ilanlar/filter', [\App\Http\Controllers\Admin\IlanSearchController::class, 'filter'])->name('ilanlar.filter');

    Route::resource('/ilanlar', \App\Http\Controllers\Admin\IlanCrudController::class)
        ->parameters(['ilanlar' => 'ilan'])
        ->except(['update']); // ✅ EXCLUDE: Update handled by manual route below


    // ✅ Mahrem Bilgiler ve Portal ID Yönetimi (Resource dışı özel route'lar)
    Route::post('/ilanlar/{ilan}/owner-private', [\App\Http\Controllers\Admin\IlanCrudController::class, 'ownerPrivate'])->name('ilanlar.owner-private')->middleware('can:viewPrivateListingData,ilan');
    Route::post('/ilanlar/{ilan}/portal-ids', [\App\Http\Controllers\Admin\IlanCrudController::class, 'updatePortalIds'])->name('ilanlar.portal-ids')->middleware('can:edit-ilanlar');


    // Test route for category cascading
    // Test route for category cascading
    // DEPRECATED: Test route removed (kategori sistemi production'da test edildi)
    // DEPRECATED: Test route removed (kategori sistemi production'da test edildi)
    // Route::get('/ilanlar-test', [\App\Http\Controllers\Admin\IlanController::class, 'testCategories'])->name('ilanlar.test-categories');
    // Route::get('/ilanlar-test', [\App\Http\Controllers\Admin\IlanController::class, 'testCategories'])->name('ilanlar.test-categories');

    // ✅ SAB: Draft Save Routes (Auth + Throttle required)
    Route::post('/ilanlar/draft', [\App\Http\Controllers\Admin\IlanDraftController::class, 'save'])
        ->name('ilanlar.draft')
        ->middleware(['auth', 'throttle:30,1']); // 30 requests per minute

    Route::get('/ilanlar/draft', [\App\Http\Controllers\Admin\IlanDraftController::class, 'load'])
        ->name('ilanlar.draft.load')
        ->middleware(['auth', 'throttle:10,1']);

    Route::delete('/ilanlar/draft', [\App\Http\Controllers\Admin\IlanDraftController::class, 'clear'])
        ->name('ilanlar.draft.clear')
        ->middleware(['auth', 'throttle:10,1']);

    Route::prefix('/changelog')->name('changelog.')->group(function () {
        Route::post('/', [\App\Http\Controllers\Admin\ChangelogController::class, 'store'])->name('store');
    });

    // AI Telemetry Dashboard (Phase 13 - Epic 2)
    Route::prefix('/ai/telemetry')->name('ai.telemetry.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AITelemetryController::class, 'index'])->name('index');
        Route::get('/metrics', [\App\Http\Controllers\Admin\AITelemetryController::class, 'getMetrics'])->name('metrics');
        Route::get('/cost-overview', [\App\Http\Controllers\Admin\AITelemetryController::class, 'getCostOverview'])->name('cost-overview');
        Route::get('/provider-performance', [\App\Http\Controllers\Admin\AITelemetryController::class, 'getProviderPerformance'])->name('provider-performance');
        Route::get('/request-volume', [\App\Http\Controllers\Admin\AITelemetryController::class, 'getRequestVolume'])->name('request-volume');
        Route::get('/error-analytics', [\App\Http\Controllers\Admin\AITelemetryController::class, 'getErrorAnalytics'])->name('error-analytics');
        Route::get('/token-leaderboard', [\App\Http\Controllers\Admin\AITelemetryController::class, 'getTokenLeaderboard'])->name('token-leaderboard');
    });

    require __DIR__ . '/admin/ai.php';

    // Test route
    Route::get('/test-simple', function () {
        return 'Simple route works!';
    });
    // Smart İlan routes removed - Use standard resource routes instead

    // İlan routes extracted to modular file
    require __DIR__ . '/admin/ilanlar.php';

    // Kullanıcılar - Full Resource Controller
    Route::resource('/kullanicilar', \App\Http\Controllers\Admin\UserController::class, [
        'except' => ['show'],
    ]);

    // CRM Yönetimi (Redirect to proper CRM dashboard)
    Route::get('/crm-legacy', function () {
        return redirect()->route('admin.crm.dashboard');
    })->name('crm.legacy');

    // Task 3.4: Leads Management (New Module)
    Route::resource('/leads', \App\Http\Controllers\Admin\LeadController::class)->only(['index', 'show']);

    // Bildirimler
    Route::get('/notifications', function () {
        return view('admin.notifications.index');
    })->name('notifications.index');

    // N2: Outbound Notification Logs
    Route::prefix('/outbound-notifications')->name('outbound-notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\OutboundNotificationController::class, 'index'])->name('index');
        Route::get('/{id}', [\App\Http\Controllers\Admin\OutboundNotificationController::class, 'show'])->name('show');
        Route::post('/{id}/retry', [\App\Http\Controllers\Admin\OutboundNotificationController::class, 'retry'])->name('retry');
        Route::post('/test', [\App\Http\Controllers\Admin\OutboundNotificationController::class, 'testSend'])->name('test');
    });

    // AI Sistemi - REMOVED (consolidated into ai-settings)

    // Takım Yönetimi
    Route::get('/takim-yonetimi', function () {
        return redirect()->route('admin.takim.dashboard');
    })->name('takim-yonetimi.index');

    // Takım Yönetimi - Kısa yol (Legacy support)
    Route::get('/takim-yonetimi/takim', function () {
        return redirect()->route('admin.takim.dashboard');
    })->name('takim-yonetimi.takim.redirect');

    // Analytics (Duplicate route removed - Logic handled by AnalyticsController::index)
    // See: Route::prefix('/analytics') group above

    // Telegram Bot
    Route::get('/telegram', function () {
        return view('admin.telegram.index');
    })->name('telegram.index');

    // Ayarlar

    // İlan Kategorileri
    Route::prefix('/ilan-kategorileri')->name('ilan-kategorileri.')->group(function () {
        // AJAX endpoints - MUST BE BEFORE {kategori} wildcard routes!
        // ✅ ZERO-GAP FIX: Inline kategori güncelleme (AJAX, Context7)
        Route::post('/{id}/inline-update', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'inlineUpdate'])->name('inline-update');
        // ✅ CORRECTED: Alt kategoriler endpoint (was in FeatureController)
        Route::get('/{id}/alt-kategoriler', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'getAltKategoriler'])->name('alt-kategoriler');
        // ✅ NEW: JSON API for AJAX (Property Type Manager)
        Route::post('/api/store', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'storeJson'])->name('api.store');

        // AJAX endpoints from main group below
        // Route::get('/alt-kategoriler', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'getAltKategoriler'])->name('alt-kategoriler');
        // Route::get('/yayin-tipleri', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'getYayinTipleri'])->name('yayin-tipleri');
        Route::get('/{id}/ozellikler', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'getOzellikler'])->name('ozellikler'); // ✅ NEW: Kategoriye özel özellikler
        Route::get('/{id}/feature-manager', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'featureManager'])->name('feature-manager'); // ✅ NEW: Feature Manager UI (recursive inheritance)
        Route::get('/{id}/ai-feature-suggestions', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'getAIFeatureSuggestions'])->name('ai-feature-suggestions'); // 🤖 AI-Powered Suggestions
        Route::get('/{id}/nexus-studio', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'nexusStudio'])->name('nexus-studio'); // ✅ NEW: Nexus Studio UI (visual inheritance)

        // Nexus Control Commands
        Route::post('/{id}/override-feature', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'overrideFeature'])->name('override-feature'); // DNA Kopyalayıcı
        Route::post('/{id}/toggle-inheritance', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'toggleInheritance'])->name('toggle-inheritance'); // Miras Kesici
        Route::post('/{id}/add-feature', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'addFeature'])->name('add-feature'); // Global Havuzdan Ekleme
        Route::get('/stats', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'stats'])->name('stats');
        Route::get('/export', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'export'])->name('export');
        Route::get('/create', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'create'])->name('create');
        Route::post('/sync-features', [\App\Http\Controllers\Admin\TemplateSyncController::class, 'syncFeatures'])->name('sync-features');

        // Resource routes
        Route::get('/', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'store'])->name('store');
        Route::get('/{kategori}/edit', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'edit'])->whereNumber('kategori')->name('edit');
        Route::get('/{kategori}', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'show'])->whereNumber('kategori')->name('show');
        Route::get('/slug/{slug}', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'show'])->name('show.slug');
        Route::put('/{kategori}', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'update'])->name('update');
        Route::delete('/{kategori}', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'bulkAction'])->name('bulk.action');
        Route::post('/sirala', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'updateOrder'])->name('sirala');

        // ✅ REFACTORED: Feature management → IlanFeatureController
        // Context7 Dynamic Form Fields API
        Route::get('/dynamic-fields/{propertyType}', [\App\Http\Controllers\Admin\IlanFeatureController::class, 'getDynamicFields'])->name('dynamic-fields');
        Route::post('/ai-property-suggestions', [\App\Http\Controllers\Admin\IlanFeatureController::class, 'getAIPropertySuggestions'])->name('ai-property-suggestions');

        // Context7 Property Type AI Description API
        Route::post('/ai-property-type-description', [\App\Http\Controllers\Admin\IlanAITitleDescriptionController::class, 'generatePropertyTypeAiDescription'])->name('ai-property-type-description');

        // Context7 API Testing Endpoints
        Route::get('/api/health', [\App\Http\Controllers\Admin\IlanApiController::class, 'apiHealthCheck'])->name('api-health');
        Route::get('/api/stats', [\App\Http\Controllers\Admin\IlanApiController::class, 'apiStats'])->name('api-stats');
        Route::get('/api/performance', [\App\Http\Controllers\Admin\IlanApiController::class, 'apiPerformance'])->name('api-performance');

        // Context7 Advanced AI Features API
        Route::post('/ai-multi-language-description', [\App\Http\Controllers\Admin\IlanAITitleDescriptionController::class, 'generateMultiLanguageAiDescription'])->name('ai-multi-language-description');
        Route::post('/ai-image-based-description', [\App\Http\Controllers\Admin\IlanAITitleDescriptionController::class, 'generateImageBasedAiDescription'])->name('ai-image-based-description');
        Route::post('/ai-location-based-suggestions', [\App\Http\Controllers\Admin\IlanAITitleDescriptionController::class, 'getLocationBasedAiSuggestions'])->name('ai-location-based-suggestions');
        Route::post('/ai-price-optimization', [\App\Http\Controllers\Admin\IlanAITitleDescriptionController::class, 'optimizePriceWithAi'])->name('ai-price-optimization');

        // Features API (Context7) - Decommissioned legacy endpoint removed
        // Route removed: /ilan-kategorileri/api/features/category/{categoryId}
    });

    // AI Core System Test Routes
    Route::prefix('ai-core-test')->name('ai-core-test.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AICoreTestController::class, 'index'])->name('index');
        Route::post('/test-ai', [\App\Http\Controllers\Admin\AICoreTestController::class, 'testAI'])->name('test-ai');
        Route::post('/teach-ai', [\App\Http\Controllers\Admin\AICoreTestController::class, 'teachAI'])->name('teach-ai');
        Route::post('/test-storage', [\App\Http\Controllers\Admin\AICoreTestController::class, 'testStorage'])->name('test-storage');
        Route::get('/system-durum', [\App\Http\Controllers\Admin\AICoreTestController::class, 'getSystemStatus'])->name('system-durum');
    });

    // AI Destekli Kategori Yönetimi Routes
    Route::prefix('ai-category')->name('ai-category.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AICategoryController::class, 'index'])->name('index');

        Route::post('/analyze', [\App\Http\Controllers\Admin\AICategoryController::class, 'analyzeCategory'])->name('analyze');
        Route::post('/suggestions', [\App\Http\Controllers\Admin\AICategoryController::class, 'getCategorySuggestions'])->name('suggestions');
        Route::post('/hibrit-siralama', [\App\Http\Controllers\Admin\AICategoryController::class, 'generateHibritSiralama'])->name('hibrit-siralama');
        Route::post('/smart-form', [\App\Http\Controllers\Admin\AICategoryController::class, 'generateSmartForm'])->name('smart-form');
        Route::post('/matrix', [\App\Http\Controllers\Admin\AICategoryController::class, 'manageMatrix'])->name('matrix');
        Route::post('/teach', [\App\Http\Controllers\Admin\AICategoryController::class, 'teachAICategory'])->name('teach');
        Route::get('/analyze-all', [\App\Http\Controllers\Admin\AICategoryController::class, 'analyzeAllCategories'])->name('analyze-all');
        Route::post('/update-ai-success', [\App\Http\Controllers\Admin\AICategoryController::class, 'updateAISuccess'])->name('update-ai-success');
    });

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

    // Kişi Yönetimi (Context7 Uyumlu)
    // ✅ FIXED: admin.kisiler.show rota ismi çakışması çözüldü (2025-12-27)
    // PRIMARY: admin.kisiler.show (asıl rota, legacy redirect'lerden buraya)
    Route::prefix('/kisiler')->name('kisiler.')->group(function () {
        Route::get('/', [KisiController::class, 'index'])->name('index');
        Route::get('/create', [KisiController::class, 'create'])->name('create');
        Route::get('/create-context7', function () {
            return view('admin.kisiler.create-context7');
        })->name('create-context7');
        Route::post('/', [KisiController::class, 'store'])->name('store');
        Route::get('/search', [KisiController::class, 'search'])->name('search');
        Route::post('/check-duplicate', [KisiController::class, 'checkDuplicate'])->name('check-duplicate');
        Route::post('/bulk-action', [KisiController::class, 'bulkAction'])->name('kisi.bulk.action');
        Route::post('/ai-analyze', [KisiController::class, 'aiAnalyze'])->name('ai-analyze');
        Route::get('/takip', [KisiController::class, 'takip'])->name('takip');
        Route::get('/{kisiId}', [KisiController::class, 'show'])->whereNumber('kisiId')->name('show');
        Route::get('/{kisiId}/edit', [KisiController::class, 'edit'])->whereNumber('kisiId')->name('edit');
        Route::put('/{kisiId}', [KisiController::class, 'update'])->whereNumber('kisiId')->name('update');
        Route::delete('/{kisiId}', [KisiController::class, 'destroy'])->whereNumber('kisiId')->name('destroy');
    });

    // 🗑️ Site Özellikleri - REMOVED (Now using Polymorphic Features System)
    // Old routes removed, now managed via: /admin/ozellikler/kategoriler
    // Site Özellikleri category_id = 5 in feature_categories table

    // Wikimapia Site/Apartman Sorgulama Paneli
    Route::prefix('/wikimapia-search')->name('wikimapia-search.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'index'])->name('index');
        Route::post('/search', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'search'])->name('search');
        Route::post('/search-places', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'searchPlaces'])->name('search-places');
        Route::post('/nearby', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'nearby'])->name('nearby');
        Route::get('/place/{id}', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'getPlaceDetails'])->name('place-details');
        Route::post('/save-site', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'saveSite'])->name('save-site');
        Route::get('/saved-sites', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'getSavedSites'])->name('saved-sites');

        // ✅ SAB: TurkiyeAPI entegrasyonu route'ları (harita sistemi için)
        Route::get('/location-data', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'getLocationData'])->name('location-data');
        Route::post('/location-from-coordinates', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'getLocationFromCoordinates'])->name('location-from-coordinates');
    });

    // Danışman Yönetimi (Standardize edildi - users tablosu kullanılıyor)
    Route::prefix('/danisman')->name('danisman.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\DanismanController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\DanismanController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\DanismanController::class, 'store'])->name('store');
        Route::get('/{danisman}', [\App\Http\Controllers\Admin\DanismanController::class, 'show'])->name('show');
        Route::get('/{danisman}/edit', [\App\Http\Controllers\Admin\DanismanController::class, 'edit'])->name('edit');
        Route::put('/{danisman}', [\App\Http\Controllers\Admin\DanismanController::class, 'update'])->name('update');
        Route::delete('/{danisman}', [\App\Http\Controllers\Admin\DanismanController::class, 'destroy'])->name('destroy');

        // AJAX işlemleri
        Route::get('/search', [\App\Http\Controllers\Admin\DanismanController::class, 'search'])->name('search');
        Route::post('/toggle-durum/{danisman}', [\App\Http\Controllers\Admin\DanismanController::class, 'toggleDurum'])->name('danisman.toggle.durum');
        Route::post('/update-online-durum/{danisman}', [\App\Http\Controllers\Admin\DanismanController::class, 'updateOnlineDurumu'])->name('update-online-durum');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\DanismanController::class, 'bulkAction'])->name('danisman.bulk.action');

        // Raporlar
        Route::get('/performance-report', [\App\Http\Controllers\Admin\DanismanController::class, 'performanceReport'])->name('performance-report');
    });

    // Talepler Yönetimi
    Route::prefix('talepler')->name('talepler.')->group(function () {
        Route::get('/', [TalepController::class, 'index'])->name('index');
        Route::get('/create', [TalepController::class, 'create'])->name('create');
        Route::post('/', [TalepController::class, 'store'])->name('store');
        Route::get('/{talep}', [TalepController::class, 'show'])->name('show');
        Route::get('/{talep}/edit', [TalepController::class, 'edit'])->name('edit');
        Route::put('/{talep}', [TalepController::class, 'update'])->name('update');
        Route::delete('/{talep}', [TalepController::class, 'destroy'])->name('destroy');
        Route::get('/{talep}/eslesen', [TalepController::class, 'eslesen'])->name('eslesen');
        Route::get('/{talep}/matches', [TalepController::class, 'showMatches'])->name('matches'); // 🎯 Eşleşme Kokpiti
        Route::get('/search', [TalepController::class, 'search'])->name('search');
        Route::post('/bulk-action', [TalepController::class, 'bulkAction'])->name('talep.bulk.action');
    });

    // Eşleştirme Sistemi
    Route::prefix('/eslesmeler')->name('eslesmeler.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\EslesmeController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\EslesmeController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\EslesmeController::class, 'store'])->name('store');
        Route::get('/{eslesme}', [\App\Http\Controllers\Admin\EslesmeController::class, 'show'])->name('show');
        Route::get('/{eslesme}/edit', [\App\Http\Controllers\Admin\EslesmeController::class, 'edit'])->name('edit');
        Route::put('/{eslesme}', [\App\Http\Controllers\Admin\EslesmeController::class, 'update'])->name('update');
        Route::delete('/{eslesme}', [\App\Http\Controllers\Admin\EslesmeController::class, 'destroy'])->name('destroy');

        // Özel eşleştirme işlemleri
        Route::get('/auto-match', [\App\Http\Controllers\Admin\EslesmeController::class, 'autoMatch'])->name('auto-match');
        Route::post('/bulk-create', [\App\Http\Controllers\Admin\EslesmeController::class, 'bulkCreate'])->name('bulk-create');

        // API endpoints for form data
        Route::prefix('/api')->name('api.')->group(function () {
            Route::get('/kisiler', [\App\Http\Controllers\Admin\EslesmeController::class, 'getKisiler'])->name('kisiler');
            Route::get('/danismanlar', [\App\Http\Controllers\Admin\EslesmeController::class, 'getDanismanlar'])->name('danismanlar');
            Route::get('/talepler', [\App\Http\Controllers\Admin\EslesmeController::class, 'getTalepler'])->name('talepler');
            Route::post('/ai/eslesme-onerileri', [\App\Http\Controllers\Admin\EslesmeController::class, 'getAIEslesmeOnerileri'])->name('ai.eslesme-onerileri');
        });
    });

    // Eşleştirmeler kısa yolu (yeni sisteme yönlendir)
    Route::get('/eslesme', function () {
        return redirect()->route('admin.eslesmeler.index');
    })->name('eslesme.index');

    // ✅ Config Options Yönetim Sistemi (Kategori ve Yayın Tipi Bazlı)
    Route::prefix('/config-options')->name('config-options.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ConfigOptionController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\ConfigOptionController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\ConfigOptionController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\Admin\ConfigOptionController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [\App\Http\Controllers\Admin\ConfigOptionController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\Admin\ConfigOptionController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\ConfigOptionController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/duplicate', [\App\Http\Controllers\Admin\ConfigOptionController::class, 'duplicate'])->name('duplicate');
    });

    // ✅ UPS Policy Manager (Direct Matrix Management)
    Route::prefix('/ups/policy')->name('ups.policy.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\UpsPolicyController::class, 'index'])->name('index');
/*
        Route::post('/', [\App\Http\Controllers\Admin\UpsPolicyController::class, 'store'])->name('store');
        // Route::post('/templates/sirala', [App\Http\Controllers\Admin\UpsTemplateController::class, 'reorder'])->name('ups.templates.sirala'); // SAB Purged
        Route::delete('/{kategoriId}', [\App\Http\Controllers\Admin\UpsPolicyController::class, 'destroy'])->name('destroy');
*/
    });

    // ❌ DEPRECATED (2026-01-25): UPS Feature Manager - Consolidated into Property Hub
    // ✅ REDIRECT: All /ups/features/* → /property-hub/features/*
    Route::prefix('/ups/features')->name('ups.features.')->group(function () {
        Route::get('/', fn() => redirect()->route('admin.property-hub.features.index'))->name('index');
        Route::get('/create', fn() => redirect()->route('admin.property-hub.features.create'))->name('create');
        Route::any('/{feature}', fn($feature) => redirect()->route('admin.property-hub.features.edit', $feature))->name('edit');
    });

    // ✅ UPS Template Versions (Phase Q - Version History & Rollback)
    Route::prefix('/ups/templates/{templateId}/versions')->name('ups.template-versions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\UPS\TemplateVersionController::class, 'index'])->name('index');
        Route::get('/{versionId}', [\App\Http\Controllers\Admin\UPS\TemplateVersionController::class, 'show'])->name('show');
        Route::post('/compare', [\App\Http\Controllers\Admin\UPS\TemplateVersionController::class, 'compare'])->name('compare');
        Route::post('/{versionId}/rollback', [\App\Http\Controllers\Admin\UPS\TemplateVersionController::class, 'rollback'])->name('rollback');
        Route::delete('/{versionId}', [\App\Http\Controllers\Admin\UPS\TemplateVersionController::class, 'destroy'])->name('destroy');
        Route::patch('/{versionId}/rename', [\App\Http\Controllers\Admin\UPS\TemplateVersionController::class, 'rename'])->name('rename');
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

    // ✅ UPS Phase 1: Özellikler (Features) - All legacy routes redirected to UPS Feature Management
    // ❌ DEPRECATED: /admin/ozellikler/* (except allowed redirects)
    // ✅ CANONICAL: /admin/features-management
    Route::prefix('/ozellikler')->name('ozellikler.')->group(function () {
        // ✅ Main redirect: /admin/ozellikler → /admin/features-management
        Route::get('/', function () {
            return redirect()->route('admin.features-management.index');
        })->name('index');

        // ✅ Legacy feature UI redirect
        Route::get('/features', function () {
            return redirect()->route('admin.features-management.index');
        })->name('features.index');

        // ✅ Kategoriler - Legacy UI (AI semantic labeling için korunuyor)
        // ✅ SAB: Feature categories hala kullanılıyor (UPS'te semantic grouping için)
        Route::prefix('/kategoriler')->name('kategoriler.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'store'])->name('store');
            Route::get('/{id}', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'destroy'])->name('destroy');
            Route::get('/kategorisiz-ozellikler', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'kategorisizOzellikler'])->name('kategorisiz');
            Route::get('/{id}/ozellikler', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'ozellikler'])->name('ozellikler');
            Route::post('/{kategori}/toggle-durum', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'toggleDurum'])->name('toggle-durum');
            Route::post('/sirala', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'reorder'])->name('sirala');
            Route::post('/check-slug', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'checkSlug'])->name('slug.check');
            Route::post('/{id}/quick-update', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'quickUpdate'])->name('quick-update');
            Route::post('/{id}/duplicate', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'duplicate'])->name('duplicate');
            Route::post('/bulk-toggle-durum', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'bulkToggleDurum'])->name('bulk-toggle-durum');
            Route::post('/bulk-delete', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'bulkDelete'])->name('bulk-delete');
            Route::get('/stats', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'stats'])->name('stats');

            // ✅ SAB AI Integration for Categories
/*
            Route::post('/ai-analysis', [\App\Http\Controllers\Admin\FeatureController::class, 'analyzeCategories'])->name('ai-analysis');
            Route::post('/train-categories', [\App\Http\Controllers\Admin\FeatureController::class, 'trainCategories'])->name('train-categories');
*/
        });

        // ✅ Features - Standard CRUD (Required by category feature list)
        Route::prefix('/features')->name('features.')->group(function () {
            Route::get('/create', [\App\Http\Controllers\Admin\OzellikController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\OzellikController::class, 'store'])->name('store');
            Route::get('/{feature}/edit', [\App\Http\Controllers\Admin\OzellikController::class, 'edit'])->name('edit');
            Route::put('/{feature}', [\App\Http\Controllers\Admin\OzellikController::class, 'update'])->name('update');
            Route::delete('/{feature}', [\App\Http\Controllers\Admin\OzellikController::class, 'destroy'])->name('destroy');
        });

        // ✅ SAB AI Feature Routes
        // ⚠️ DEPRECATED: suggestFeatures, smartSearch, categorizeFeatures moved to API (AdminAIController)
        // Use /api/v1/suggest-features instead
        Route::prefix('/context7')->name('context7.')->group(function () {
            // Route::post('/suggest', [\App\Http\Controllers\Admin\FeatureController::class, 'suggestFeatures'])->name('suggest');
            // Route::post('/search', [\App\Http\Controllers\Admin\FeatureController::class, 'smartSearch'])->name('search');
            // Route::post('/categorize', [\App\Http\Controllers\Admin\FeatureController::class, 'categorizeFeatures'])->name('categorize');
/*
            Route::get('/training-durum', [\App\Http\Controllers\Admin\FeatureController::class, 'getTrainingStatus'])->name('training-durum');
            Route::post('/train/behavior', [\App\Http\Controllers\Admin\FeatureController::class, 'trainUserBehavior'])->name('train.behavior');
            Route::post('/train/market', [\App\Http\Controllers\Admin\FeatureController::class, 'trainMarketTrends'])->name('train.market');
*/
        });

        // ✅ Özellikler - Legacy bulk actions (OzellikController)
        Route::prefix('/ozellik')->name('ozellik.')->group(function () {
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\OzellikController::class, 'bulkAction'])->name('bulk.action');
        });
    });

    // Geriye dönük uyumluluk: Eski yol alias'ları (/admin/module/ozellikler/*)
    Route::get('/module/ozellikler-redirect', function () {
        return redirect()->route('admin.ozellikler.index');
    })->name('module.ozellikler.index');


    // Harita
    Route::get('/harita', [\App\Http\Controllers\Admin\MapController::class, 'index'])->name('map.index');

    // ❌ DEPRECATED: Smart Calculator (Removed 2026-01-12)
    // Reason: SmartCalculatorService and SmartCalculatorController were stubs only.
    // See routes/api/v1/admin.php for deprecation details.
    // Route::get('/smart-calculator', [\App\Http\Controllers\Admin\SmartCalculatorController::class, 'index'])->name('smart-calculator');

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

    // Finans Yönetimi (Financial Management)
    // Context7 Standardı: C7-FINANS-ADMIN-2025-11-25
    Route::prefix('/finans')->name('finans.')->group(function () {
        // Health Check
        Route::get('/health', function () {
            return response()->json(['success' => true, 'module' => 'finans', 'ai_durum' => true]);
        })->name('system-health');

        // Finansal İşlemler (Financial Transactions)
        Route::prefix('/islemler')->name('islemler.')->group(function () {
            Route::get('/', [\App\Modules\Finans\Controllers\FinansalIslemController::class, 'index'])->name('index');
            Route::get('/create', function () {
                return view('admin.finans.islemler.create');
            })->name('create');
            Route::post('/', [\App\Modules\Finans\Controllers\FinansalIslemController::class, 'store'])->name('store');
            Route::get('/{id}', [\App\Modules\Finans\Controllers\FinansalIslemController::class, 'show'])->name('show');
            Route::get('/{id}/edit', function ($id) {
                return view('admin.finans.islemler.edit', ['id' => $id]);
            })->name('edit');
            Route::put('/{id}', [\App\Modules\Finans\Controllers\FinansalIslemController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Modules\Finans\Controllers\FinansalIslemController::class, 'destroy'])->name('destroy');

            // Status Management
            Route::post('/{id}/approve', [\App\Modules\Finans\Controllers\FinansalIslemController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [\App\Modules\Finans\Controllers\FinansalIslemController::class, 'reject'])->name('reject');
            Route::post('/{id}/complete', [\App\Modules\Finans\Controllers\FinansalIslemController::class, 'complete'])->name('complete');

            // 🤖 AI-Powered Endpoints
            Route::post('/ai/analyze', [\App\Modules\Finans\Controllers\FinansalIslemController::class, 'aiAnalyze'])->name('ai.analyze');
            Route::post('/ai/predict', [\App\Modules\Finans\Controllers\FinansalIslemController::class, 'aiPredict'])->name('ai.predict');
            Route::get('/{id}/ai/invoice', [\App\Modules\Finans\Controllers\FinansalIslemController::class, 'aiSuggestInvoice'])->name('ai.invoice');
            Route::post('/ai/risk', [\App\Modules\Finans\Controllers\FinansalIslemController::class, 'aiAnalyzeRisk'])->name('ai.risk');
            Route::post('/ai/summary', [\App\Modules\Finans\Controllers\FinansalIslemController::class, 'aiGenerateSummary'])->name('ai.summary');
        });

        // Komisyonlar (Commissions) - REMOVED (views deleted, module deprecated)
    });

    // Sistem Ayarları
    Route::prefix('/ayarlar')->name('ayarlar.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AyarlarController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\AyarlarController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\AyarlarController::class, 'store'])->name('store');
        Route::post('/bulk-store', [\App\Http\Controllers\Admin\AyarlarController::class, 'bulkStore'])->name('bulk-store');
        Route::post('/bulk-update', [\App\Http\Controllers\Admin\AyarlarController::class, 'bulkUpdate'])->name('bulk-update');
        Route::post('/clear-caches', [\App\Http\Controllers\Admin\AyarlarController::class, 'clearCaches'])->name('clear-caches');

        // Enterprise Locale & Currency Control
        Route::post('/languages/toggle', [\App\Http\Controllers\Admin\AyarlarController::class, 'toggleLanguage'])->name('languages.toggle');
        Route::post('/languages/set-default', [\App\Http\Controllers\Admin\AyarlarController::class, 'setDefaultLanguage'])->name('languages.set-default');
        Route::post('/currencies/toggle', [\App\Http\Controllers\Admin\AyarlarController::class, 'toggleCurrency'])->name('currencies.toggle');
        Route::post('/currencies/set-default', [\App\Http\Controllers\Admin\AyarlarController::class, 'setDefaultCurrency'])->name('currencies.set-default');
        Route::get('/{ayar}', [\App\Http\Controllers\Admin\AyarlarController::class, 'show'])->name('show');
        Route::get('/{ayar}/edit', [\App\Http\Controllers\Admin\AyarlarController::class, 'edit'])->name('edit');
        Route::put('/{ayar}', [\App\Http\Controllers\Admin\AyarlarController::class, 'update'])->name('update');
        Route::delete('/{ayar}', [\App\Http\Controllers\Admin\AyarlarController::class, 'destroy'])->name('destroy');
    });

    // ✅ REMOVED: Duplicate route - Use admin.ayarlar.bulk-update instead
    // Route::post('/settings/update', [\App\Http\Controllers\Admin\AyarlarController::class, 'bulkUpdate'])->name('settings.update');

    // AI Ayarları
    Route::prefix('/ai-settings')->name('ai-settings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AISettingsController::class, 'index'])->name('index');
        Route::put('/', [\App\Http\Controllers\Admin\AISettingsController::class, 'update'])->name('update');
        Route::post('/', [\App\Http\Controllers\Admin\AISettingsController::class, 'update'])->name('update.post'); // POST support for form compatibility
        Route::post('/test-provider', [\App\Http\Controllers\Admin\AISettingsController::class, 'testProvider'])->name('test-provider');
        Route::post('/test-query', [\App\Http\Controllers\Admin\AISettingsController::class, 'testQuery'])->name('test-query');
        Route::post('/test-ollama', [\App\Http\Controllers\Admin\AISettingsController::class, 'testOllamaConnection'])->name('test-ollama');
        Route::get('/analytics', [\App\Http\Controllers\Admin\AISettingsController::class, 'analytics'])->name('analytics'); // Context7: Real-time analytics
        Route::get('/provider-durum', [\App\Http\Controllers\Admin\AISettingsController::class, 'getProviderStatus'])->name('provider-durum');
        Route::get('/statistics', [\App\Http\Controllers\Admin\AISettingsController::class, 'statistics'])->name('statistics');
        Route::post('/proxy-ollama', [\App\Http\Controllers\Admin\AISettingsController::class, 'proxyOllama'])->name('proxy-ollama');

        // API Key ve Ayarlar Güncelleme Route'ları (2025-11-30 eklendi)
        Route::post('/update-api-key', [\App\Http\Controllers\Admin\AISettingsController::class, 'updateApiKey'])->name('update-api-key');
        Route::post('/update-ollama-url', [\App\Http\Controllers\Admin\AISettingsController::class, 'updateOllamaUrl'])->name('update-ollama-url');
        Route::post('/update-provider-model', [\App\Http\Controllers\Admin\AISettingsController::class, 'updateProviderModel'])->name('update-provider-model');
        Route::post('/update-openai-organization', [\App\Http\Controllers\Admin\AISettingsController::class, 'updateOpenAIOrganization'])->name('update-openai-organization');
        Route::post('/update-locale', [\App\Http\Controllers\Admin\AISettingsController::class, 'updateLocale'])->name('update-locale');
        Route::post('/update-currency', [\App\Http\Controllers\Admin\AISettingsController::class, 'updateCurrency'])->name('update-currency');
    });

    // Telegram Bot Yönetimi
    Route::prefix('/telegram-bot')->name('telegram-bot.')->group(function () {
        Route::get('/', [App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'index'])->name('index');
        Route::get('/aktiflik-durumu', [App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'getAktiflikDurumu'])->name('get-aktiflik-durumu');
        Route::post('/set-webhook', [App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'setWebhook'])->name('set-webhook');
        Route::post('/send-test-message', [App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'sendTestMessage'])->name('send-test-message');
        Route::get('/webhook-info', [App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'getWebhookInfo'])->name('webhook-info');
        Route::post('/update-settings', [App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'updateSettings'])->name('update-settings');
        Route::post('/send-test', [App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'sendTestMessage'])->name('send-test');
        Route::get('/test', [App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'testBot'])->name('test');
        Route::post('/generate-pairing-code', [App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'generatePairingCode'])->name('generate-pairing-code');
    });

    // Takım Yönetimi ve Görev Dağılımı (Context7: Naming sync with config/menus.php)
    // TakimYönetimi route'ları TakimYonetimiServiceProvider tarafından
    // app/Modules/TakimYonetimi/routes/web.php üzerinden yükleniyor.
    // Duplicate tanım route:cache'i kırdığı için bu blok kaldırıldı.

    // CRM Routes (T3 Consolidated — SAB v24.0)
    Route::prefix('crm')->name('crm.')->group(function () {
        // CRM Dashboard — Canonical entrypoint
        Route::get('/', [CRMController::class, 'index'])->name('dashboard');

        // CRM Dashboard Features (Consolidated from CRMDashboardController)
        Route::get('/pipeline', [CRMController::class, 'pipeline'])->name('pipeline');
        Route::get('/lead-sources', [CRMController::class, 'leadSourceAnalytics'])->name('lead-sources');
        Route::post('/recalculate-scores', [CRMController::class, 'recalculateScores'])->name('recalculate-scores');

        // CRM AJAX Actions (Pipeline & Segment mutations via KisiScoringService)
        Route::post('/kisi/{kisi}/update-pipeline', [CRMController::class, 'updatePipelineStage'])->name('update-pipeline');
        Route::post('/kisi/{kisi}/update-segment', [CRMController::class, 'updateSegment'])->name('update-segment');

        // CRM Pipeline (Kanban) Routes - Phase 6
        Route::prefix('pipeline')->name('pipeline.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\CRM\PipelineController::class, 'index'])->name('index');
            Route::post('/{kisi}/update-stage', [\App\Http\Controllers\Admin\CRM\PipelineController::class, 'updateStage'])->name('update-stage');
            Route::post('/{kisi}/quick-note', [\App\Http\Controllers\Admin\CRM\PipelineController::class, 'quickNote'])->name('quick-note');
            Route::get('/statistics', [\App\Http\Controllers\Admin\CRM\PipelineController::class, 'statistics'])->name('statistics');
            Route::get('/{kisi}/details', [\App\Http\Controllers\Admin\CRM\PipelineController::class, 'getPersonDetails'])->name('person-details');
        });

        // CRM Activity Timeline Routes - Phase 6
        Route::prefix('people')->name('people.')->group(function () {
            Route::get('/{kisi}/activities', [\App\Http\Controllers\Admin\CRM\ActivityController::class, 'getActivities'])->name('activities');
            Route::post('/{kisi}/activities', [\App\Http\Controllers\Admin\CRM\ActivityController::class, 'storeActivity'])->name('activities.store');
            Route::delete('/{kisi}/activities/{activity}', [\App\Http\Controllers\Admin\CRM\ActivityController::class, 'deleteActivity'])->name('activities.delete');
            Route::get('/{kisi}/activities/stats', [\App\Http\Controllers\Admin\CRM\ActivityController::class, 'getActivityStats'])->name('activities.stats');
        });

        // T3-B: CRM Customer route aliases → canonical Kişi routes
        // Resolves admin.crm.customers.* references in CRM views
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/', fn () => redirect()->route('admin.kisiler.index'))->name('index');
            Route::get('/create', fn () => redirect()->route('admin.kisiler.create'))->name('create');
            Route::get('/{kisiId}', fn ($kisiId) => redirect()->route('admin.kisiler.show', ['kisiId' => $kisiId]))->whereNumber('kisiId')->name('show');
            Route::get('/{kisiId}/edit', fn ($kisiId) => redirect()->route('admin.kisiler.edit', ['kisiId' => $kisiId]))->whereNumber('kisiId')->name('edit');
        });
    });

    // Talep-Portföy Eşleştirme Routes
    // ⚠️ CRITICAL: Özel route'lar generic route'ların ÖNÜNDE olmalı!
    Route::prefix('talep-portfolyo')->name('talep-portfolyo.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\TalepPortfolyoController::class, 'index'])->name('index');

/*
        // 🎯 Özel Route'lar (ÖNCE)
        Route::get('/ai-durum', [App\Http\Controllers\Admin\TalepPortfolyoController::class, 'aiDurumu'])->name('ai-durum');
        Route::post('/toplu-analiz', [App\Http\Controllers\Admin\TalepPortfolyoController::class, 'topluAnaliz'])->name('toplu-analiz');
        Route::post('/cache-temizle', [App\Http\Controllers\Admin\TalepPortfolyoController::class, 'cacheTemizle'])->name('cache-temizle');

        // 🔀 Generic Route'lar (SONRA) - {talep} her şeyi yakalar!
        Route::get('/{talep}', [App\Http\Controllers\Admin\TalepPortfolyoController::class, 'show'])->name('show');
        Route::post('/{talep}/analiz', [App\Http\Controllers\Admin\TalepPortfolyoController::class, 'analizEt'])->name('analiz');
        Route::post('/{talep}/portfolyo-oner', [App\Http\Controllers\Admin\TalepPortfolyoController::class, 'portfolyoOner'])->name('portfolyo-oner');
*/
    });

    // Bildirim Sistemi Routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\NotificationController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\NotificationController::class, 'store'])->name('store');
        Route::get('/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'show'])->name('show');
        Route::delete('/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'destroy'])->name('destroy');

        // Bildirim İşlemleri
        Route::post('/{notification}/mark-read', [\App\Http\Controllers\Admin\NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/{notification}/mark-unread', [\App\Http\Controllers\Admin\NotificationController::class, 'markAsUnread'])->name('mark-unread');
        Route::post('/mark-all-read', [\App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');

        // API Endpoints
        Route::get('/api/statistics', [\App\Http\Controllers\Admin\NotificationController::class, 'statistics'])->name('api.statistics');
        Route::get('/api/unread-count', [\App\Http\Controllers\Admin\NotificationController::class, 'unreadCount'])->name('api.unread-count');
        Route::get('/api/recent', [\App\Http\Controllers\Admin\NotificationController::class, 'recent'])->name('api.recent');

        // Test Endpoints
        Route::get('/test', [\App\Http\Controllers\Admin\NotificationController::class, 'testRealTime'])->name('test');
        Route::get('/test-page', function () {
            return view('admin.notifications.index');
        })->name('test-page');

        Route::post('/test', [\App\Http\Controllers\Admin\NotificationController::class, 'testRealTime'])->name('test.post');
        Route::post('/test-sms', [\App\Http\Controllers\Admin\NotificationController::class, 'testSms'])->name('test-sms');
        Route::post('/test-email', [\App\Http\Controllers\Admin\NotificationController::class, 'testEmail'])->name('test-email');
    });

    // ✅ Phase S: Admin Notifications (Rezervasyon Bildirimleri)
    Route::prefix('admin-notifications')->name('admin-notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'index'])->name('index');
        Route::post('/{adminNotification}/mark-read', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/mark-all-read', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'markAllAsRead'])->name('mark-all-read');

        // API Endpoints
        Route::get('/api', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'apiIndex'])->name('api.index');
        Route::get('/api/unread-count', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'apiUnreadCount'])->name('api.unread-count');
    });

    // ✅ Phase U: Activity Events (READ-ONLY)
    Route::prefix('activity-events')->name('activity-events.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminActivityEventController::class, 'index'])->name('index');

        // API Endpoints
        Route::get('/api', [\App\Http\Controllers\Admin\AdminActivityEventController::class, 'apiIndex'])->name('api.index');
        Route::get('/api/statistics', [\App\Http\Controllers\Admin\AdminActivityEventController::class, 'apiStatistics'])->name('api.statistics');
    });

    // Danışman Özel Route'ları
    // Context7: musteri → kisi terminology
    Route::prefix('kisilerim')->name('kisilerim.')->group(function () {
        Route::get('/', [KisiController::class, 'kisilerim'])->name('index');
    });



    Route::prefix('taleplerim')->name('taleplerim.')->group(function () {
        Route::get('/', [TalepController::class, 'index'])->name('index');
    });

/*
    Route::prefix('raporlarim')->name('raporlarim.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ReportingController::class, 'raporlarim'])->name('index');
    });
*/

/*
    // User Özel Route'ları
    Route::prefix('profilim')->name('profilim.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ProfileController::class, 'index'])->name('index');
        Route::put('/', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('update');
    });
*/

    // ✅ REMOVED: UserSettingsController placeholder - Use admin.ayarlar instead
    // Route::prefix('ayarlarim')->name('ayarlarim.')->group(function () {
    //     Route::get('/', [\App\Http\Controllers\Admin\UserSettingsController::class, 'index'])->name('index');
    //     Route::put('/', [\App\Http\Controllers\Admin\UserSettingsController::class, 'update'])->name('update');
    // });

    Route::prefix('adres-yonetimi')->name('adres-yonetimi.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'index'])->name('index');

        // Context7: Specific routes FIRST (before generic /{type}/{id})
        Route::get('/ulkeler', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getUlkeler'])->name('ulkeler');
        Route::get('/bolgeler', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getBolgeler'])->name('bolgeler');
        Route::get('/iller', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getIller'])->name('iller');
        Route::get('/iller/{ulkeId}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getIllerByUlke'])->name('iller.by-ulke');
        Route::get('/ilceler', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getIlceler'])->name('ilceler');
        Route::get('/ilceler/{ilId}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getIlcelerByIl'])->name('ilceler.by-il');
        Route::get('/mahalleler', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getMahalleler'])->name('mahalleler');
        Route::get('/mahalleler/{ilceId}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getMahallelerByIlce'])->name('mahalleler.by-ilce');

        // ✅ SAB: TurkiyeAPI entegrasyonu route'ları
        Route::post('/sync-from-turkiyeapi', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'syncFromTurkiyeAPI'])->name('sync-from-turkiyeapi');
        Route::post('/fetch-from-turkiyeapi', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'fetchFromTurkiyeAPI'])->name('fetch-from-turkiyeapi');
        Route::get('/ilceler/{ilId}/turkiyeapi', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getIlcelerByIlFromTurkiyeAPI'])->name('ilceler.by-il.turkiyeapi');
        Route::get('/all-location-types/{ilceId}/turkiyeapi', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getAllLocationTypesFromTurkiyeAPI'])->name('all-location-types.turkiyeapi');

        // ✅ SAB: New API routes for enhanced address management
        Route::get('/api/provinces', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'provinces'])->name('api.provinces');
        Route::get('/api/districts/{provinceApiId}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'districts'])->name('api.districts');
        Route::get('/api/neighborhoods/{districtApiId}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'neighborhoods'])->name('api.neighborhoods');
        Route::put('/api/neighborhoods/{id}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'updateNeighborhood'])->name('api.neighborhoods.update');
        Route::post('/api/sync-all', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'syncAll'])->name('api.sync-all');
        Route::post('/api/fetch-from-turkiyeapi', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'fetchFromTurkiyeAPI'])->name('api.fetch-from-turkiyeapi');

        // Generic routes LAST (catch-all)
        Route::get('/create/{type}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'create'])->name('create');
        Route::get('/{type}/{id}/edit', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'edit'])->name('edit');
        Route::get('/{type}/{id}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'show'])->name('show');
        Route::post('/{type}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'store'])->name('store');
        Route::put('/{type}/{id}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'update'])->name('update');
        Route::delete('/{type}/{id}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'destroy'])->name('destroy');

        // Bulk actions
        Route::post('/bulk-delete', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'bulkDelete'])->name('bulk-delete');
    });

    // ✅ REMOVED: SettingsController placeholder - Use admin.ayarlar instead
    // Route::get('/ayarlar/konum', [\App\Http\Controllers\Admin\SettingsController::class, 'locationSettings'])->name('settings.location');

    // Page Analyzer Routes
    Route::prefix('/page-analyzer')->name('page-analyzer.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'index'])->name('index');
        Route::get('/dashboard', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'dashboard'])->name('dashboard');
        Route::get('/create', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'destroy'])->name('destroy');

        // Additional routes
        Route::get('/export/{id?}', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'export'])->name('export');
        Route::get('/download', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'download'])->name('download');
        Route::post('/rerun/{id}', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'rerun'])->name('rerun');
        Route::get('/metrics', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'metrics'])->name('metrics');
        Route::get('/health', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'health'])->name('health');
        Route::get('/recommendations', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'recommendations'])->name('recommendations');
    });
});



// Bulk Kisi Management Routes
Route::prefix('admin/bulk-kisi')->name('admin.bulk-kisi.')->middleware(['web', 'auth', 'admin', 'role:admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\BulkKisiController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\BulkKisiController::class, 'create'])->name('create');
    Route::post('/store', [\App\Http\Controllers\Admin\BulkKisiController::class, 'store'])->name('store');
    Route::get('/edit', [\App\Http\Controllers\Admin\BulkKisiController::class, 'edit'])->name('edit');
    Route::put('/update', [\App\Http\Controllers\Admin\BulkKisiController::class, 'update'])->name('update');
    Route::delete('/destroy', [\App\Http\Controllers\Admin\BulkKisiController::class, 'destroy'])->name('destroy');
    Route::get('/export', [\App\Http\Controllers\Admin\BulkKisiController::class, 'export'])->name('export');
    Route::post('/import', [\App\Http\Controllers\Admin\BulkKisiController::class, 'import'])->name('import');
});

// Yazlik Kiralama Management Routes
Route::prefix('admin/yazlik-kiralama')->name('admin.yazlik-kiralama.')->middleware(['web', 'auth', 'admin', 'role:admin'])->group(function () {

    // ⚠️ CRITICAL: Specific routes BEFORE dynamic {id} routes!

    // Bookings Management (MUST be first!)
    Route::get('/bookings/{id?}', [\App\Http\Controllers\Admin\YazlikKiralamaController::class, 'bookings'])->name('bookings');
    // Route::put('/bookings/{id}/durum', [\App\Http\Controllers\Admin\YazlikKiralamaController::class, 'updateBookingDurumu'])->name('bookings.update-durum');

    // Takvim - Calendar View (MUST be second!)
    Route::prefix('takvim')->name('takvim.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\TakvimController::class, 'index'])->name('index');
        Route::get('/sezonlar', [\App\Http\Controllers\Admin\TakvimController::class, 'sezonlar'])->name('sezonlar');
        Route::post('/sezon/store', [\App\Http\Controllers\Admin\TakvimController::class, 'storeSezon'])->name('sezon.store');
        Route::put('/sezon/{id}', [\App\Http\Controllers\Admin\TakvimController::class, 'updateSezon'])->name('sezon.update');
        Route::delete('/sezon/{id}', [\App\Http\Controllers\Admin\TakvimController::class, 'destroySezon'])->name('sezon.destroy');
    });

    // Resource routes (LAST - {id} catches everything else!)
    Route::get('/', [\App\Http\Controllers\Admin\YazlikKiralamaController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\YazlikKiralamaController::class, 'create'])->name('create');
    Route::post('/store', [\App\Http\Controllers\Admin\YazlikKiralamaController::class, 'store'])->name('store');
    Route::get('/{id}', [\App\Http\Controllers\Admin\YazlikKiralamaController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [\App\Http\Controllers\Admin\YazlikKiralamaController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\App\Http\Controllers\Admin\YazlikKiralamaController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\Admin\YazlikKiralamaController::class, 'destroy'])->name('destroy');
});

// DanismanAI Management Routes
Route::prefix('admin/danisman-ai')->name('admin.danisman-ai.')->middleware(['web', 'auth', 'admin', 'role:admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\DanismanAIController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\DanismanAIController::class, 'create'])->name('create');
    Route::post('/store', [\App\Http\Controllers\Admin\DanismanAIController::class, 'store'])->name('store');
    Route::get('/{id}', [\App\Http\Controllers\Admin\DanismanAIController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [\App\Http\Controllers\Admin\DanismanAIController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\App\Http\Controllers\Admin\DanismanAIController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\Admin\DanismanAIController::class, 'destroy'])->name('destroy');
    Route::post('/chat', [\App\Http\Controllers\Admin\DanismanAIController::class, 'chat'])->name('chat');
    Route::post('/analyze', [\App\Http\Controllers\Admin\DanismanAIController::class, 'analyze'])->name('analyze');
    Route::post('/suggest', [\App\Http\Controllers\Admin\DanismanAIController::class, 'suggest'])->name('suggest');
    Route::get('/analytics/data', [\App\Http\Controllers\Admin\DanismanAIController::class, 'analytics'])->name('analytics');
    Route::get('/export/{type}', [\App\Http\Controllers\Admin\DanismanAIController::class, 'export'])->name('export');
    Route::get('/prompt-interface', [\App\Http\Controllers\Admin\DanismanAIController::class, 'promptInterface'])->name('prompt-interface');
});

// KisiNot Management Routes
Route::prefix('admin/kisi-not')->name('admin.kisi-not.')->middleware(['web', 'auth', 'admin', 'role:admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\KisiNotController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\KisiNotController::class, 'create'])->name('create');
    Route::post('/store', [\App\Http\Controllers\Admin\KisiNotController::class, 'store'])->name('store');
    Route::get('/{id}', [\App\Http\Controllers\Admin\KisiNotController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [\App\Http\Controllers\Admin\KisiNotController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\App\Http\Controllers\Admin\KisiNotController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\Admin\KisiNotController::class, 'destroy'])->name('destroy');
    Route::post('/bulk', [\App\Http\Controllers\Admin\KisiNotController::class, 'bulk'])->name('bulk');
    Route::get('/export', [\App\Http\Controllers\Admin\KisiNotController::class, 'export'])->name('export');
    Route::get('/search', [\App\Http\Controllers\Admin\KisiNotController::class, 'search'])->name('search');
});

// AI Category Suggestions Routes
Route::prefix('admin/ai-category')->group(function () {
/*
    Route::post('/suggest', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'suggestCategories']);
    Route::get('/trends', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'getTrends']);
    Route::get('/performance', [\App\Http\Controllers\Admin\IlanKategoriController::class, 'getPerformance']);
*/
});

// Performance Monitoring Routes

// Analytics Dashboard Routes
Route::prefix('admin/analytics')->name('admin.analytics.')->group(function () {
    Route::get('/', [AnalyticsController::class, 'index'])->name('index');
    Route::get('/data', [AnalyticsController::class, 'data'])->name('data');
    Route::get('/{id}', [AnalyticsController::class, 'show'])->name('show');
});

// Feature Category Management Routes - REMOVED (unused)

// Address Management Routes
Route::prefix('admin/address')->name('admin.address.')->middleware(['web', 'auth', 'role:admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AddressController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\AddressController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\AddressController::class, 'store'])->name('store');
    Route::get('/{address}', [\App\Http\Controllers\Admin\AddressController::class, 'show'])->name('show');
    Route::get('/{address}/edit', [\App\Http\Controllers\Admin\AddressController::class, 'edit'])->name('edit');
    Route::put('/{address}', [\App\Http\Controllers\Admin\AddressController::class, 'update'])->name('update');
    Route::delete('/{address}', [\App\Http\Controllers\Admin\AddressController::class, 'destroy'])->name('destroy');

    // AJAX endpoints
    Route::get('/districts', [\App\Http\Controllers\Admin\AddressController::class, 'getDistricts'])->name('districts');
    Route::get('/neighborhoods', [\App\Http\Controllers\Admin\AddressController::class, 'getNeighborhoods'])->name('neighborhoods');
    Route::post('/bulk', [\App\Http\Controllers\Admin\AddressController::class, 'bulkAction'])->name('bulk');
});

// Etiket Management Routes — kaldırıldı (EtiketController yok, CRM etiket feature backlog'da)

// Location Management Routes
Route::prefix('admin/locations')->name('admin.locations.')->middleware(['web', 'auth', 'role:admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\LocationController::class, 'index'])->name('index');
    Route::get('/{id}', [\App\Http\Controllers\Admin\LocationController::class, 'show'])->name('show');

    // API endpoints
    Route::get('/api/provinces', [\App\Http\Controllers\Admin\LocationController::class, 'getProvinces'])->name('provinces');
    Route::get('/api/districts', [\App\Http\Controllers\Admin\LocationController::class, 'getDistricts'])->name('districts');
    Route::get('/api/neighborhoods', [\App\Http\Controllers\Admin\LocationController::class, 'getNeighborhoods'])->name('neighborhoods');
});

// KisiNot Management Routes


// Pazar İstihbaratı (Market Intelligence) Routes
Route::prefix('admin/market-intelligence')->name('admin.market-intelligence.')->middleware(['web', 'auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\MarketIntelligenceController::class, 'dashboard'])->name('dashboard');
    Route::get('/settings', [\App\Http\Controllers\Admin\MarketIntelligenceController::class, 'settings'])->name('settings');
    Route::get('/compare/{ilan?}', [\App\Http\Controllers\Admin\MarketIntelligenceController::class, 'compare'])->name('compare');
    Route::get('/trends', [\App\Http\Controllers\Admin\MarketIntelligenceController::class, 'trends'])->name('trends');
});

// Pazar İstihbaratı API Routes (n8n bot ve AJAX için)
Route::prefix('api/market-intelligence')->name('api.market-intelligence.')->middleware(['web', 'auth'])->group(function () {
    // n8n bot için: Aktif bölgeleri getir
    Route::get('/active-regions', [\App\Http\Controllers\Admin\MarketIntelligenceController::class, 'getActiveRegions'])->name('active-regions');

    // Bölge ayarları yönetimi
    Route::post('/settings', [\App\Http\Controllers\Admin\MarketIntelligenceController::class, 'saveSettings'])->name('settings.save');
    Route::delete('/settings/{id}', [\App\Http\Controllers\Admin\MarketIntelligenceController::class, 'deleteSetting'])->name('settings.delete');
    Route::patch('/settings/{id}/toggle', [\App\Http\Controllers\Admin\MarketIntelligenceController::class, 'toggleSetting'])->name('settings.toggle');
});

// n8n Bot Sync Endpoint (CSRF exempt - n8n secret middleware ile korumalı)
Route::prefix('api/admin/market-intelligence')->name('admin.api.market-intelligence.')->middleware(['web', 'auth'])->group(function () {
    Route::post('/compare-price', [\App\Http\Controllers\Admin\MarketIntelligenceController::class, 'comparePrice'])->name('compare-price');
    Route::post('/analyze-trends', [\App\Http\Controllers\Admin\MarketIntelligenceController::class, 'analyzeTrends'])->name('analyze-trends');
    Route::post('/sync', [\App\Http\Controllers\Admin\MarketIntelligenceController::class, 'sync'])->name('sync');
});

// Property Events (Rental Engine API for Alpine Component)
Route::prefix('api/admin')->name('admin.api.events.')->middleware(['web', 'auth'])->group(function () {
    Route::get('/ilanlar/{ilan}/events', [\App\Http\Controllers\Admin\PropertyEventApiController::class, 'index'])->name('index');
    Route::post('/events', [\App\Http\Controllers\Admin\PropertyEventApiController::class, 'store'])->name('store');
    Route::patch('/events/{event}', [\App\Http\Controllers\Admin\PropertyEventApiController::class, 'update'])->name('update');
    Route::delete('/events/{event}', [\App\Http\Controllers\Admin\PropertyEventApiController::class, 'destroy'])->name('destroy');
});

// Diagnostic Test Route (Yalıhan Bekçi)
Route::get('/test-minimal', function () {
    return view('admin.test-minimal');
})->name('test-minimal');

// ✅ UPS Governance Routes (Phase M)
Route::prefix('admin/ups')->name('admin.ups.')->middleware(['web', 'auth', 'admin', 'role:admin', 'verified', 'throttle:30,1'])->group(function () {
    Route::get('/governance', fn() => redirect()->route('admin.governance.feature-health'))->name('governance.index');
    Route::post('/governance/generate-proposals', fn() => redirect()->route('admin.governance.feature-health.generate-proposals'))->name('governance.generate-proposals');
    // Route::get('/advanced', [\App\Http\Controllers\Admin\UpsTemplateManagerController::class, 'advanced'])->name('advanced');

    // ❌ DEPRECATED (2026-01-25): UPS Feature Manager - Consolidated into Property Hub
    // ✅ REDIRECT: All /ups/features/* → /property-hub/features/*
    Route::prefix('features')->name('features.')->group(function () {
        Route::get('/dependencies', fn() => redirect()->route('admin.property-hub.features.index'))->name('dependencies');
        Route::get('/', fn() => redirect()->route('admin.property-hub.features.index'))->name('index');
        Route::get('/create', fn() => redirect()->route('admin.property-hub.features.create'))->name('create');
        Route::any('/{feature}', fn($feature) => redirect()->route('admin.property-hub.features.edit', $feature))->name('edit');
    });

    // ✅ UPS Feature Packs (Phase L+1)
    Route::prefix('feature-packs')->name('feature-packs.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\UpsFeaturePackController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\UpsFeaturePackController::class, 'store'])->name('store');
        Route::put('/{pack}', [\App\Http\Controllers\Admin\UpsFeaturePackController::class, 'update'])->name('update');
        // Route::patch('/{pack}/durum', [\App\Http\Controllers\Admin\UpsFeaturePackController::class, 'toggleDurum'])->name('durum');
        Route::post('/{pack}/features', [\App\Http\Controllers\Admin\UpsFeaturePackController::class, 'addFeature'])->name('add-feature');
        Route::delete('/{pack}/features/{feature}', [\App\Http\Controllers\Admin\UpsFeaturePackController::class, 'removeFeature'])->name('remove-feature');
        Route::post('/preview', [\App\Http\Controllers\Admin\UpsFeaturePackController::class, 'preview'])->name('preview');
        Route::post('/apply', [\App\Http\Controllers\Admin\UpsFeaturePackController::class, 'apply'])->name('apply');
    });

    // UPS Template Manager — SAB PURGE: UpsTemplateManagerController kaldırıldı, TemplateController kullanılıyor
    // ✅ UPS Versioning (Phase N)
    Route::prefix('versions')->name('versions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\UpsVersionController::class, 'index'])->name('index');
        Route::get('/history', [\App\Http\Controllers\Admin\UpsVersionController::class, 'history'])->name('history');
        Route::post('/{version}/rollback', [\App\Http\Controllers\Admin\UpsVersionController::class, 'rollback'])->name('rollback');
    });

    // ✅ Phase 8.0: Marketing Asset Templates (Template Editor & Preview)
    Route::prefix('marketing')->name('marketing.')->group(function () {
        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'index'])->name('index');
            Route::get('/edit', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'edit'])->name('edit');
            Route::post('/', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'store'])->name('store');
            Route::put('/', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'update'])->name('update');
            Route::delete('/', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'destroy'])->name('destroy');
            Route::post('/preview', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'preview'])->name('preview');
        });

        // ✨ Ilan-specific asset generation
        Route::prefix('assets')->name('assets.')->group(function () {
            Route::post('/ilanlar/{ilan}/generate', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'generateForListing'])->name('generate');
            Route::get('/ilanlar/{ilan}', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'getListingAssets'])->name('listing');
            Route::delete('/ilanlar/{ilan}', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'deleteListingAssets'])->name('delete');
        });
    });

    // ✅ UPS Policies (Phase L+3)
    Route::get('/policies', [\App\Http\Controllers\Admin\UpsPolicyController::class, 'index'])->name('policies');

    // ✅ UPS Packs (Phase L+1 Alias)
    // ✅ UPS Packs (Phase L+1 Alias) & Feature Management
    Route::group(['prefix' => 'packs', 'as' => 'packs.'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\UpsPackController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\UpsPackController::class, 'store'])->name('store');
        Route::get('/{pack}/features', [\App\Http\Controllers\Admin\UpsPackController::class, 'getFeatures'])->name('features.get');
        Route::post('/{pack}/features', [\App\Http\Controllers\Admin\UpsPackController::class, 'updateFeatures'])->name('features.update');
        Route::delete('/{pack}', [\App\Http\Controllers\Admin\UpsPackController::class, 'destroy'])->name('destroy');
    });

    // ✅ UPS Audit Log (Phase N)
    Route::get('/audit-log', [\App\Http\Controllers\Admin\UPS\AuditLogController::class, 'index'])->name('audit-log');
    Route::get('/audit-log/{auditLog}', [\App\Http\Controllers\Admin\UPS\AuditLogController::class, 'show'])->name('audit-log.show');
    Route::delete('/audit-log/{auditLog}', [\App\Http\Controllers\Admin\UPS\AuditLogController::class, 'destroy'])->name('audit-log.destroy');
    Route::post('/audit-log/export', [\App\Http\Controllers\Admin\UPS\AuditLogController::class, 'export'])->name('audit-log.export');
    Route::post('/audit-log/cleanup', [\App\Http\Controllers\Admin\UPS\AuditLogController::class, 'cleanup'])->name('audit-log.cleanup');

    // ✅ UPS Health Check (Phase S - System Integrity)
    Route::get('/health', [\App\Http\Controllers\Admin\UpsHealthController::class, 'index'])->name('health');
    Route::post('/health/repair', [\App\Http\Controllers\Admin\UpsHealthController::class, 'repair'])->name('health.repair');

    // ✅ UPS Analytics (Dashboard) - Redirected to Property Hub Analytics
    Route::get('/analytics', fn() => redirect()->route('admin.property-hub.analytics'))->name('analytics');
});

// ✅ UPS Feature Whitelist (Admin CRUD)
Route::middleware(['web', 'auth', 'admin', 'role:admin', 'verified'])
    ->prefix('admin/ups-feature-whitelist')
    ->name('admin.ups-feature-whitelist.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\UpsFeatureWhitelistController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\UpsFeatureWhitelistController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\UpsFeatureWhitelistController::class, 'store'])->name('store');
        Route::get('/{whitelist}/edit', [\App\Http\Controllers\Admin\UpsFeatureWhitelistController::class, 'edit'])->name('edit');
        Route::put('/{whitelist}', [\App\Http\Controllers\Admin\UpsFeatureWhitelistController::class, 'update'])->name('update');
        Route::delete('/{whitelist}', [\App\Http\Controllers\Admin\UpsFeatureWhitelistController::class, 'destroy'])->name('destroy');
    });

// ✅ Ilan Calendar / Reservation (Phase P+R)
Route::prefix('admin/ilanlar/{ilan}/calendar')->name('admin.ilanlar.calendar')->middleware(['web', 'auth', 'verified', 'throttle:30,1'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\IlanCalendarController::class, 'index']);
    Route::get('.json', [\App\Http\Controllers\Admin\IlanCalendarController::class, 'json'])->name('.json');
    Route::post('/', [\App\Http\Controllers\Admin\IlanCalendarController::class, 'store'])->name('.store');
    Route::post('/close', [\App\Http\Controllers\Admin\IlanCalendarController::class, 'close'])->name('.close');
    Route::post('/{reservation}/cancel', [\App\Http\Controllers\Admin\IlanCalendarController::class, 'cancel'])->name('.cancel');
    // Phase V: Activity feed actions
    Route::post('/{reservation}/confirm', [\App\Http\Controllers\Admin\IlanCalendarController::class, 'confirm'])->name('.confirm');

    // ✅ Calendar Feed Admin (Phase Q)
    Route::get('/feed', [\App\Http\Controllers\Admin\IlanCalendarFeedAdminController::class, 'show'])->name('.feed');
    Route::post('/feed', [\App\Http\Controllers\Admin\IlanCalendarFeedAdminController::class, 'create'])->name('.feed.create');
    Route::post('/feed/revoke', [\App\Http\Controllers\Admin\IlanCalendarFeedAdminController::class, 'revoke'])->name('.feed.revoke');
});

// 🎯 PHASE 8 - SPRINT 3: Matching Feedback System (UI Learning Loop)
Route::prefix('/matching/feedback')->name('matching.feedback.')->group(function () {
    Route::post('/', [\App\Http\Controllers\Admin\MatchingFeedbackController::class, 'store'])->name('store');
    Route::get('/history', [\App\Http\Controllers\Admin\MatchingFeedbackController::class, 'history'])->name('history');
    Route::get('/stats', [\App\Http\Controllers\Admin\MatchingFeedbackController::class, 'stats'])->name('stats');
    Route::post('/{id}/mark-result', [\App\Http\Controllers\Admin\MatchingFeedbackController::class, 'markResult'])->name('mark-result');
});

// 📍 Address Management System (Context7)
Route::get('/address-management', [\App\Http\Controllers\Admin\AddressManagementController::class, 'index'])->name('address-management.index');

// Address Management API Routes
Route::prefix('api/v1/admin/address')->name('api.admin.address.')->middleware(['web', 'auth', 'admin', 'role:admin'])->group(function () {
    Route::get('/iller', [\App\Http\Controllers\Admin\AddressManagementController::class, 'getIller'])->name('iller');
    Route::get('/ilceler', [\App\Http\Controllers\Admin\AddressManagementController::class, 'getIlceler'])->name('ilceler');
    Route::get('/mahalleler', [\App\Http\Controllers\Admin\AddressManagementController::class, 'getMahalleler'])->name('mahalleler');
    Route::post('/update-coordinates', [\App\Http\Controllers\Admin\AddressManagementController::class, 'updateCoordinates'])->name('update-coordinates');
    Route::post('/bulk-sync', [\App\Http\Controllers\Admin\AddressManagementController::class, 'bulkSync'])->name('bulk-sync');
});

// ======================================
// FINANCE MODULE (Phase 5 - FiCore)
// Context7: Commission, transactions, bonuses management
// ======================================
Route::middleware(['auth', 'role:admin'])->prefix('admin/finance')->name('admin.finance.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Admin\FinanceController::class, 'dashboard'])->name('dashboard');

    // Commissions (Hakedişler)
    Route::get('/commissions', [\App\Http\Controllers\Admin\FinanceController::class, 'commissions'])->name('commissions.index');
    Route::post('/commissions/{commission}/approve', [\App\Http\Controllers\Admin\FinanceController::class, 'approveCommission'])->name('commissions.approve');
    Route::post('/commissions/{commission}/pay', [\App\Http\Controllers\Admin\FinanceController::class, 'payCommission'])->name('commissions.pay');

    // Transactions (Tahsilatlar)
    Route::get('/transactions', [\App\Http\Controllers\Admin\FinanceController::class, 'transactions'])->name('transactions.index');
    Route::post('/transactions', [\App\Http\Controllers\Admin\FinanceController::class, 'storeTransaction'])->name('transactions.store');
    Route::post('/transactions/{transaction}/verify', [\App\Http\Controllers\Admin\FinanceController::class, 'verifyTransaction'])->name('transactions.verify');

    // Bonuses (Primler)
    Route::get('/bonuses', [\App\Http\Controllers\Admin\FinanceController::class, 'bonuses'])->name('bonuses.index');
    Route::post('/bonuses/{bonus}/pay', [\App\Http\Controllers\Admin\FinanceController::class, 'payBonus'])->name('bonuses.pay');
    Route::post('/bonuses/calculate-monthly', [\App\Http\Controllers\Admin\FinanceController::class, 'calculateMonthlyBonuses'])->name('bonuses.calculate');

    // Calculators (Income Simulator)
    Route::post('/simulate-commission', [\App\Http\Controllers\Admin\FinanceController::class, 'simulateCommission'])->name('simulate.commission');
    Route::post('/simulate-bonus', [\App\Http\Controllers\Admin\FinanceController::class, 'simulateBonus'])->name('simulate.bonus');
});

// Agent Wallet (Self-Service Finance)
Route::middleware(['auth'])->prefix('admin/my-wallet')->name('admin.wallet.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\WalletController::class, 'index'])->name('index');
    Route::get('/commissions', [\App\Http\Controllers\Admin\WalletController::class, 'commissions'])->name('commissions');
    Route::get('/bonuses', [\App\Http\Controllers\Admin\WalletController::class, 'bonuses'])->name('bonuses');
    Route::get('/performance', [\App\Http\Controllers\Admin\WalletController::class, 'performance'])->name('performance');
});

// ======================================
// DANIŞMAN SELF-SERVICE PROFİL
// Context7: Danışmanların kendi profillerini yönetebilmeleri
// ======================================
Route::middleware(['auth', 'role:danisman'])->prefix('danisman')->name('danisman.')->group(function () {
    Route::get('/profil', [\App\Http\Controllers\Danisman\ProfilController::class, 'edit'])
        ->name('profil.edit');
    Route::put('/profil', [\App\Http\Controllers\Danisman\ProfilController::class, 'update'])
        ->name('profil.update');
    Route::put('/profil/sifre', [\App\Http\Controllers\Danisman\ProfilController::class, 'updatePassword'])
        ->name('profil.password');
});

// Monitoring & Cache Stats
// Context7: System observability endpoints
Route::prefix('/monitoring')->name('monitoring.')->group(function () {
    Route::get('/cache/stats', [\App\Http\Controllers\Admin\CacheStatsController::class, 'index'])->name('cache.stats');
    Route::get('/api/cache/stats', [\App\Http\Controllers\Admin\CacheStatsController::class, 'api'])->name('api.cache.stats');
    Route::get('/health', [\App\Http\Controllers\Admin\HealthController::class, 'dashboard'])->name('health.dashboard');
    Route::get('/api/health', [\App\Http\Controllers\Admin\HealthController::class, 'api'])->name('api.health');
});
