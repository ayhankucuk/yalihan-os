<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AnalyticsDashboardController;
use App\Http\Controllers\Admin\YalihanBekciController;
use App\Http\Controllers\Admin\GovernanceController;
use App\Http\Controllers\Admin\UpsGovernanceController;
use App\Http\Controllers\Admin\DecisionEngineController;
use App\Http\Controllers\Admin\CacheStatsController;
use App\Http\Controllers\Admin\HealthController;
use Illuminate\Support\Facades\Route;

// Main dashboard routes
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/index', [DashboardController::class, 'index'])->name('dashboard.index');
Route::get('/dashboard/stats', [DashboardController::class, 'getDashboardStats'])->name('dashboard.stats');
Route::get('/dashboard/agent', [\App\Http\Controllers\Admin\Dashboard\AgentDashboardController::class, 'index'])->name('dashboard.agent');
Route::get('/dashboard/investor', [\App\Http\Controllers\Admin\InvestorDashboardController::class, 'index'])->name('dashboard.investor');

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
})->name('index');

// Context7 Analytics Dashboard Routes
Route::get('/cortex', [\App\Http\Controllers\Admin\CortexAnalyticsController::class, 'index'])->name('cortex');

Route::prefix('/analytics')->name('analytics.')->group(function () {
    Route::get('/visibility', [\App\Http\Controllers\Admin\VisibilityController::class, 'index'])->name('visibility.index');
    Route::get('/visibility/{id}', [\App\Http\Controllers\Admin\VisibilityController::class, 'show'])->whereNumber('id')->name('visibility.show');
    Route::get('/context7', [AnalyticsDashboardController::class, 'index'])->name('context7');
    Route::get('/ai-governance', [\App\Http\Controllers\Admin\AIGovernanceController::class, 'index'])->name('ai-governance');
    Route::get('/command-center', \App\Http\Livewire\Admin\GovernanceCommandCenter::class)->name('governance.command-center');
    Route::get('/governance-dashboard', \App\Http\Livewire\Admin\GovernanceDashboard::class)->name('governance.dashboard');
});

// Yalihan Bekçi Monitoring Dashboard
Route::prefix('/yalihan-bekci')->name('yalihan-bekci.')->group(function () {
    Route::get('/', [YalihanBekciController::class, 'index'])->name('index');
    Route::get('/live-data', [YalihanBekciController::class, 'liveData'])->name('live-data');
    Route::post('/run-check', [YalihanBekciController::class, 'runCheck'])->name('run-check');
});

// SAB Governance Dashboard (Read-Only Observability)
Route::prefix('/governance')->name('governance.')->group(function () {
    Route::get('/telemetry', \App\Http\Livewire\Admin\GovernanceDashboard::class)->name('telemetry');
    Route::get('/', [GovernanceController::class, 'dashboard'])->name('dashboard');
    Route::get('/feature-health', [UpsGovernanceController::class, 'index'])->name('feature-health');
    Route::post('/feature-health/generate-proposals', [UpsGovernanceController::class, 'generateHealthProposals'])->name('feature-health.generate-proposals');

    // Decision Engine
    Route::get('/review-queue', [DecisionEngineController::class, 'reviewQueue'])->name('review-queue');
    Route::get('/decisions/{decision}', [DecisionEngineController::class, 'show'])->name('decisions.show');
    Route::post('/decisions/{decision}/approve', [DecisionEngineController::class, 'approve'])->name('decisions.approve');
    Route::post('/decisions/{decision}/reject', [DecisionEngineController::class, 'reject'])->name('decisions.reject');
    Route::get('/decision-history', [DecisionEngineController::class, 'history'])->name('decision-history');
    Route::post('/scan', [DecisionEngineController::class, 'scan'])->name('scan');
    Route::post('/decisions/{decision}/rollback', [DecisionEngineController::class, 'rollback'])->name('decisions.rollback');
    Route::post('/decisions/{decision}/suppress', [DecisionEngineController::class, 'suppress'])->name('decisions.suppress');
    Route::post('/decisions/{decision}/override', [DecisionEngineController::class, 'override'])->name('decisions.override');
    Route::get('/suppressions', [DecisionEngineController::class, 'suppressionList'])->name('suppression-list');
    Route::delete('/suppressions/{suppression}', [DecisionEngineController::class, 'removeSuppression'])->name('suppressions.remove');
    Route::get('/intelligence-center', [DecisionEngineController::class, 'intelligenceCenter'])->name('intelligence-center');
    Route::post('/suggestions/{suggestion}/approve', [DecisionEngineController::class, 'approveSuggestion'])->name('suggestions.approve');
    Route::post('/suggestions/{suggestion}/reject', [DecisionEngineController::class, 'rejectSuggestion'])->name('suggestions.reject');
    Route::post('/behavior/toggle-safe-mode', [DecisionEngineController::class, 'toggleSafeMode'])->name('behavior.toggle-safe-mode');
    Route::post('/behavior/update', [DecisionEngineController::class, 'updateBehavior'])->name('behavior.update');
    Route::get('/autonomy', [DecisionEngineController::class, 'autonomyPanel'])->name('autonomy-panel');
    Route::post('/autonomy/level', [DecisionEngineController::class, 'updateAutonomyLevel'])->name('autonomy.update-level');
    Route::post('/autonomy/pause', [DecisionEngineController::class, 'pauseSystem'])->name('autonomy.pause');
    Route::post('/autonomy/resume', [DecisionEngineController::class, 'resumeSystem'])->name('autonomy.resume');
    Route::post('/autonomy/toggle-dry-run', [DecisionEngineController::class, 'toggleDryRun'])->name('autonomy.toggle-dry-run');
    Route::post('/autonomy/budget', [DecisionEngineController::class, 'updateActionBudget'])->name('autonomy.update-budget');
    Route::get('/action-dashboard', [DecisionEngineController::class, 'actionDashboard'])->name('action-dashboard');
    Route::post('/decisions/{decision}/record-result', [DecisionEngineController::class, 'recordResult'])->name('decisions.record-result')->middleware('throttle:20,1');
    Route::post('/decisions/{decision}/feedback', [DecisionEngineController::class, 'addFeedback'])->name('decisions.feedback')->middleware('throttle:20,1');
    Route::post('/decisions/{decision}/simulate', [DecisionEngineController::class, 'simulateAction'])->name('decisions.simulate')->middleware('throttle:10,1');
});

// Monitoring & Cache Stats
Route::prefix('/monitoring')->name('monitoring.')->group(function () {
    Route::get('/cache/stats', [CacheStatsController::class, 'index'])->name('cache.stats');
    Route::get('/api/cache/stats', [CacheStatsController::class, 'api'])->name('api.cache.stats');
    Route::get('/health', [HealthController::class, 'dashboard'])->name('health.dashboard');
    Route::get('/api/health', [HealthController::class, 'api'])->name('api.health');
});
