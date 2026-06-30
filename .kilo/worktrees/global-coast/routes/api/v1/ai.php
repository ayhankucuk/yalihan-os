<?php

use App\Http\Controllers\Admin\IlanKategoriController;
use App\Http\Controllers\Api\AdminAIController;
use App\Http\Controllers\Api\AIContentController;
use App\Http\Controllers\Api\AIController;
use App\Http\Controllers\Api\AiHealthController;
use App\Http\Controllers\Api\IlanAIController;
use App\Http\Controllers\Api\GeminiTemplateController;
use App\Http\Controllers\Api\PlanNotesController;
use App\Http\Controllers\Api\CalendarToolsController;
use App\Http\Controllers\Api\VoiceSearchController;
use App\Http\Controllers\Api\NLPController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Ilan;
use App\Models\AILeadScore;
use App\Http\Controllers\AI\AdvancedAIController;
use App\Http\Controllers\Api\AIChatController;
use App\Http\Controllers\Api\EnvironmentAnalysisController;
use App\Services\AI\SemanticSearchService;
use App\Services\Logging\LogService;
use App\Http\Controllers\Api\AIFrontendAssistantController;

/*
|--------------------------------------------------------------------------
| AI API Routes (v1)
|--------------------------------------------------------------------------
|
| AI-powered API endpoints
| Includes content generation, analysis, and suggestions
|
*/

// Admin AI Routes (Requires Authentication)
Route::prefix('admin/ai')->name('api.ai.admin.')->middleware(['auth', 'admin', 'role:admin', 'ai.cost.guard'])->group(function () {
    // Core AI Operations
    Route::post('/analyze', [AIController::class, 'analyze'])->name('analyze');
    Route::post('/suggest', [AIController::class, 'suggest'])->name('suggest');
    Route::post('/generate', [AIController::class, 'generate'])->name('generate');
    Route::get('/health', [AIController::class, 'healthCheck'])->name('health');
    Route::get('/providers', [AIController::class, 'getProviders'])->name('providers');
    Route::post('/switch-provider', [AIController::class, 'switchProvider'])->name('switch-provider');
    Route::get('/stats', [AIController::class, 'getStats'])->name('stats');
    Route::get('/logs', [AIController::class, 'getLogs'])->name('logs');
    Route::get('/briefing', [AIController::class, 'getBriefing'])->name('briefing');

    // Admin AI Controller
    Route::post('/chat', [AdminAIController::class, 'chat'])->name('chat');
    Route::post('/price/predict', [AdminAIController::class, 'pricePredict'])->name('price.predict');
    Route::post('/suggest-features', [AdminAIController::class, 'suggestFeatures'])->name('suggest-features');
    Route::get('/analytics', [AdminAIController::class, 'analytics'])->name('analytics');

    // Plan Notes Query
    Route::post('/plan-notes/query', [PlanNotesController::class, 'query'])->name('plan-notes.query');

    // Talepler Create - AI Assistant Endpoints
    Route::post('/suggest-price', [AIController::class, 'suggestPrice'])->name('suggest-price');
    Route::post('/find-matches', [AIController::class, 'findMatches'])->name('find-matches');
    Route::post('/generate-description', [AIController::class, 'generateDescription'])->name('generate-description');

    // Voice-to-CRM: Sesli komut ile hızlı kayıt (Context7: C7-VOICE-TO-CRM-2025-11-27)
    Route::post('/voice-to-crm', [AIController::class, 'voiceToCrm'])->name('voice-to-crm');

    // Voice-to-Query: Sesli komut ile arama (Context7: C7-VOICE-TO-QUERY-2026-01-01)
    Route::post('/voice-to-query', [VoiceSearchController::class, 'voiceToQuery'])->name('voice-to-query');

    // Voice-to-Task: Sesli komut ile görev oluşturma (Context7: C7-VOICE-TO-TASK-2026-01-01)
    Route::post('/voice-to-task', [VoiceSearchController::class, 'voiceToTask'])->name('voice-to-task');

    // Opportunity Audio: Fırsat seslendirme (Context7: C7-OPPORTUNITY-AUDIO-2026-01-01)
    Route::get('/opportunity/{id}/audio', [AIController::class, 'generateOpportunityAudio'])
        ->name('opportunity.audio')
        ->where('id', '[0-9]+');
});

// Gemini Template Enrichment API (Context7: Gemini Template Blueprint v1)
Route::prefix('ai/gemini')->name('api.ai.gemini.')->middleware(['auth'])->group(function () {
    Route::post('/template/enrich', [GeminiTemplateController::class, 'enrich'])->name('template.enrich');
});

// AI Feedback API (Context7: C7-AI-FEEDBACK-2025-11-25)
// Danışman geri bildirim sistemi - AI öğrenme döngüsü için
Route::prefix('ai')->name('api.ai.feedback.')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/feedback/{logId}', [AIController::class, 'submitFeedback'])
        ->name('submit')
        ->where('logId', '[0-9]+');
});

// Churn Risk API
Route::get('/ai/churn-risk/{kisiId}', [AIController::class, 'getChurnRisk'])
    ->middleware(['auth:sanctum'])
    ->name('api.ai.churn-risk')
    ->where('kisiId', '[0-9]+');

Route::get('/ai/churn-risk/top/{limit?}', [AIController::class, 'getTopChurnRisks'])
    ->middleware(['auth:sanctum'])
    ->name('api.ai.churn-risk.top')
    ->where('limit', '[0-9]+');

// Negotiation Strategy API (Context7: Pazarlık Stratejisi Analizi)
Route::get('/ai/strategy/{kisiId}', [AIController::class, 'getNegotiationStrategy'])
    ->middleware(['auth:sanctum'])
    ->name('api.ai.strategy')
    ->where('kisiId', '[0-9]+');

// AI Assist Routes for İlan Creation - REMOVED (Phantom)
/*
Route::prefix('admin/ai-assist')->name('api.ai.assist.')->middleware(['ai.cost.guard'])->group(function () {
    Route::post('/auto-categorize', [IlanAIController::class, 'autoDetectCategory'])->name('auto-categorize');
    Route::post('/price-suggest', [IlanAIController::class, 'suggestOptimalPrice'])->name('price-suggest');
    Route::post('/description-generate', [IlanAIController::class, 'generateDescription'])->name('description-generate');
    Route::post('/seo-optimize', [IlanAIController::class, 'optimizeForSEO'])->name('seo-optimize');
    Route::post('/image-analyze', [IlanAIController::class, 'analyzeUploadedImages'])->name('image-analyze');
});
*/

// İlan AI Routes (Context7: C7-ILAN-AI-API-2025-11-30)
Route::prefix('ai')->name('api.ai.')->middleware(['auth'])->group(function () {
    Route::post('/fetch-tkgm', [IlanAIController::class, 'fetchTkgm'])->name('fetch-tkgm');
    Route::post('/calculate-m2-price', [IlanAIController::class, 'calculateM2Price'])->name('calculate-m2-price');
    Route::post('/analyze-construction', [IlanAIController::class, 'analyzeConstruction'])->name('analyze-construction');
    Route::post('/calculate-seasonal-price', [IlanAIController::class, 'calculateSeasonalPrice'])->name('calculate-seasonal-price');
    Route::post('/baslat-video-render/{ilanId}', [AIController::class, 'startVideoRender'])
        ->name('baslat-video-render')
        ->where('ilanId', '[0-9]+');
    Route::get('/video-durumu/{ilanId}', [AIController::class, 'getVideoStatus'])
        ->name('video-durum')
        ->where('ilanId', '[0-9]+');
});

// AI Analysis API
Route::post('/admin/ilan-kategorileri/ai-analysis', [IlanKategoriController::class, 'aiAnalysis'])
    ->middleware(['auth', 'admin', 'role:admin'])
    ->name('api.ai.analysis');

// Public AI Routes (Rate Limited)
Route::prefix('public-ai')->name('api.ai.public.')->group(function () {
    Route::middleware('throttle:10,1')->post('/ilan-arama', [App\Http\Controllers\Api\IlanAIController::class, 'publicSearch'])->name('ilan-arama');
});

// AI Suggestions API (Public - Rate Limited)
Route::prefix('ai')->name('api.ai.')->middleware(['throttle:30,1', 'ai.cost.guard'])->group(function () {
    // AI Health Check
    Route::get('/health', [AiHealthController::class, 'health'])->name('health');

    // Health Check API Endpoints (Context7: C7-HEALTH-CHECK-API-2025-12-01)
    // Monitoring araçları için (Prometheus, Grafana, UptimeRobot)
    Route::prefix('health')->name('health.')->group(function () {
        Route::get('/', [AdvancedAIController::class, 'healthCheck'])->name('check');
        Route::get('/system', [AdvancedAIController::class, 'systemHealthApi'])->name('system');
        Route::get('/queue', [AdvancedAIController::class, 'queueHealth'])->name('queue');
        Route::get('/telegram', [AdvancedAIController::class, 'telegramHealth'])->name('telegram');
    });

/*
    // Dashboard AJAX Endpoints (Dashboard Auto-Refresh)
    Route::get('/system-health', [AdvancedAIController::class, 'systemHealthApi'])->name('system-health');

    // Quick Actions (Dashboard Interactive Controls)
    Route::post('/quick-action', [AdvancedAIController::class, 'quickAction'])->name('quick-action');
*/

    /*
    // QA Karantina: Üretim seviyesinde sahte statik veri döndürdüğü ve kullanımı olmadığı
    // tespit edildiği için tamamen izole edilmiştir.
    Route::post('/analyze-listing', function (Request $request) { ... })->name('analyze-listing');
    Route::post('/suggestions', function (Request $request) { ... })->name('suggestions');
    */

    // AI Assistant Query (Context7: C7-AI-ASSISTANT-2026-03-06)
    Route::post('/assistant/query', [AIFrontendAssistantController::class, 'query'])->name('assistant.query');

    // AI Opportunities (Phase 18 MVP: AI Fırsat Avcısı / Opportunity Inbox)
    Route::get('/opportunities', [\App\Http\Controllers\Api\AIOpportunityController::class, 'index'])->name('opportunities');
    Route::get('/opportunities/{id}', [\App\Http\Controllers\Api\AIOpportunityController::class, 'show'])->name('opportunities.show')->where('id', '[0-9]+');

    // AI Buyer Match Engine (SAB v16.4)
    Route::get('/buyer-matches/{ilan}', [\App\Http\Controllers\Api\V1\AIBuyerMatchController::class, 'show'])->name('buyer-matches.show');
    Route::get('/buyer-matches/{ilan}/history', [\App\Http\Controllers\Api\V1\AIBuyerMatchController::class, 'history'])->name('buyer-matches.history');

    // AI Deal Predictor Engine (SAB v16.5)
    Route::get('/deal-predictor', [\App\Http\Controllers\Api\V1\AIDealPredictorController::class, 'predict'])->name('deal-predictor.predict');
});

// AI Content Generation API Routes - Admin Only
Route::prefix('ai')->name('api.ai.content.')->middleware(['auth:sanctum', 'throttle:60,1', 'ai.cost.guard'])->group(function () {
    Route::post('/generate-titles', [AIContentController::class, 'generateTitles'])->name('generate-titles');
    Route::post('/generate-description', [AIContentController::class, 'generateDescription'])->name('generate-description');
    Route::post('/generate-features', [AIContentController::class, 'generateFeatures'])->name('generate-features');
    Route::post('/generate-seo', [AIContentController::class, 'generateSEO'])->name('generate-seo');
// Route::get('/durum', [AIContentController::class, 'getStatus'])->name('durum');
});

/*
|--------------------------------------------------------------------------
| AI Description Pipeline Routes (Sprint 3.4.5)
|--------------------------------------------------------------------------
|
| Pipeline: ContextBuilder → LLM → Draft → Owner Review → Accept → Persist
|
| Endpoints:
|  POST   /api/v1/ai/description/generate                  - Generate draft
|  GET    /api/v1/ai/description/ilan/{id}/latest        - Get latest draft
|  GET    /api/v1/ai/description/ilan/{id}/history        - Get draft history
|  GET    /api/v1/ai/description/{id}                     - Get single draft
|  POST   /api/v1/ai/description/{id}/approve             - Owner approve + apply
|  POST   /api/v1/ai/description/{id}/reject             - Owner reject
|
*/
Route::prefix('ai/description')->name('api.ai.description.')->middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
    // Generate draft
    Route::post('/generate', [\App\Http\Controllers\Api\AIDescriptionController::class, 'generate'])->name('generate');

    // Get drafts for listing
    Route::get('/ilan/{ilanId}/latest', [\App\Http\Controllers\Api\AIDescriptionController::class, 'getLatest'])->name('ilan.latest');
    Route::get('/ilan/{ilanId}/history', [\App\Http\Controllers\Api\AIDescriptionController::class, 'getHistory'])->name('ilan.history');

    // Single draft operations
    Route::get('/{id}', [\App\Http\Controllers\Api\AIDescriptionController::class, 'show'])->name('show');
    Route::post('/{id}/approve', [\App\Http\Controllers\Api\AIDescriptionController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [\App\Http\Controllers\Api\AIDescriptionController::class, 'reject'])->name('reject');
});

/*
|--------------------------------------------------------------------------
| Environment Analysis API Routes (Context7: C7-ENV-API-2025-12-23)
|--------------------------------------------------------------------------
|
| POI (Points of Interest) ve çevre analizi endpoints
| Tüm gayrimenkul türleri için ortak servis
|
| Rate Limit: 120 requests/minute per IP
| Auth: Optional (unauthenticated requests supported)
| Response Format: Context7 standard (success/data/timestamp/meta)
|
| Endpoints:
| - GET  /api/v1/environment/pois?lat={lat}&lng={lng}&radius={radius}&types={types}
|   Yakındaki POI'ları getirir (DB cache + Overpass API fallback)
|
| - GET  /api/v1/environment/analyze?lat={lat}&lng={lng}&radius={radius}
|   AI-powered çevre analizi (scores, insights, recommendations)
|
| - GET  /api/v1/environment/category/{category}?lat={lat}&lng={lng}&radius={radius}
|   Belirtilen kategori POI'larını filtreleyin
|
| - POST /api/v1/environment/value-prediction
|   Location quality'ye göre fiyat tahmini
|
| Cache Strategy:
|   Frontend: Debounce 1000ms (radius/filter changes)
|   Backend: Cache key = "poi_{lat}_{lng}_{radius}_{types}", TTL = 3600s
|   Hit source: "database" (local) or "api" (Overpass)
|
| Error Handling:
|   - 400: Validation error (invalid lat/lng/radius)
|   - 429: Rate limit exceeded (120 req/min)
|   - 500: Server error (logged)
|
*/
Route::prefix('environment')->name('api.environment.')->middleware(['throttle:120,1'])->group(function () {
    Route::get('/analyze', [EnvironmentAnalysisController::class, 'analyze'])
        ->name('analyze');
    Route::get('/category/{category}', [EnvironmentAnalysisController::class, 'analyzeCategory'])
        ->name('category');
    Route::post('/value-prediction', [EnvironmentAnalysisController::class, 'predictLocationValue'])
        ->name('value-prediction');
    Route::get('/pois', [EnvironmentAnalysisController::class, 'getPOIs'])
        ->name('pois');
});

// Calendar Tools (Availability & Refund) - Sanctum + Throttle
Route::prefix('ai/calendar')->name('api.ai.calendar.')->middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
    Route::post('/check-availability', [CalendarToolsController::class, 'checkAvailability'])->name('check-availability');
    Route::post('/calculate-refund', [CalendarToolsController::class, 'calculateRefund'])->name('calculate-refund');
});

// AI Chat Endpoints - REMOVED (Ghost routes - methods missing in AIChatController)
/*
Route::prefix('chat')->name('api.chat.')->middleware('throttle:30,1')->group(function () {
    Route::post('/message', [AIChatController::class, 'chat'])->name('message');
    Route::post('/generate-description', [AIChatController::class, 'generateDescription'])->name('generate-description');
    Route::post('/suggest-tags', [AIChatController::class, 'suggestTags'])->name('suggest-tags');
    Route::post('/analyze-demand', [AIChatController::class, 'analyzeDemand'])->name('analyze-demand');
    Route::post('/find-matching-properties', [AIChatController::class, 'findMatchingProperties'])->name('find-matching-properties');
});
*/

// NLP Endpoints (Phase 1 - Core Services)
Route::prefix('nlp')->name('api.nlp.')->middleware('throttle:60,1')->group(function () {
    Route::post('/parse', [NLPController::class, 'parse'])->name('parse');
    Route::post('/classify', [NLPController::class, 'classify'])->name('classify');
    Route::post('/extract', [NLPController::class, 'extract'])->name('extract');
    Route::post('/sentiment', [NLPController::class, 'sentiment'])->name('sentiment');
    Route::get('/health', [NLPController::class, 'health'])->name('health');
});
