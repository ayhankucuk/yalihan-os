<?php

namespace App\Services\AI\Domains;

use App\Models\Ilan;
use App\Models\Talep;
use App\Services\AI\Monitoring\AiTelemetryService;
use App\Services\AIMatch\BuyerMatchDetectionService;
use App\Services\AIMatch\BuyerMatchFormatterService;
use App\Services\AIMatch\BuyerMatchTelemetryService;
use App\Services\Logging\LogService;
use App\Services\AI\KisiChurnService;
use App\Services\AI\SmartPropertyMatcherAI;
use Illuminate\Support\Facades\App;
use Exception;

/**
 * 🧠 Cortex Matching Domain Service
 * Responsibility: Handles buyer matching and sale matching logic.
 * Refactored from YalihanCortex God Object.
 */
class CortexMatchingService
{
    protected BuyerMatchDetectionService $buyerMatchDetection;
    protected BuyerMatchFormatterService $buyerMatchFormatter;
    protected BuyerMatchTelemetryService $buyerMatchTelemetry;
    protected AiTelemetryService $telemetry;
    protected KisiChurnService $churnService;
    protected SmartPropertyMatcherAI $propertyMatcher;

    public function __construct(
        BuyerMatchDetectionService $buyerMatchDetection,
        BuyerMatchFormatterService $buyerMatchFormatter,
        BuyerMatchTelemetryService $buyerMatchTelemetry,
        AiTelemetryService $telemetry,
        KisiChurnService $churnService,
        SmartPropertyMatcherAI $propertyMatcher
    ) {
        $this->buyerMatchDetection = $buyerMatchDetection;
        $this->buyerMatchFormatter = $buyerMatchFormatter;
        $this->buyerMatchTelemetry = $buyerMatchTelemetry;
        $this->telemetry = $telemetry;
        $this->churnService = $churnService;
        $this->propertyMatcher = $propertyMatcher;
    }

    /**
     * AI Buyer Match Engine (SAB v16.4)
     */
    public function detectBuyerMatches(Ilan $ilan): array
    {
        $startTime = microtime(true);
        $locale = App::getLocale();

        try {
            // 1. Detection & Scoring
            $matches = $this->buyerMatchDetection->detectForListing($ilan);

            // 2. Formatting (Reasons)
            $formattedMatches = $matches->map(function ($match) {
                $match['reason'] = $this->buyerMatchFormatter->formatReasons($match['score']['breakdown']);
                return $match;
            });

            // 3. Telemetry (Logging & Snapshots)
            $this->buyerMatchTelemetry->logMatches($ilan, $formattedMatches, $locale);

            $topMatch = $formattedMatches->first();
            $this->buyerMatchTelemetry->recordSnapshot(
                $ilan,
                $formattedMatches->count(),
                $topMatch['score']['total'] ?? 0,
                $topMatch['buyer']->id ?? null
            );

            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            $this->logCortexDecision('detect_buyer_matches', [
                'ilan_id' => $ilan->id,
                'match_count' => $formattedMatches->count(),
                'top_score' => $topMatch['score']['total'] ?? 0,
            ], $durationMs, true);

            return [
                'success' => true,
                'listing_id' => $ilan->id,
                'match_count' => $formattedMatches->count(),
                'top_recommendations' => $formattedMatches->take(3)->values()->toArray(),
                'duration_ms' => $durationMs,
                'locale' => $locale,
            ];
        } catch (Exception $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);
            $this->logCortexDecision('detect_buyer_matches', [
                'ilan_id' => $ilan->id,
                'error' => $e->getMessage(),
            ], $durationMs, false);

            return [
                'success' => false,
                'ilan_id' => $ilan->id,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Talep için zenginleştirilmiş eşleştirme
     */
    public function matchForSale(Talep $talep, array $options = []): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_match_for_sale');

        try {
            LogService::ai(
                'yalihan_cortex_match_started',
                'YalihanCortex',
                [
                    'talep_id' => $talep->id,
                    'talep_baslik' => $talep->baslik,
                    'kisi_id' => $talep->kisi_id,
                ]
            );

            $result = [
                'talep_id' => $talep->id,
                'kisi_id' => $talep->kisi_id,
                'churn_analysis' => null,
                'matches' => [],
                'recommendations' => [],
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'algorithm' => 'YalihanCortex v1.0',
                ],
            ];

            // 1. Churn Risk Analizi
            $churnScore = 0;
            if ($talep->kisi_id && $talep->kisi) {
                $churnRisk = $this->churnService->calculateChurnRisk($talep->kisi);
                $churnScore = $churnRisk['score'];
                $result['churn_analysis'] = [
                    'risk_score' => $churnRisk['score'],
                    'risk_level' => $this->getRiskLevel($churnRisk['score']),
                    'breakdown' => $churnRisk['breakdown'],
                    'recommendation' => $this->getChurnRecommendation($churnRisk['score']),
                ];
            }

            // 2. Property Matching
            $matches = $this->propertyMatcher->match($talep);
            $result['matches'] = $this->enrichMatches($matches, $talep, $churnScore);

            // 3. Akıllı Öneriler
            $result['recommendations'] = $this->generateRecommendations($talep, $result);

            // 4. Performans metrikleri
            $durationMs = LogService::stopTimer($startTime);
            $result['metadata']['duration_ms'] = $durationMs;
            $result['metadata']['matches_count'] = count($result['matches']);
            $result['metadata']['success'] = true;

            $this->logCortexDecision('match_for_sale', [
                'talep_id' => $talep->id,
                'kisi_id' => $talep->kisi_id,
                'matches_count' => count($result['matches']),
                'churn_score' => $churnScore,
            ], $durationMs, true);

            LogService::ai(
                'yalihan_cortex_match_completed',
                'YalihanCortex',
                [
                    'talep_id' => $talep->id,
                    'matches_count' => count($result['matches']),
                    'duration_ms' => $durationMs,
                ]
            );

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);

            $this->logCortexDecision('match_for_sale', [
                'talep_id' => $talep->id,
                'kisi_id' => $talep->kisi_id,
                'error' => $e->getMessage(),
            ], $durationMs, false);

            LogService::error(
                'YalihanCortex match failed',
                [
                    'talep_id' => $talep->id,
                    'error' => $e->getMessage(),
                ],
                $e,
                LogService::CHANNEL_AI
            );

            return [
                'talep_id' => $talep->id,
                'success' => false,
                'error' => $e->getMessage(),
                'matches' => [],
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'algorithm' => 'YalihanCortex v1.0',
                    'duration_ms' => $durationMs,
                ],
            ];
        }
    }

    /**
     * Internal Decision Logger
     */
    private function logCortexDecision(string $decisionType, array $context, float $durationMs, bool $success): void
    {
        try {
            $this->telemetry->logTransaction(
                'YalihanCortex',
                $decisionType,
                $durationMs / 1000,
                0,
                0,
                $success ? 200 : 500,
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
            LogService::warning('Failed to log Cortex decision in MatchingService', [
                'decision_type' => $decisionType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getRiskLevel(int $score): string
    {
        if ($score >= 70) return 'high';
        if ($score >= 40) return 'medium';
        return 'low';
    }

    private function getChurnRecommendation(int $score): string
    {
        if ($score >= 70) return 'Acil müdahale gerekli. Müşteri ile hemen iletişime geçin.';
        if ($score >= 40) return 'Dikkatli takip edilmeli. Proaktif iletişim önerilir.';
        return 'Düşük risk. Normal takip yeterli.';
    }

    private function enrichMatches(array $matches, Talep $talep, float $churnScore = 0): array
    {
        return collect($matches)
            ->map(function ($match) use ($talep, $churnScore) {
                $ilan = $match['ilan'];
                $matchScore = (float) $match['score'];
                $actionScore = $matchScore + ($churnScore * 0.5);

                return [
                    'ilan_id' => $ilan->id,
                    'baslik' => $ilan->baslik,
                    'fiyat' => $ilan->fiyat,
                    'para_birimi' => $ilan->para_birimi ?? 'TRY',
                    'match_score' => round($matchScore, 2),
                    'churn_score' => round($churnScore, 2),
                    'action_score' => round($actionScore, 2),
                    'match_level' => $this->getMatchLevel($matchScore),
                    'reasons' => $match['reasons'] ?? [],
                    'breakdown' => $match['breakdown'] ?? [],
                    'priority' => $this->calculatePriority($match, $talep, $churnScore),
                ];
            })
            ->filter(fn($match) => ($match['action_score'] ?? 0) > 85)
            ->sortByDesc('action_score')
            ->take(5)
            ->values()
            ->toArray();
    }

    private function getMatchLevel(float $score): string
    {
        if ($score >= 85) return 'excellent';
        if ($score >= 70) return 'good';
        if ($score >= 50) return 'fair';
        return 'poor';
    }

    private function calculatePriority(array $match, Talep $talep, float $churnScore = 0): int
    {
        $matchScore = (float) ($match['score'] ?? 0);
        $actionScore = $matchScore + ($churnScore * 0.5);
        $priority = (int) ($actionScore / 10);
        return min(10, max(0, $priority));
    }

    private function generateRecommendations(Talep $talep, array $result): array
    {
        $recommendations = [];

        if (($result['churn_analysis']['risk_score'] ?? 0) >= 70) {
            $recommendations[] = [
                'type' => 'urgent',
                'title' => 'Yüksek Churn Riski',
                'message' => 'Müşteri ile acil iletişime geçin.',
                'action' => 'contact_customer',
            ];
        }

        if (empty($result['matches'])) {
            $recommendations[] = [
                'type' => 'info',
                'title' => 'Eşleşme Bulunamadı',
                'message' => 'Kriterleri genişletmeyi düşünün.',
                'action' => 'expand_criteria',
            ];
        }

        $highScoreMatches = collect($result['matches'])->where('match_score', '>=', 85)->count();
        if ($highScoreMatches > 0) {
            $recommendations[] = [
                'type' => 'success',
                'title' => 'Mükemmel Eşleşmeler Bulundu',
                'message' => "{$highScoreMatches} adet yüksek kaliteli eşleşme mevcut.",
                'action' => 'review_matches',
            ];
        }

        return $recommendations;
    }
}
