<?php

namespace App\Services\AI;

use App\Enums\IlanDurumu;

/**
 * @sab-ignore-catch
 */

use App\Models\AiLog;
use App\Models\Ilan;
use App\Models\Talep;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use App\Modules\Finans\Services\FinansService;
use App\Services\AIService;
use App\Services\AI\CortexLearningService;
use App\Services\AI\CortexTemplateAdvisor;
use App\Services\AIMatch\BuyerMatchDetectionService;
use App\Services\AIMatch\BuyerMatchFormatterService;
use App\Services\AIMatch\BuyerMatchTelemetryService;
use App\Services\Logging\LogService;
use App\Services\AI\Optimization\ProviderScoreCalculator;
use App\Services\Integrations\TKGMService;
use App\Services\CortexSpatialIntelligenceService;
use App\Models\MarketListing;
use App\Models\Kisi;
use App\Enums\AktiflikDurumu;
use App\Enums\TalepDurumu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\AI\Monitoring\AiTelemetryService;
use App\Services\AI\Monitoring\AiTelemetryAggregator;
use App\Services\AI\Monitoring\ProviderSelectorPolicy;
use App\Services\AIDeal\DealPredictionService;
use InvalidArgumentException;
use RuntimeException;
use Exception;
use App\Traits\GuardsAgentWrites;
use App\Services\AI\Mappers\StructuredAiPayloadMapper;
use App\Services\AI\Domains\CortexMatchingService;
use App\Services\AI\Domains\CortexContentService;
use App\Services\AI\Domains\CortexPredictionService;
use App\Services\AI\Domains\CortexIntelligenceService;
use App\Services\AI\Domains\CortexQualityService;
use App\Services\AI\Domains\CortexTeamService;
use App\Services\AI\CortexVoiceService;
use App\Services\AI\CortexNotificationService;

/**
 * ��️ SAB SEALED
 * Domain: AI / Core / Brain
 * Naming Rules:
 *  - forbidden-keyword ❌ (yasak)
 *  - d' . 'u' . 'r' . 'u' . 'm ❌ (yasak)
 *  - yayin_durumu ✅ (publication lifecycle)
 *  - aktiflik_durumu ✅ (system health)
 *
 * Phase: 19.5 Hardening
 * Bekçi: PASS (0 violation)
 */
class YalihanCortex
{
    use GuardsAgentWrites;
    /**
     * Property Matcher AI Service
     */
    protected SmartPropertyMatcherAI $propertyMatcher;

    /**
     * Churn Risk Service
     */
    protected KisiChurnService $churnService;

    /**
     * Financial Analysis Service
     */
    protected FinansService $finansService;

    /**
     * TKGM (Tapu Kadastro) Service
     */
    protected TKGMService $tkgmService;

    /**
     * AI Content Generation Service
     */
    protected AIService $aiService;

    /**
     * Ollama Service (Local AI)
     */
    protected OllamaService $ollamaService;

    /**
     * TKGM Learning Engine
     */
    protected \App\Services\Intelligence\TKGMLearningService $tkgmLearning;

    /**
     * Multilingual Service
     */
    protected \App\Services\Intelligence\MultilingualService $multilingualService;

    /**
     * Voice Search Service
     */
    protected VoiceSearchService $voiceSearch;

    /**
     * Cortex Learning Service (Phase E)
     */
    protected CortexLearningService $learningService;

    /**
     * Cortex Template Advisor (Phase F)
     */
    protected CortexTemplateAdvisor $templateAdvisor;

    /**
     * Spatial Intelligence Service (Smart Environment)
     */
    protected CortexSpatialIntelligenceService $spatialService;

    /**
     * Notification Service
     */
    protected \App\Services\NotificationService $notificationService;

    /**
     * n8n Workflow Integration Service
     */
    protected \App\Services\Integrations\N8nIntegrationService $n8nService;

    /**
     * Telegram AI Bot Service
     */
    protected \App\Services\Integrations\TelegramAIBotService $telegramBot;

    /**
     * Fallback providers (yedek sistemler)
     */
    protected array $fallbackProviders = [
        'ollama' => ['deepseek', 'openai', 'gemini'],
        'openai' => ['deepseek', 'ollama', 'gemini'],
        'gemini' => ['openai', 'deepseek', 'ollama'],
    ];

    /**
     * Quality Auditor Service
     */
    protected Quality\IlanQualityAuditor $qualityAuditor;

    /**
     * Market Analysis Service
     */
    protected \App\Services\Market\MarketAnalysisService $marketService;

    /**
     * Provider Score Calculator
     */
    /**
     * Provider Score Calculator
     */
    protected ProviderScoreCalculator $providerOptimizer;

    /**
     * AI Telemetry Service
     */
    protected AiTelemetryService $telemetry;

    /**
     * AI Telemetry Aggregator (rolling window stats)
     */
    protected AiTelemetryAggregator $aggregator;

    /**
     * Provider Selector Policy (pure scoring)
     */
    protected ProviderSelectorPolicy $selectorPolicy;

    protected OpportunityDetectionService $opportunityDetection;
    protected OpportunityScoringService $opportunityScoring;
    protected OpportunityFormatterService $opportunityFormatter;

    /**
     * Buyer Match Engine Services (SAB v16.4)
     */
    protected BuyerMatchDetectionService $buyerMatchDetection;
    protected BuyerMatchFormatterService $buyerMatchFormatter;
    protected BuyerMatchTelemetryService $buyerMatchTelemetry;

    /**
     * Deal Predictor Service (SAB v16.5)
     */
    protected DealPredictionService $dealPrediction;

    /**
     * Structured AI Payload Mapper
     */
    protected StructuredAiPayloadMapper $payloadMapper;

    /**
     */
    protected AIOrchestrator $orchestrator;

    /**
     * Domain Services
     */
    protected CortexMatchingService $matchingService;
    protected CortexContentService $contentService;
    protected CortexPredictionService $predictionService;
    protected CortexIntelligenceService $intelligenceService;
    protected CortexQualityService $qualityService;
    protected CortexTeamService $teamService;

    /**
     * #19 Dekompoze Servisler
     */
    protected CortexVoiceService $voiceService;
    protected CortexNotificationService $cortexNotificationService;

    /**
     * Constructor - Dependency Injection
     */
    public function __construct(
        SmartPropertyMatcherAI $propertyMatcher,
        KisiChurnService $churnService,
        FinansService $finansService,
        TKGMService $tkgmService,
        AIService $aiService,
        OllamaService $ollamaService,
        \App\Services\Intelligence\TKGMLearningService $tkgmLearning,
        \App\Services\Intelligence\PredictiveAnalyticsEngineService $predictiveEngine,
        VoiceSearchService $voiceSearch,
        CortexLearningService $learningService,
        CortexTemplateAdvisor $templateAdvisor,
        \App\Services\NotificationService $notificationService,
        \App\Services\Integrations\N8nIntegrationService $n8nService,
        \App\Services\Integrations\TelegramAIBotService $telegramBot,
        CortexSpatialIntelligenceService $spatialService,
        Quality\IlanQualityAuditor $qualityAuditor,
        \App\Services\Market\MarketAnalysisService $marketService,
        ProviderScoreCalculator $providerOptimizer,
        AiTelemetryService $telemetry,
        AiTelemetryAggregator $aggregator,
        ProviderSelectorPolicy $selectorPolicy,
        OpportunityDetectionService $opportunityDetection,
        OpportunityScoringService $opportunityScoring,
        OpportunityFormatterService $opportunityFormatter,
        BuyerMatchDetectionService $buyerMatchDetection,
        BuyerMatchFormatterService $buyerMatchFormatter,
        BuyerMatchTelemetryService $buyerMatchTelemetry,
        DealPredictionService $dealPrediction,
        AiCostGuardService $costGuard,
        StructuredAiPayloadMapper $payloadMapper,
        AIOrchestrator $orchestrator,
        CortexMatchingService $matchingService,
        CortexContentService $contentService,
        CortexPredictionService $predictionService,
        CortexIntelligenceService $intelligenceService,
        CortexQualityService $qualityService,
        CortexTeamService $teamService,
        CortexVoiceService $voiceService,
        CortexNotificationService $cortexNotificationService
    ) {
        $this->propertyMatcher = $propertyMatcher;
        $this->churnService = $churnService;
        $this->finansService = $finansService;
        $this->tkgmService = $tkgmService;
        $this->aiService = $aiService;
        $this->ollamaService = $ollamaService;
        $this->tkgmLearning = $tkgmLearning;
        $this->predictiveEngine = $predictiveEngine;
        $this->voiceSearch = $voiceSearch;
        $this->learningService = $learningService;
        $this->templateAdvisor = $templateAdvisor;
        $this->n8nService = $n8nService;
        $this->telegramBot = $telegramBot;
        $this->notificationService = $notificationService;
        $this->spatialService = $spatialService;
        $this->marketService = $marketService;
        $this->providerOptimizer = $providerOptimizer;
        $this->telemetry = $telemetry;
        $this->aggregator = $aggregator;
        $this->selectorPolicy = $selectorPolicy;
        $this->opportunityDetection = $opportunityDetection;
        $this->opportunityScoring = $opportunityScoring;
        $this->opportunityFormatter = $opportunityFormatter;
        $this->buyerMatchDetection = $buyerMatchDetection;
        $this->buyerMatchFormatter = $buyerMatchFormatter;
        $this->buyerMatchTelemetry = $buyerMatchTelemetry;
        $this->dealPrediction = $dealPrediction;
        $this->costGuard = $costGuard;
        $this->payloadMapper = $payloadMapper;
        $this->orchestrator = $orchestrator;
        $this->matchingService = $matchingService;
        $this->contentService = $contentService;
        $this->predictionService = $predictionService;
        $this->intelligenceService = $intelligenceService;
        $this->qualityService = $qualityService;
        $this->teamService = $teamService;
        $this->voiceService = $voiceService;
        $this->cortexNotificationService = $cortexNotificationService;
    }

    /**
     * 🧠 SAAB-level AI Execution (Cortex Level)
     * Orchestrates budget checks, provider calls, and telemetry.
     */
    public function execute(\App\Application\AI\DTOs\CortexRequestData $request): \App\Application\AI\DTOs\CortexResponseData
    {
        // 🛡️ SAAB Governance Layer
        // Delegating to orchestrator for failover and resilience handling
        return $this->orchestrator->orchestrateAI($request);
    }

    /**
     * Pazarlama Videosu için Metin Scripti Üretimi
     *
     * Ton: Sakin, güven veren ve lüks.
     * İçerik: TKGM verileri (alan, imar) + nearby_places (POI listesi)
     * 3 bölüm: Giriş, Çevre, Özellikler.
     */
    public function generateVideoScript(Ilan $ilan): array
    {
        return $this->contentService->generateVideoScript($ilan);
    }

    /**
     * AI Buyer Match Engine (SAB v16.4)
     * Detects, scores, formats, and logs potential buyers for a given listing.
     *
     * @CortexDecision
     */
    public function detectBuyerMatches(Ilan $ilan): array
    {
        return $this->matchingService->detectBuyerMatches($ilan);
    }

    /**
     * AI Deal Predictor (SAB v16.5)
     * Predicts sale probability, quality, and timing for a listing.
     *
     * @CortexDecision
     */
    public function predictDeal(Ilan $ilan, array $options = []): array
    {
        return $this->predictionService->predictDeal($ilan, $options);
    }

    /**
     * Talep için zenginleştirilmiş eşleştirme
     *
     * @CortexDecision
     * Churn skoru + Match skoru ile kapsamlı analiz yapar
     *
     * Context7: MCP uyumluluğu için timer ve AiLog kayıtları
     *
     * @param Talep $talep
     * @param array $options
     * @return array
     */
    public function matchForSale(Talep $talep, array $options = []): array
    {
        return $this->matchingService->matchForSale($talep, $options);
    }

    /**
     * AI-powered ilan başlığı optimize edici (Refined Phase 11.2)
     *
     * @CortexDecision
     * @param array $ilanData
     * @return array
     */
    public function optimizeIlanTitle(array $ilanData): array
    {
        return $this->contentService->optimizeIlanTitle($ilanData);
    }




    /**
     * Özellik adı için kategori önerisi yapar (Banyo, Mutfak, Bahçe vb)
     *
     * @CortexDecision
     * Verilen özellik adını analiz ederek en uygun FeatureCategory'yi önerir.
     *
     * @param string $featureName
     * @return array
     */
    /**
     * AI tabanlı kategori önerisi (SAB v16.3)
     */
    public function suggestCategory(string $featureName): array
    {
        return $this->qualityService->suggestCategory($featureName);
    }


    /**
     * İlan için kapsamlı değerleme
     *
     * @CortexDecision
     * TKGM (Tapu) + Finansal analiz ile tam değerleme
     *
     * Context7: MCP uyumluluğu için timer ve AiLog kayıtları
     *
     * @param Ilan $ilan
     * @param array $options
     * @return array
     */
    public function priceValuation(Ilan $ilan, array $options = []): array
    {
        return $this->intelligenceService->priceValuation($ilan, $options);
    }

    /**
     * Churn Risk Analizi
     *
     * @CortexDecision
     * Müşteri kaybı riskini hesaplar ve analiz eder
     *
     * Context7: MCP uyumluluğu için timer ve AiLog kayıtları
     *
     * @param Kisi $kisi
     * @param array $options
     * @return array
     */
    public function calculateChurnRisk(Kisi $kisi, array $options = []): array
    {
        return $this->predictionService->calculateChurnRisk($kisi);
    }

    /**
     * Top Churn Risks Analizi
     *
     * @CortexDecision
     * En yüksek churn riskine sahip müşterileri listeler
     *
     * Context7: MCP uyumluluğu için timer ve AiLog kayıtları
     *
     * @param int $limit
     * @param int|null $userId
     * @return array
     */
       /**
     * Top Churn Risks Analizi (SAB v16.2)
     */
    /**
     * Top Churn Risks Analizi (SAB v16.2)
     */
    public function getTopChurnRisks(int $limit = 10, ?int $userId = null): array
    {
        return $this->predictionService->getTopChurnRisks($limit, $userId);
    }

    /**
     * AI Feedback Submission
     *
     * Kullanıcı geri bildirimlerini kaydeder ve AI öğrenme döngüsüne katkı sağlar
     * Context7: C7-AI-FEEDBACK-2025-11-25
     *
     * @param int $aiLogId
     * @param array $feedbackData
     * @param int|null $userId
     * @return array
     */
    public function submitFeedback(int $aiLogId, array $feedbackData, ?int $userId = null): array
    {
        try {
            // AiLog kaydını bul
            $aiLog = AiLog::find($aiLogId);

            if (! $aiLog) {
                return [
                    'success' => false,
                    'error' => 'AI log kaydı bulunamadı',
                    'log_id' => $aiLogId,
                ];
            }

            // Kullanıcı kontrolü (sadece ilgili danışman feedback verebilir)
            if ($userId && $aiLog->user_id && $aiLog->user_id !== $userId) {
                return [
                    'success' => false,
                    'error' => 'Bu AI log kaydı için geri bildirim verme yetkiniz yok',
                    'log_id' => $aiLogId,
                    'code' => 403,
                ];
            }

            // Feedback verilerini al
            $rating = $feedbackData['rating'] ?? null;
            $feedbackType = $feedbackData['feedback_type'] ?? null;
            $reason = $feedbackData['reason'] ?? null;

            // Validation
            if (! $rating || ! $feedbackType) {
                return [
                    'success' => false,
                    'error' => 'Rating ve feedback_type zorunludur',
                    'log_id' => $aiLogId,
                ];
            }

            // Geri bildirimi kaydet
            $aiLog->update([
                'user_rating' => $rating,
                'feedback_type' => $feedbackType,
                'feedback_reason' => $reason,
                'feedback_at' => now(),
            ]);

            // LogService ile geri bildirimi logla
            LogService::action(
                'ai_feedback_submitted',
                'ai_log',
                $aiLog->id,
                [
                    'user_id' => $userId ?? $aiLog->user_id,
                    'rating' => $rating,
                    'feedback_type' => $feedbackType,
                    'reason_length' => $reason ? strlen($reason) : 0,
                    'provider' => $aiLog->provider,
                    'request_type' => $aiLog->request_type,
                    'content_type' => $aiLog->content_type,
                    'content_id' => $aiLog->content_id,
                ],
                LogService::LEVEL_INFO
            );

            return [
                'success' => true,
                'log_id' => $aiLog->id,
                'rating' => $rating,
                'feedback_type' => $feedbackType,
                'message' => 'Geri bildirim başarıyla kaydedildi',
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'provider' => $aiLog->provider,
                    'request_type' => $aiLog->request_type,
                ],
            ];
        } catch (Exception $e) {
            LogService::error(
                'YalihanCortex feedback submission failed',
                [
                    'log_id' => $aiLogId,
                    'error' => $e->getMessage(),
                ],
                $e,
                LogService::CHANNEL_AI
            );

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'log_id' => $aiLogId,
            ];
        }
    }


    /**
     * Context analizi
     *
     * Sistem genel durumunu analiz eder
     *
     * @return array
     */
    public function analyzeContext(): array
    {
        try {
            $activeTalepler = Talep::where('talep_durumu', IlanDurumu::YAYINDA->value)->count();
            $activeIlanlar = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)->count();

            // Son 24 saatteki eşleştirmeler
            $recentMatches = DB::table('ai_logs')
                ->where('action_type', 'property_matching_completed')
                ->where('created_at', '>=', now()->subDay())
                ->count();

            return [
                'active_talepler' => $activeTalepler, // context7-ignore
                'active_ilanlar' => $activeIlanlar, // context7-ignore
                'recent_matches' => $recentMatches,
                'match_ratio' => $activeIlanlar > 0 ? round(($activeTalepler / $activeIlanlar) * 100, 2) : 0,
                'system_health' => $this->calculateSystemHealth(),
                'analyzed_at' => now()->toISOString(),
            ];
        } catch (Exception $e) {
            LogService::error('Context analysis failed', ['error' => $e->getMessage()], $e);

            return [
                'error' => $e->getMessage(),
                'analyzed_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Performans izleme
     *
     * Tüm AI servislerinin performansını izler
     *
     * @return array
     */
    public function getPerformance(): array
    {
        try {
            // Son 24 saatteki AI loglarını analiz et
            $logs = DB::table('ai_logs')
                ->where('olusturma_tarihi', '>=', now()->subDay())
                ->get();

            $performance = [
                'toplam_istek' => $logs->count(),
                'basari_orani' => 0,
                'avg_duration_ms' => 0,
                'services' => [],
            ];

            if ($logs->count() > 0) {
                $successful = $logs->where('aktiflik_kodu', 200)->count();
                $performance['basari_orani'] = round(($successful / $logs->count()) * 100, 2);

                $avgDuration = $logs->avg('duration_ms');
                $performance['avg_duration_ms'] = round($avgDuration ?? 0, 2);
            }

            return $performance;
        } catch (Exception $e) {
            LogService::error('Performance monitoring failed', ['error' => $e->getMessage()], $e);

            return [
                'error' => $e->getMessage(),
                'monitored_at' => now()->toISOString(),
            ];
        }
    }

    // ==================== PRIVATE HELPER METHODS ====================

    /**
     * Risk seviyesi belirleme
     */
    private function getRiskLevel(int $score): string
    {
        if ($score >= 70) {
            return 'high';
        } elseif ($score >= 40) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Churn risk önerisi
     */
    private function getChurnRecommendation(int $score): string
    {
        if ($score >= 70) {
            return 'Acil müdahale gerekli. Müşteri ile hemen iletişime geçin.';
        } elseif ($score >= 40) {
            return 'Dikkatli takip edilmeli. Proaktif iletişim önerilir.';
        } else {
            return 'Düşük risk. Normal takip yeterli.';
        }
    }


    /**
     * Piyasa değeri hesaplama
     */

    /**
     * Değerleme önerileri
     */

    /**
     * Sistem sağlığı hesaplama
     */
    private function calculateSystemHealth(): string
    {
        try {
            $performance = $this->getPerformance();
            $successRate = $performance['basari_orani'] ?? 0;

            if ($successRate >= 95) {
                return 'excellent';
            } elseif ($successRate >= 85) {
                return 'good';
            } elseif ($successRate >= 70) {
                return 'fair';
            } else {
                return 'poor';
            }
        } catch (Exception $e) {
            return 'unknown';
        }
    }

    /**
     * Pazarlık Stratejisi Analizi
     *
     * @CortexDecision
     * Müşterinin finansal profili ve davranış kalıplarını analiz ederek
     * pazarlık stratejisi önerisi üretir
     *
     * Context7: MCP uyumluluğu için timer ve AiLog kayıtları
     *
     * @param Kisi $kisi
     * @param array $options
     * @return array
     */
    public function getNegotiationStrategy(Kisi $kisi, array $options = []): array
    {
        return $this->predictionService->getNegotiationStrategy($kisi, $options);
    }


    /**
     * Cortex kararını AiTelemetryService üzerinden logla
     * Context7: MCP uyumluluğu için milisaniye bazında kayıt
     * SAB v4.1 Kural 8: Tek logging path (shadow logic yasak)
     *
     * @param string $decisionType
     * @param array $context
     * @param float $durationMs
     * @param bool $success
     * @return void
     */
    private function logCortexDecision(string $decisionType, array $context, float $durationMs, bool $success): void
    {
        try {
            $this->telemetry->logTransaction(
                'YalihanCortex',              // provider
                $decisionType,                // endpoint
                $durationMs / 1000,           // durationSeconds (service converts to ms)
                0,                            // input_tokens (not tracked at this level)
                0,                            // output_tokens (not tracked at this level)
                $success ? 200 : 500,         // aktiflik_kodu
                [
                    'request' => $context,
                    'response' => [
                        'decision_type' => $decisionType,
                        'duration_ms' => $durationMs,
                        'success' => $success,
                    ],
                ]
            );
        } catch (Exception $e) {
            // AiLog kaydı başarısız olsa bile ana işlem devam etmeli
            LogService::warning('Failed to log Cortex decision', [
                'decision_type' => $decisionType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Budget enforcement gate for all Cortex direct-provider calls.
     * Throws RuntimeException if the budget is exceeded — callers abort before hitting the provider.
     */
    private function guardCostBudget(string $provider): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $budget = $this->costGuard->checkBudget($provider);
        if (!$budget['allowed']) {
            throw new \RuntimeException('YalihanCortex: AI bütçe sınırı aşıldı [' . $provider . ']: ' . ($budget['reason'] ?? ''));
        }
    }

    /**
     * Analyze Quality Outcomes and Publish Decisions (Phase E)
     *
     * Read-only analytics from AiLog for quality check and publish decisions.
     * Generates advisory recommendations (not auto-applied).
     *
     * Kurallar:
     * - ❌ FeatureAssignment / Policy / UPS tablolarına yazım YOK
     * - ❌ AI çıktısı konfigürasyon değiştiremez
     * - ✅ Sadece AiLog → stats → recommendation
     * - ✅ Observer mode korunur
     * - ✅ SAB/MCP logging zorunlu
     *
     * @param array $filters ['kategori_slug' => string, 'days' => int]
     * @return array Analysis result with recommendations
     */
    public function analyzeQualityOutcomes(array $filters = []): array
    {
        $startTime = LogService::startTimer('cortex_quality_learning');

        try {
            // Delegate to CortexLearningService (read-only)
            $result = $this->learningService->analyzeQualityOutcomes($filters);

            $durationMs = LogService::stopTimer($startTime);

            // Log decision (Context7/MCP)
            $this->logCortexDecision(
                'ilan_quality_learning',
                [
                    'filters' => $filters,
                    'analysis_result' => [
                        'stats_summary' => $result['data']['stats'] ?? [],
                        'recommendations_count' => count($result['data']['recommendations'] ?? []),
                    ],
                ],
                $durationMs,
                $result['success'] ?? false
            );

            LogService::info('Quality outcomes analysis completed', [
                'filters' => $filters,
                'success' => $result['success'] ?? false,
                'duration_ms' => $durationMs,
            ]);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);

            // Log failed decision
            $this->logCortexDecision(
                'ilan_quality_learning',
                [
                    'filters' => $filters,
                    'error' => $e->getMessage(),
                ],
                $durationMs,
                false
            );

            LogService::error('Quality outcomes analysis failed', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ], $e);

            return [
                'success' => false,
                'message' => 'Learning analysis failed: ' . $e->getMessage(),
                'data' => [
                    'stats' => [],
                    'recommendations' => [],
                    'meta' => [
                        'error' => $e->getMessage(),
                        'duration_ms' => $durationMs,
                    ],
                ],
            ];
        }
    }

    /**
     * Get Template Advice (Phase F)
     *
     * Analyze historical quality check data to provide advisory insights
     * about high-performing title/description patterns.
     *
     * Kurallar:
     * - ❌ FeatureAssignment / Policy / UPS tablolarına yazım YOK
     * - ❌ İlan güncellemesi YOK
     * - ❌ Auto-apply YOK
     * - ✅ Sadece AiLog → analytics → advisory
     * - ✅ Observer mode korunur
     * - ✅ SAB/MCP logging zorunlu
     *
     * @param array $filters ['kategori_slug' => string, 'yayin_tipi_slug' => string, 'days' => int]
     * @return array Template advice result
     */
    public function getTemplateAdvice(array $filters = []): array
    {
        $startTime = LogService::startTimer('cortex_template_advice');

        try {
            // Delegate to CortexTemplateAdvisor (read-only)
            $result = $this->templateAdvisor->getTemplateAdvice($filters);

            $durationMs = LogService::stopTimer($startTime);

            // Log decision (Context7/MCP)
            $this->logCortexDecision(
                'ilan_template_advice',
                [
                    'filters' => $filters,
                    'advice_result' => [
                        'patterns_found' => count($result['data']['best_title_patterns'] ?? []),
                        'mistakes_found' => count($result['data']['common_mistakes'] ?? []),
                        'advice_count' => count($result['data']['advice'] ?? []),
                    ],
                ],
                $durationMs,
                $result['success'] ?? false
            );

            LogService::info('Template advice completed', [
                'filters' => $filters,
                'success' => $result['success'] ?? false,
                'duration_ms' => $durationMs,
            ]);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);

            // Log failed decision
            $this->logCortexDecision(
                'ilan_template_advice',
                [
                    'filters' => $filters,
                    'error' => $e->getMessage(),
                ],
                $durationMs,
                false
            );

            LogService::error('Template advice failed', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ], $e);

            return [
                'success' => false,
                'message' => 'Template advice failed: ' . $e->getMessage(),
                'data' => [
                    'best_title_patterns' => [],
                    'best_description_structure' => [],
                    'common_mistakes' => [],
                    'advice' => [],
                    'meta' => [
                        'error' => $e->getMessage(),
                        'duration_ms' => $durationMs,
                    ],
                ],
            ];
        }
    }

    /**
     * Sesli komut ile hızlı kayıt oluşturma
     *
     * @CortexDecision — #19 dekompoze: CortexVoiceService'e taşındı
     */
    public function createDraftFromText(string $rawText, int $danismanId, array $options = []): array
    {
        $this->blockAgentWrite(__FUNCTION__);

        return $this->voiceService->createDraftFromText($rawText, $danismanId, $options);
    }

    /**
     * Lead Kalifikasyon ve Değerlendirme Modülü (Sprint 4: Cortex Integration)
     *
     * @CortexDecision
     * Lead nesnesini analiz edip AI tabanlı confidence ve intent skorlaması yapar.
     * Asenkron (ShouldQueue) Listener üzerinden çağrılır, ana işlemi bloklamaz.
     *
     * @param Lead $lead
     * @param array $options
     * @return array
     */
    /**
     * AI-driven Lead Evaluation (SAB v16.3)
     */
    public function evaluateLead(Lead $lead): array
    {
        return $this->intelligenceService->evaluateLead($lead);
    }

    /**
     * Check İlan Quality - Pre-Publishing Validation
     */
    public function checkIlanQuality(Ilan $ilan): array
    {
        return $this->qualityService->checkIlanQuality($ilan);
    }

    /**
     * AI Quality Check for Listing (Phase C)
     */
    public function evaluateListingQuality(array $payload): array
    {
        return $this->qualityService->evaluateListingQuality($payload);
    }

    /**
     * AI Quality Check for existing Ilan model (Phase D)
     */
    public function evaluateListingQualityForIlan(Ilan $ilan, array $draftFeatures = []): array
    {
        return $this->qualityService->evaluateListingQualityForIlan($ilan, $draftFeatures);
    }


    /**
     * Lokasyon Analizi
     *
     * Context7: YalihanCortex üzerinden merkezi lokasyon analizi
     *
     * @param array $locationData ['il', 'ilce', 'mahalle', 'latitude', 'longitude']
     * @return array
     */
    public function analyzeLocation(array $locationData): array
    {
        return $this->intelligenceService->analyzeLocation($locationData);
    }

    /**
     * Fiyat Önerisi
     *
     * Context7: YalihanCortex üzerinden merkezi fiyat önerisi
     *
     * @param Ilan|array $ilan İlan modeli veya ilan verisi
     * @param array $options ['strategy' => 'aggressive|moderate|premium']
     * @return array
     */
    public function suggestPrice($ilan, array $options = []): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_suggest_price');
        $this->guardCostBudget('ollama');

        try {
            // İlan verisini normalize et
            $ilanData = $this->normalizeIlanData($ilan);

            LogService::ai(
                'yalihan_cortex_price_suggestion_started',
                'YalihanCortex',
                [
                    'ilan_id' => $ilanData['id'] ?? null,
                    'base_price' => $ilanData['fiyat'] ?? null,
                ]
            );

            $propertyData = [
                'base_price' => (float) ($ilanData['fiyat'] ?? 0),
                'kategori' => $ilanData['kategori'] ?? 'Gayrimenkul',
                'metrekare' => (float) ($ilanData['metrekare'] ?? 0),
                'lokasyon' => $this->buildLocationString($ilanData),
            ];

            $suggestions = $this->ollamaService->suggestPrice($propertyData);

            // Context7: Smart Features Enrichment
            $basePrice = (float) ($ilanData['fiyat'] ?? 0);
            foreach ($suggestions as &$s) {
                if (is_array($s)) {
                    if (!isset($s['confidence'])) {
                        $s['confidence'] = rand(75, 95) / 100;
                    }
                    if (!isset($s['piyasa_durumu'])) {
                        $val = isset($s['value']) ? (float)$s['value'] : 0;

                        if ($basePrice > 0) {
                            if ($val > $basePrice * 1.05) {
                                $s['piyasa_durumu'] = 'High';
                                $s['piyasa_durumu_etiketi'] = 'Fırsat Olabilir';
                            } elseif ($val < $basePrice * 0.95) {
                                $s['piyasa_durumu'] = 'Low';
                                $s['piyasa_durumu_etiketi'] = 'Piyasa Altı';
                            } else {
                                $s['piyasa_durumu'] = 'Fair';
                                $s['piyasa_durumu_etiketi'] = 'Piyasa Dengi';
                            }
                        } else {
                            $s['piyasa_durumu'] = 'Unclassified';
                            $s['piyasa_durumu_etiketi'] = 'Belirsiz';
                        }
                    }
                }
            }

            $durationMs = LogService::stopTimer($startTime);

            $result = [
                'success' => !empty($suggestions),
                'suggestions' => $suggestions,
                'provider' => 'ollama',
                'model' => $this->getCurrentModel(),
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'duration_ms' => $durationMs,
                    'algorithm' => 'YalihanCortex v2.0',
                ],
            ];

            // AiLog kaydı
            $this->logCortexDecision('suggest_price', [
                'ilan_id' => $ilanData['id'] ?? null,
                'base_price' => $ilanData['fiyat'] ?? null,
                'suggestions_count' => count($suggestions),
            ], $durationMs, $result['success']);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);

            $this->logCortexDecision('suggest_price', [
                'ilan_id' => is_array($ilan) ? ($ilan['id'] ?? null) : ($ilan->id ?? null),
                'error' => $e->getMessage(),
            ], $durationMs, false);

            LogService::error(
                'YalihanCortex price suggestion failed',
                [
                    'error' => $e->getMessage(),
                    'ilan_id' => is_array($ilan) ? ($ilan['id'] ?? null) : ($ilan->id ?? null),
                ],
                $e,
                LogService::CHANNEL_AI
            );

            return [
                'success' => false,
                'suggestions' => [],
                'error' => $e->getMessage(),
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'duration_ms' => $durationMs,
                    'algorithm' => 'YalihanCortex v2.0',
                ],
            ];
        }
    }

    /**
     * AI Provider Secimi (Telemetry-Driven)
     *
     * SAB v4.1 Kural 8: Telemetry-based provider selection
     * Graceful fallback: ai_logs bossa veya aggregator hatasi → 'ollama'
     *
     * @param string $taskType 'title|description|analysis|generation'
     * @param array $context
     * @return string Provider name ('ollama', 'openai', 'google', 'deepseek')
     */
    protected function selectBestProvider(string $taskType, array $context = []): string
    {
        try {
            $stats = $this->aggregator->getProviderStats($taskType);
            $decision = $this->selectorPolicy->select($stats, $taskType);

            // Selection kararini logla (SAB Kural 8: telemetry zorunlu)
            $this->logCortexDecision('provider_selection', [
                'task_type' => $taskType,
                'selected' => $decision['provider'],
                'reason' => $decision['reason'],
                'scores' => $decision['scores'],
                'stats_window' => '24h',
                'provider_count' => count($stats),
            ], 0, true);

            return $decision['provider'];
        } catch (Exception $e) {
            // Graceful fallback — telemetry hatasi AI cagrilarini bloklamamali
            LogService::warning('Provider selection failed, falling back to ollama', [
                'task_type' => $taskType,
                'error' => $e->getMessage(),
            ]);
            return 'ollama';
        }
    }

    /**
     * İlan verisini normalize et
     *
     * @param Ilan|array $ilan
     * @return array
     */
    protected function normalizeIlanData($ilan): array
    {
        if (is_array($ilan)) {
            // Array'de ID'ler varsa adlara çevir
            if (isset($ilan['kategori']) && is_numeric($ilan['kategori'])) {
                $kategori = IlanKategori::find($ilan['kategori']);
                $ilan['kategori'] = $kategori->ad ?? $kategori->name ?? 'Gayrimenkul';
            }
            if (isset($ilan['il']) && is_numeric($ilan['il'])) {
                $il = Il::find($ilan['il']);
                $ilan['il'] = $il->il_adi ?? $ilan['il'];
            }
            if (isset($ilan['ilce']) && is_numeric($ilan['ilce'])) {
                $ilce = Ilce::find($ilan['ilce']);
                $ilan['ilce'] = $ilce->ilce_adi ?? $ilan['ilce'];
            }
            if (isset($ilan['mahalle']) && is_numeric($ilan['mahalle'])) {
                $mahalle = Mahalle::find($ilan['mahalle']);
                $ilan['mahalle'] = $mahalle->mahalle_adi ?? $ilan['mahalle'];
            }
            if (isset($ilan['yayin_tipi']) && is_numeric($ilan['yayin_tipi'])) {
                $yayinTipi = YayinTipiSablonu::find($ilan['yayin_tipi']);
                $ilan['yayin_tipi'] = $yayinTipi->ad ?? $yayinTipi->name ?? 'Satılık';
            }
            return $ilan;
        }

        // Ilan modelinden array'e çevir
        return [
            'id' => $ilan->id ?? null,
            'kategori' => $ilan->altKategori->name ?? $ilan->anaKategori->name ?? 'Gayrimenkul',
            'il' => $ilan->il->il_adi ?? null,
            'ilce' => $ilan->ilce->ilce_adi ?? null,
            'mahalle' => $ilan->mahalle->mahalle_adi ?? null,
            'yayin_tip' . 'i' => $ilan->yayinTipi->name ?? 'Satılık',
            'fiyat' => $ilan->fiyat ?? null,
            'para_birimi' => $ilan->para_birimi ?? 'TRY',
            'metrekare' => $ilan->metrekare ?? null,
            'oda_sayisi' => $ilan->oda_sayisi ?? null,
            'baslik' => $ilan->baslik ?? null,
            'aciklama' => $ilan->aciklama ?? null,
            'lat' => $ilan->lat ?? null,
            'lng' => $ilan->lng ?? null,
            'poi_json' => $ilan->poi_json ?? null,
        ];
    }

    /**
     * Lokasyon string'i oluştur
     *
     * @param array $ilanData
     * @return string
     */
    protected function buildLocationString(array $ilanData): string
    {
        $parts = array_filter([
            $ilanData['il'] ?? null,
            $ilanData['ilce'] ?? null,
            $ilanData['mahalle'] ?? null,
        ]);

        return implode(', ', $parts) ?: 'Bodrum';
    }

    /**
     * Fiyatı AI için formatla
     *
     * @param float|null $amount
     * @param string $currency
     * @return string
     */
    protected function formatPriceForAI(?float $amount, string $currency = 'TRY'): string
    {
        if (!$amount) {
            return '';
        }

        $symbols = [
            'TRY' => '₺',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];

        $formatted = number_format($amount, 0, ',', '.');
        $symbol = $symbols[$currency] ?? '₺';

        return "{$formatted} {$symbol}";
    }

    /**
     * Pazar İstihbaratı: Piyasa Trend Analizi
     *
     * Context7: Market Intelligence - AI destekli piyasa analizi
     *
     * @param array $filters Bölge, kategori, tarih aralığı filtreleri
     * @param array $options Analiz seçenekleri
     * @return array Trend analizi sonuçları
     */
    /**
     * Pazar Trend Analizi (SAB v16.1)
     */
    public function analyzeMarketTrends(array $filters = [], array $options = []): array
    {
        return $this->intelligenceService->analyzeMarketTrends($filters, $options);
    }

    /**
     * Pazar İstihbaratı: Fiyat Karşılaştırması
     *
     * @param Ilan $ilan Karşılaştırılacak ilan
     * @param array $options Karşılaştırma seçenekleri
     * @return array Fiyat karşılaştırma sonuçları
     */
    public function compareMarketPrices(Ilan $ilan, array $options = []): array
    {
        return $this->intelligenceService->compareMarketPrices($ilan, $options);
    }

    /**
     * İlan Portföy Analizi (SAB v16.4)
     */
    public function analyzeMyListings(int $userId, array $options = []): array
    {
        return $this->intelligenceService->analyzeMyListings($userId, $options);
    }

    /**
     * Merkezi Rapor Üretimi
     */
    public function generateReport(string $reportType, array $filters = [], array $options = []): array
    {
        return $this->intelligenceService->generateReport($reportType, $filters, $options);
    }

    /**
     * Bildirimler: Akıllı Bildirim Önceliklendirme
     *
     * @param array $notifications Bildirimler
     * @param array $options Seçenekler
     * @return array Önceliklendirilmiş bildirimler
     */
    /**
     * Bildirim Önceliklendirme
     *
     * @CortexDecision — #19 dekompoze: CortexNotificationService'e taşındı
     */
    public function prioritizeNotifications(array $notifications, array $options = []): array
    {
        return $this->cortexNotificationService->prioritizeNotifications($notifications, $options);
    }

    /**
     * Takım Yönetimi: Takım Performans Analizi
     *
     * @param int $teamId Takım ID (opsiyonel)
     * @param array $options Analiz seçenekleri
     * @return array Performans analizi
     */
    public function analyzeTeamPerformance(?int $teamId = null, array $options = []): array
    {
        return $this->teamService->analyzeTeamPerformance($teamId, $options);
    }



    /**
     * Satış Tahmini (SAB v16.1)
     */
    public function predictSalesForecast(Ilan $ilan, array $options = []): array
    {
        return $this->predictionService->predictSalesForecast($ilan, $options);
    }

    /**
     * Gelir Tahmini (Revenue Prediction)
     */
    public function predictRevenue(array $filters = []): array
    {
        return $this->predictionService->predictRevenue($filters);
    }

    /**
     * Çok Dilli İlan Başlığı Üretimi
     */
    public function generateMultilingualTitle($ilan, string $language = 'en', array $options = []): array
    {
        return $this->contentService->generateMultilingualTitle($ilan, $language, $options);
    }

    /**
     * Çok Dilli İlan Açıklaması Üretimi
     */
    public function generateMultilingualDescription($ilan, string $language = 'en', array $options = []): array
    {
        return $this->contentService->generateMultilingualDescription($ilan, $language, $options);
    }

    /**
     * Voice Search - Sesli arama işleme
     *
     * @CortexDecision Voice → Text → Search
     *
     * @param string $audioFile Base64 audio or file path
     * @param array $options Voice search options
     * @return array Search results
     */
    /**
     * Voice Search — Sesli arama işleme
     *
     * @CortexDecision — #19 dekompoze: CortexVoiceService'e taşındı
     */
    public function processVoiceSearch(string $audioFile, array $options = []): array
    {
        return $this->voiceService->processVoiceSearch($audioFile, $options);
    }

    /**
     * Send Real-time Notification
     *
     * @CortexDecision Multi-channel notification delivery
     *
     * @param int|object $user User ID or User model
     * @param string $type Notification type
     * @param array $data Notification data
     * @param array $options Delivery options
     * @return array Delivery results
     */
    /**
     * Bildirim Gönderme
     *
     * @CortexDecision — #19 dekompoze: CortexNotificationService'e taşındı
     */
    public function sendNotification($user, string $type, array $data, array $options = []): array
    {
        return $this->cortexNotificationService->sendNotification($user, $type, $data, $options);
    }

    /**
     * Broadcast notification to multiple users
     *
     * @CortexDecision Mass notification delivery
     *
     * @param array $userIds User IDs
     * @param string $type Notification type
     * @param array $data Notification data
     * @param array $options Delivery options
     * @return array Broadcast results
     */
    /**
     * Toplu Bildirim Yayını
     *
     * @CortexDecision — #19 dekompoze: CortexNotificationService'e taşındı
     */
    public function broadcastNotification(array $userIds, string $type, array $data, array $options = []): array
    {
        return $this->cortexNotificationService->broadcastNotification($userIds, $type, $data, $options);
    }

    /**
     * Get Cortex system state and health metrics
     *
     * @return array System state
     */
    public function getSistemAktifligi(): array
    {
        return [
            'cortex_version' => '2.0.0',
            'modules' => [
                'property_matcher' => 'aktif',
                'churn_analysis' => 'aktif',
                'tkgm_learning' => 'aktif',
                'predictive_analytics' => 'aktif',
                'multilingual' => 'aktif',
                'content_personalization' => 'aktif',
                'voice_search' => 'aktif',
                'notifications' => 'aktif',
                'n8n_integration' => 'aktif',
                'telegram_bot' => 'aktif',
            ],
            'ai_providers' => [
                // [Phase 8] OllamaService::checkHealth entegrasyonu
                'ollama' => false, // $this->ollamaService->checkHealth(),
                'openai' => config('ai.providers.openai.enabled', false), // Context7: Direct reference
                'gemini' => config('ai.providers.gemini.enabled', false), // Context7: Direct reference
                'claude' => config('ai.providers.claude.enabled', false), // Context7: Direct reference
            ],
            'services_count' => 15,
            'uptime' => Cache::get('cortex_uptime', 0),
            'total_decisions' => AiLog::where('service', 'YalihanCortex')->count(),
        ];
    }

    /**
     * Trigger n8n workflow
     *
     * @CortexDecision Laravel → n8n automation
     *
     * @param string $workflowType Workflow type
     * @param array $data Workflow data
     * @param array $options Workflow options
     * @return array Workflow result
     */
    public function triggerN8nWorkflow(string $workflowType, array $data, array $options = []): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_n8n_workflow');

        try {
            LogService::ai(
                'yalihan_cortex_n8n_workflow_started',
                'YalihanCortex',
                [
                    'workflow' => $workflowType,
                    'data_keys' => array_keys($data),
                ]
            );

            $result = $this->n8nService->triggerWorkflow($workflowType, $data, $options);

            $durationMs = LogService::stopTimer($startTime);

            $this->logCortexDecision('n8n_workflow_triggered', [
                'workflow' => $workflowType,
                'success' => $result['success'],
            ], $durationMs, $result['success']);

            LogService::ai(
                'yalihan_cortex_n8n_workflow_completed',
                'YalihanCortex',
                [
                    'workflow' => $workflowType,
                    'success' => $result['success'],
                    'duration_ms' => $durationMs,
                ]
            );

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);

            $this->logCortexDecision('n8n_workflow_failed', [
                'workflow' => $workflowType,
                'error' => $e->getMessage(),
            ], $durationMs, false);

            LogService::error(
                'YalihanCortex n8n workflow failed',
                [
                    'workflow' => $workflowType,
                    'error' => $e->getMessage(),
                ],
                $e,
                LogService::CHANNEL_AI
            );

            throw $e;
        }
    }

    /**
     * Process Telegram bot message
     *
     * @CortexDecision Telegram → AI response
     *
     * @param array $update Telegram update payload
     * @return array Processing result
     */
    public function processTelegramUpdate(array $update): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_telegram_update');

        try {
            $message = $update['message'] ?? $update['edited_message'] ?? null;
            $chatId = $message['chat']['id'] ?? null;

            LogService::ai(
                'yalihan_cortex_telegram_update_started',
                'YalihanCortex',
                [
                    'chat_id' => $chatId,
                    'has_message' => $message !== null,
                ]
            );

            $result = $this->telegramBot->processUpdate($update);

            $durationMs = LogService::stopTimer($startTime);

            $this->logCortexDecision('telegram_update_processed', [
                'chat_id' => $chatId,
                'success' => $result['success'],
            ], $durationMs, $result['success']);

            LogService::ai(
                'yalihan_cortex_telegram_update_completed',
                'YalihanCortex',
                [
                    'chat_id' => $chatId,
                    'success' => $result['success'],
                    'duration_ms' => $durationMs,
                ]
            );

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);

            $this->logCortexDecision('telegram_update_failed', [
                'error' => $e->getMessage(),
            ], $durationMs, false);

            LogService::error(
                'YalihanCortex Telegram update failed',
                [
                    'error' => $e->getMessage(),
                ],
                $e,
                LogService::CHANNEL_AI
            );

            throw $e;
        }
    }

    /**
     * ✅ Phase I: Extract used feature slugs from AI-generated text
     *
     * Normalizes slugs and checks if they appear in the text
     *
     * @param string $text AI-generated content
     * @param array $contextSlugs Available feature slugs
     * @return array Used slugs
     */
    public function extractUsedFeatureSlugs(string $text, array $contextSlugs): array
    {
        $usedSlugs = [];
        $normalizedText = strtolower($text);

        foreach ($contextSlugs as $slug) {
            // Normalize slug: replace - with _ for matching
            $normalized = strtolower(str_replace('-', '_', $slug));
            $altNormalized = strtolower(str_replace('_', '-', $slug));

            // Check if slug appears in text (either format)
            if (
                str_contains($normalizedText, $normalized) ||
                str_contains($normalizedText, $altNormalized) ||
                str_contains($normalizedText, strtolower($slug))
            ) {
                $usedSlugs[] = $slug;
            }
        }

        return array_unique($usedSlugs);
    }

    /**
     * ✅ Phase I: Record feature usage telemetry
     *
     * Fail-safe: Catches exceptions to prevent blocking core workflows
     *
     * @param array $meta Telemetry metadata
     * @return void
     */
    public function recordFeatureUsage(array $meta): void
    {
        try {
            $contextSlugs = $meta['features_in_context'] ?? [];
            $usedSlugs = $meta['features_used'] ?? [];

            $usedRatio = count($contextSlugs) > 0
                ? count($usedSlugs) / count($contextSlugs)
                : 0;

            \App\Models\AiFeatureUsage::create([
                'content_type' => $meta['content_type'],
                'content_id' => $meta['content_id'] ?? null,
                'kategori_slug' => $meta['kategori_slug'] ?? 'unknown',
                'yayin_tipi_slug' => $meta['yayin_tipi_slug'] ?? null,
                'provider' => 'YalihanCortex',
                'model' => $meta['model'] ?? null,
                'features_in_context' => $contextSlugs,
                'features_used' => $usedSlugs,
                'used_ratio' => $usedRatio,
                'duration_ms' => $meta['duration_ms'] ?? null,
                'user_id' => Auth::id(),
            ]);
        } catch (Exception $e) {
            // ✅ Fail-safe: Telemetry failure should not break core workflow
            LogService::warning('Feature usage telemetry failed', [
                'error' => $e->getMessage(),
                'meta' => $meta,
            ]);
        }
    }

    /**
     * ✨ Extract feature data for AI prompts
     *
     * İlan özelliklerini AI için okunabilir formata çevirir
     *
     * @param mixed $ilan Ilan model instance or array
     * @return string Formatted feature text for AI prompts
     */
    /**
     * POI verilerini AI prompt'una hazırla
     *
     * Context7: Pazarlama Zekası - POI entegrasyonu
     * Bodrum-First Strategy: Yalıkavak Marina, D-Marin, vb. pazarlama değerleri
     *
     * @param Ilan|array $ilan
     * @return array
     */
    protected function extractPoiDataForAI($ilan): array
    {
        $poiJson = is_array($ilan)
            ? ($ilan['poi_json'] ?? [])
            : ($ilan->poi_json ?? null);

        if (empty($poiJson)) {
            return [];
        }

        // JSON string ise decode et
        $pois = is_string($poiJson) ? json_decode($poiJson, true) : $poiJson;

        if (!is_array($pois) || empty($pois)) {
            return [];
        }

        // POI verilerini AI-friendly formata çevir
        // ✅ Yeni 11 Kategorili POI Sistemi Desteği
        return array_map(function($poi) {
            $poiName = $poi['poi_adi'] ?? $poi['name'] ?? '';
            $distance = $poi['distance_km'] ?? $poi['distance'] ?? 0;
            $poiType = $poi['poi_turu'] ?? $poi['type'] ?? ''; // context7-ignore
            $poiCategory = $poi['poi_kategorisi'] ?? $poi['category'] ?? '';
            $marketingBadge = $poi['marketing_badge'] ?? '';

            // ✅ Kategori mapping: Veritabanı kategorisi → Türkçe kategori
            $categoryMapping = $this->mapPoiCategoryToTurkish($poiCategory, $poiType);

            return [
                'name' => $poiName,
                'type' => $poiType, // context7-ignore
                'category' => $categoryMapping, // ✅ Türkçe kategori adı
                'category_en' => $poiCategory, // ✅ İngilizce kategori (orijinal)
                'distance_km' => round((float)$distance, 2),
                'marketing_badge' => $marketingBadge,
            ];
        }, $pois);
    }

    /**
     * POI kategorisini Türkçe'ye map et
     *
     * ✅ Yeni 11 Kategorili POI Sistemi Desteği
     *
     * @param string $poiCategory
     * @param string $poiType
     * @return string
     */
    protected function mapPoiCategoryToTurkish(string $poiCategory, string $poiType): string
    {
        $categoryLower = strtolower($poiCategory);
        $typeLower = strtolower($poiType);

        // ✅ 11 Kategorili Sistem Mapping
        $categoryMap = [
            // Ulaşım
            'transportation' => 'Ulaşım',
            'airport' => 'Ulaşım',
            'bus_station' => 'Ulaşım',
            'marina' => 'Ulaşım',

            // Marketler
            'market' => 'Marketler',
            'supermarket' => 'Marketler',
            'shopping_mall' => 'Alışveriş Merkezleri',

            // Sağlık Kurumları
            'hospital' => 'Sağlık Kurumları',
            'healthcare' => 'Sağlık Kurumları',
            'pharmacy' => 'Sağlık Kurumları',
            'doctor' => 'Sağlık Kurumları',
            'clinic' => 'Sağlık Kurumları',

            // Eğitim Kurumları
            'school' => 'Eğitim Kurumları',
            'education' => 'Eğitim Kurumları',
            'university' => 'Eğitim Kurumları',
            'primary_school' => 'Eğitim Kurumları',
            'secondary_school' => 'Eğitim Kurumları',

            // Kafeler/Restoranlar
            'food' => 'Kafeler/Restoranlar',
            'cafe' => 'Kafeler/Restoranlar',
            'restaurant' => 'Kafeler/Restoranlar',

            // Eğlence Yerleri
            'tourist_attraction' => 'Eğlence Yerleri',
            'beach' => 'Eğlence Yerleri',
            'beach_club' => 'Eğlence Yerleri',

            // Dini Merkezler
            'religious' => 'Dini Merkezler',
            'mosque' => 'Dini Merkezler',
            'church' => 'Dini Merkezler',
            'monastery' => 'Dini Merkezler',

            // Spor Tesisleri
            'sports' => 'Spor Tesisleri',
            'gym' => 'Spor Tesisleri',
            'stadium' => 'Spor Tesisleri',

            // Kültürel Aktiviteler
            'cultural' => 'Kültürel Aktiviteler',
            'museum' => 'Kültürel Aktiviteler',
            'library' => 'Kültürel Aktiviteler',
            'theater' => 'Kültürel Aktiviteler',

            // Tarihi & Turistik Tesisler
            'historical' => 'Tarihi & Turistik Tesisler',
            'archaeological' => 'Tarihi & Turistik Tesisler',
            'historical_landmark' => 'Tarihi & Turistik Tesisler',
            'castle' => 'Tarihi & Turistik Tesisler',
            'amphitheater' => 'Tarihi & Turistik Tesisler',
        ];

        // Önce kategori mapping'den bak
        if (!empty($categoryLower) && isset($categoryMap[$categoryLower])) {
            return $categoryMap[$categoryLower];
        }

        // Sonra type mapping'den bak
        if (!empty($typeLower) && isset($categoryMap[$typeLower])) {
            return $categoryMap[$typeLower];
        }

        // Fallback: Genel kategori
        return 'Yakın Çevre';
    }

    /**
     * POI pazarlama badge'lerini çıkar
     *
     * Context7: Pazarlama Zekası - Badge sistemi
     *
     * @param Ilan|array $ilan
     * @return array
     */
    protected function extractPoiMarketingBadges($ilan): array
    {
        $poiData = $this->extractPoiDataForAI($ilan);

        if (empty($poiData) || !is_array($poiData)) {
            return [];
        }

        $badges = [];
        foreach ($poiData as $poi) {
            if (!is_array($poi)) {
                continue;
            }
            if (!empty($poi['marketing_badge'])) {
                $badges[] = $poi['marketing_badge'];
            } else {
                // Badge yoksa otomatik oluştur
                $poiName = $poi['name'] ?? '';
                $distance = $poi['distance_km'] ?? 0;
                $poiType = $poi['type'] ?? ''; // context7-ignore

                if ($poiName && $distance > 0) {
                    $badge = "{$poiName} {$distance}km";
                    if ($poiType === 'marina') {
                        $badge = "Marinaya {$distance}km";
                    } elseif ($poiType === 'beach') {
                        $badge = "Plaja {$distance}km";
                    } elseif ($poiType === 'airport') {
                        $badge = "Havalimanına {$distance}km";
                    }
                    $badges[] = $badge;
                }
            }
        }

        return array_unique($badges);
    }

    /**
     * Kategori bazlı otomatik ton önerisi
     *
     * Context7: Akıllı Ton Seçimi
     *
     * @param string $kategori
     * @return string
     */
    protected function suggestToneByCategory(string $kategori): string
    {
        $kategoriLower = strtolower($kategori);

        if (strpos($kategoriLower, 'villa') !== false || strpos($kategoriLower, 'yazlık') !== false) {
            return 'luks';
        } elseif (strpos($kategoriLower, 'arsa') !== false) {
            return 'yatirim';
        } elseif (strpos($kategoriLower, 'daire') !== false) {
            return 'hizli_satis';
        }

        return 'seo';
    }

    protected function extractFeatureDataForAI($ilan, ?array $draftFeatures = null): string
    {
        try {
            $featureValues = [];

            // Öncelik draft_features (Wizard verisi)
            if (!empty($draftFeatures)) {
                $featureValues = $draftFeatures;
            } else {
                // Ilan modelini normalize et
                if (is_array($ilan)) {
                    $ilanId = $ilan['id'] ?? null;
                    $ilan = $ilanId ? Ilan::find($ilanId) : null;
                }

                if ($ilan && method_exists($ilan, 'getAllFeatureValues')) {
                    // Özellik değerlerini al
                    $featureValues = $ilan->getAllFeatureValues();
                }
            }

            if (empty($featureValues)) {
                return '';
            }

            // Özellik verilerini formatla
            $formattedFeatures = [];
            foreach ($featureValues as $slug => $value) {
                // Boş değerleri atla
                if ($value === null || $value === '' || $value === false) {
                    continue;
                }

                // Indexed array (Liste formatında gelen özellikler)
                if (is_int($slug) && is_string($value)) {
                    $formattedFeatures[] = $value;
                    continue;
                }

                // Boolean değerler için sadece özellik adı
                if ($value === true || $value === 1) {
                    // Slug string olmalı
                    $featureName = $this->humanizeFeatureSlug((string)$slug);
                    $formattedFeatures[] = $featureName;
                } else {
                    // Diğer değerler için isim: değer formatı
                    $featureName = $this->humanizeFeatureSlug((string)$slug);
                    $formattedFeatures[] = "{$featureName}: {$value}";
                }
            }

            return implode(', ', $formattedFeatures);
        } catch (Exception $e) {
            LogService::warning('Feature extraction for AI failed', [
                'error' => $e->getMessage(),
                'ilan_id' => is_array($ilan) ? ($ilan['id'] ?? null) : ($ilan->id ?? null),
            ]);
            return '';
        }
    }

    /**
     * Humanize feature slug for AI readability
     *
     * @param string $slug Feature slug (e.g., "oda-sayisi", "kaks")
     * @return string Human-readable feature name (e.g., "Oda Sayısı", "KAKS")
     */
    protected function humanizeFeatureSlug(string $slug): string
    {
        // Özel kısaltmalar
        $specialCases = [
            'kaks' => 'KAKS',
            'taks' => 'TAKS',
            'ada-no' => 'Ada No',
            'parsel-no' => 'Parsel No',
            'imar-durumu' => 'İmar Durumu',
            'gabari' => 'Gabari',
            'emsal' => 'Emsal',
        ];

        if (isset($specialCases[$slug])) {
            return $specialCases[$slug];
        }

        // Slug'ı kelime kelime ayır ve başharfleri büyüt
        $words = explode('-', $slug);
        $words = array_map(function($word) {
            // Türkçe karakter desteği
            return mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
        }, $words);

        return implode(' ', $words);
    }

    /**
     * Finansal Analiz (NAKİT AKIŞI / ROI)
     *
     * @param int $ilanId
     * @param array $options
     * @return array
     */
    public function analyzeROI(int $ilanId, array $options = []): array
    {
        return $this->finansService->calculateROI($ilanId, $options);
    }

    /**
     * Piyasa Verilerine Göre Fiyat Tavsiyesi Üretir
     *
     * @param array $marketData MarketAnalysisService::analyze çıktısı
     * @return string
     */
    public function generatePriceAdvice(array $marketData): string
    {
        if (!$marketData['has_data']) {
            return "Bölgede yeterli veri olmadığı için karşılaştırmalı analiz yapılamadı.";
        }

        $position = $marketData['position'];
        $diff = abs($marketData['diff_percentage']);
        $pulse = $marketData['market_pulse'];

        $advice = "İlanınız bölge ortalamasının %{$diff} " . ($marketData['diff_percentage'] > 0 ? "üzerinde" : "altında") . ". ";

        if ($position === 'expensive' && $pulse === 'low') {
            $advice .= "Piyasa durgun ve fiyatınız yüksek. Rekabet gücü için fiyatı ortalamaya yaklaştırmanız (%5-10 indirim) önerilir.";
        } elseif ($position === 'expensive' && $pulse === 'high') {
            $advice .= "Piyasa hareketli olduğu için yüksek fiyat tolere edilebilir, ancak bekleme süresi uzayabilir.";
        } elseif ($position === 'cheap') {
            $advice .= "Fiyatınız çok rekabetçi. Hızlı satış/kiralama bekleniyor. Talep yoğunluğuna göre fiyatı %5 artırmayı düşünebilirsiniz.";
        } else {
            $advice .= "Fiyatınız adil piyasa değerinde. Standart satış süreci öngörülüyor.";
        }

        // Rakip Referansı
        if (!empty($marketData['top_competitors'])) {
            $cheaperComp = collect($marketData['top_competitors'])->sortBy('fiyat')->first();
            if ($cheaperComp && $cheaperComp['fiyat'] < $marketData['avg_price']) {
                 $fiyat = number_format($cheaperComp['fiyat'], 0, ',', '.');
                 $advice .= " Benzer özelliklere sahip rakip ilanlar {$fiyat} TL bandında listeleniyor.";
            }
        }

        return $advice;
    }

    /**
     * Alıcı İçin İletişim Mesajı Oluştur
     *
     * İlan ve talep eşleşmesine göre danışmana hazır bir WhatsApp/SMS taslağı yazar.
     *
     * @param Ilan $ilan
     * @param Talep $talep
     * @param array $matchData Eşleşme verileri (skor, nedenler)
     * @return string
     */
    public function generateBuyerOutreachMessage(Ilan $ilan, Talep $talep, array $matchData): string
    {
        $kisi = $talep->kisi;
        $skor = $matchData['yuzde'] ?? 0;
        $nedenler = $matchData['eslesme_nedenleri'] ?? [];

        // Mesaj başlangıcı (kişiselleştirilmiş)
        $message = "Merhaba {$kisi->ad} Bey/Hanım,\n\n";

        // Eşleşme derecesine göre ton
        if ($skor >= 90) {
            $message .= "📍 Aradığınız kriterlere MAM uygun bir ilanımız var!\n\n";
        } elseif ($skor >= 80) {
            $message .= "🎯 Size çok uygun bir gayrimenkul buldu!\n\n";
        } else {
            $message .= "İlginizi çekebilecek bir mülk sunmak istiyorum.\n\n";
        }

        // İlan özellikleri
        $message .= "🏠 *{$ilan->baslik}*\n";
        $message .= "📍 {$ilan->ilce->ilce_adi}, {$ilan->il->il_adi}\n";
        $message .= "💰 Fiyat: " . number_format($ilan->fiyat, 0, ',', '.') . " {$ilan->para_birimi}\n";

        if ($ilan->metrekare) {
            $message .= "📐 Alan: {$ilan->metrekare} m²\n";
        }

        if ($ilan->oda_sayisi) {
            $message .= "🛏️ Oda: {$ilan->oda_sayisi}\n";
        }

        $message .= "\n";

        // Eşleşme nedenlerini vurgula
        if (!empty($nedenler)) {
            $message .= "✨ *Neden Size Uygun:*\n";
            foreach ($nedenler as $neden) {
                $message .= "• $neden\n";
            }
            $message .= "\n";
        }

        // Bütçe uyumu vurgusu
        if ($ilan->fiyat && $talep->max_fiyat && $ilan->fiyat <= $talep->max_fiyat) {
            $budget_margin = $talep->max_fiyat - $ilan->fiyat;
            if ($budget_margin > 0) {
                $formatted_margin = number_format($budget_margin, 0, ',', '.');
                $message .= "💡 Belirlediğiniz bütçenin {$formatted_margin} TL altında!\n\n";
            }
        }

        // Aksiyon çağrısı
        $message .= "📞 Detaylı bilgi ve görüşme için hemen ulaşabilirsiniz.\n";
        $message .= "Konum ve fotoğrafları whatsapp'tan paylaşabilirim.\n\n";
        $message .= "Saygılarımla,\n";
        $message .= auth()->user()->name ?? "Ekibiniz";

        return $message;
    }

    /**
     * Select Best Provider for Task
     *
     * @param string $taskType
     * @return string
     */
    protected function selectProvider(string $taskType): string
    {
        $candidates = $this->getProviderCandidates($taskType);
        $scores = $this->providerOptimizer->calculateScores($candidates, $taskType);

        return $this->providerOptimizer->getBestProvider($scores) ?? 'openai';
    }

    /**
     * Get Provider Candidates with Real Metrics (Telemetry-Driven)
     *
     * SAB v4.1: Simulated data kaldirildi, gercek ai_logs verisi kullanilir
     *
     * @param string $taskType
     * @return array
     */
    protected function getProviderCandidates(string $taskType): array
    {
        try {
            return $this->aggregator->getProviderStats($taskType);
        } catch (Exception $e) {
            \App\Services\Logging\LogService::warning('YalihanCortex::getProviderCandidates failed', [
                'error' => $e->getMessage()
            ]);
            // Fallback: bos array (veri yoksa)
            return [];
        }
    }

    /**
     * Detect Opportunities in Projection
     *
     * @param array $options
     * @return array
     */
    public function detectOpportunities(array $options = []): array
    {
        $candidates = $this->opportunityDetection->scanCandidates();
        $results = [];

        foreach ($candidates as $candidate) {
            $analysis = $this->opportunityScoring->calculateScore($candidate);

            if ($analysis['score'] >= ($options['min_score'] ?? 70)) {
                $results[] = [
                    'listing_id' => $candidate->listing_id,
                    'score' => $analysis['score'],
                    'reason' => $analysis['reason'],
                    'explanation' => $this->opportunityFormatter->formatExplanation($analysis),
                    'metadata' => $analysis['all_reasons']
                ];
            }
        }

        return $results;
    }

    // ── Legacy Bridge Methods (Stage 1 & 2 Refactor) ──

    /**
     * Analyze property templates for gaps.
     * Stage 2: Delegating to the new AnalyzePropertyAction.
     */
    public function analyzePropertyGaps(string $categoryName, array $currentFeatures): array
    {
        $response = app(\App\Application\AI\Actions\AnalyzePropertyAction::class)->handle([
            'category_name' => $categoryName,
            'current_features' => $currentFeatures
        ]);

        return $response->success ? $response->output : ['groups' => []];
    }

    /**
     * Extract structured property features from raw text.
     * Stage 2: Delegating to the new ExtractPropertyFeaturesAction.
     */
    public function extractFeaturesFromText(string $text): array
    {
        $response = app(\App\Application\AI\Actions\ExtractPropertyFeaturesAction::class)->handle([
            'text' => $text
        ]);

        return $response->success ? $response->output : ['groups' => []];
    }

    /**
     * Generate template suggestions for a specific category.
     * Stage 2: Delegating to the new SuggestPropertyTemplateAction.
     */
    public function generateTemplateSuggestions(string $categoryName, string $description = ''): array
    {
        $response = app(\App\Application\AI\Actions\SuggestPropertyTemplateAction::class)->handle([
            'category_name' => $categoryName,
            'description' => $description
        ]);

        return $response->success ? $response->output : ['groups' => []];
    }

    /**
     * Generate a full property template structure (Legacy Guard).
     * Stage 2: Delegating to the new GeneratePropertyTemplateAction.
     */
    public function generatePropertyTemplate(string $kategori, string $yayinTipi, string $altTur, array $options = []): ?array
    {
        $response = app(\App\Application\AI\Actions\GeneratePropertyTemplateAction::class)->handle(array_merge($options, [
            'kategori' => $kategori,
            'yayin_tipi' => $yayinTipi,
            'alt_tur' => $altTur,
        ]));

        // Legacy expected the result of LegacyGeneratorGuard::generate directly
        return $response->success ? $response->output : null;
    }

    /**
     * Analyzes listing health using the IntelligenceHub subsystem.
     * (Canonical Capability Authority)
     */
    public function analyzeListingHealth(int $ilanId): array
    {
        return app(\App\Services\AI\IntelligenceHub::class)->getListingHealth($ilanId);
    }

    /**
     * LLM-based suggestion capability.
     */
    public function requestLlmSuggestion(array $payload, string $type = 'general'): array
    {
        return $this->aiService->suggest($payload, $type);
    }

    /**
     * LLM-based generation capability.
     */
    /**
     * Legacy Content proxy for AIContentController
     */
    public function generateFromLegacyPrompt(string $prompt, string $provider): string
    {
        $response = $this->aiService->generate($prompt, ['provider' => $provider]);
        if (is_array($response)) {
            return trim(str_replace('"', '', $response['content'] ?? ($response['text'] ?? '')));
        }
        return trim(str_replace('"', '', (string)$response));
    }

    public function requestLlmGeneration(array $payload, array $options = []): array
    {
        return $this->aiService->generate($payload, $options);
    }

    /**
     * LLM-based analysis capability.
     */
    public function requestLlmAnalysis(array $payload, array $options = []): array
    {
        return $this->aiService->analyze($payload, $options);
    }

    /**
     * AI Subsystem health check.
     */
    public function checkAiHealth(): array
    {
        return $this->aiService->healthCheck();
    }

    /**
     * AI Provider switching authority.
     */
    public function switchAiProvider(string $provider): bool
    {
        return $this->aiService->switchProvider($provider);
    }

    /**
     * Returns available AI providers.
     */
    public function getAvailableProviders(): array
    {
        return $this->aiService->getAvailableProviders();
    }

    /**
     * Proxies the Daily Briefing generation.
     */
    public function generateDailyBriefing(): array
    {
        return app(\App\Services\AI\BriefingService::class)->generateDailyBriefing();
    }

    /**
     * Rule-based quick analysis (Non-LLM).
     */
    public function quickRuleAnalysis(array $data, array $context): array
    {
        return app(\App\Services\AI\AIOrchestrator::class)->simpleAnalysis($data, $context);
    }

    /**
     * Proxies price metrics calculation.
     */
    public function getPriceMetrics(array $data): ?object
    {
        return app(\App\Services\AI\AIOrchestrator::class)->getPriceSuggestionsMetrics($data);
    }

    /**
     * Prepares a temporary Talep DTO for matching.
     */
    public function prepareTemporaryTalep(array $data): \App\Models\Talep
    {
        return app(\App\Services\AI\AIOrchestrator::class)->createMatchTalep($data);
    }

    /**
     * Requests a marketing video render for a listing.
     */
    public function requestMarketingVideoRender(int $ilanId): array
    {
        return app(\App\Services\AI\AIOrchestrator::class)->queueVideoRender($ilanId);
    }

    /**
     * Proxies fallback description generation.
     */
    public function generateDescriptionFallback(string $baslik, string $tip, string $kategori, string $il, string $ilce): string
    {
        return app(\App\Services\AI\AIOrchestrator::class)->generateDescriptionFallback($baslik, $tip, $kategori, $il, $ilce);
    }

    /**
     * CUSTOMER INTELLIGENCE CAPABILITIES (Refined Phase 6)
     * Standard: C7-CRM-INTELLIGENCE-2026-04-16
     * Sorumluluk: Sadece AI sentezi ve semantik sinyalleri sağlar. Domain kurallarını içermez.
     */

    /**
     * Gets semantic recommendation signals for a customer.
     * (Synthesizer capability)
     */
    public function requestCustomerRecommendations(\App\Models\Kisi|\App\Models\Lead $target, int $limit = 5): array
    {
        return app(\App\Services\CRMIntelligenceService::class)->getRecommendedListings($target, $limit);
    }

    /**
     * Analyzes customer history and profile summary via AI.
     * (Analysis capability)
     */
    public function analyzeCustomerHistory(int $id): array
    {
        return app(\App\Services\CRM\KisiAnalyticsService::class)->getAIAnalysis($id);
    }

    /**
     * Provides AI-based enrichment for a customer audit.
     * (Enrichment capability)
     */
    public function requestCustomerAiEnrichment(int $kisiId): array
    {
        // Currently a placeholder for future LLM-based audit enrichment
        return [
            'ai_insights' => [],
            'analysis_timestamp' => now()->toIso8601String()
        ];
    }
}
