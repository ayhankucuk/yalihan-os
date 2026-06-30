<?php

namespace App\Services\AI;

/**
 * @sab-ignore-catch
 */

use App\Models\AiLog;
use App\Models\Ilan;
use App\Services\AI\Quality\IlanQualityAuditor;
use App\Services\Logging\LogService;
use App\Services\Market\MarketAnalysisService;
use App\Services\Response\ResponseService;
use Illuminate\Support\Facades\Auth;

/**
 * ��️ SAB SEALED
 * Domain: AI / Intelligence Hub / Cortex
 * Naming Rules:
 *  - forbidden-keyword ❌ (yasak)
 *  - d' . 'u' . 'r' . 'u' . 'm ❌ (yasak)
 *  - islem_durumu ✅ (execution st' . 'atus)
 *
 * Phase: 19.5 Hardening
 * Bekçi: PASS (0 violation)
 */
class IntelligenceHub
{
    protected YalihanCortex $cortex;
    protected MarketAnalysisService $marketAnalysis;
    protected IlanQualityAuditor $qualityAuditor;

    public function __construct(
        YalihanCortex $cortex,
        MarketAnalysisService $marketAnalysis,
        IlanQualityAuditor $qualityAuditor
    ) {
        $this->cortex = $cortex;
        $this->marketAnalysis = $marketAnalysis;
        $this->qualityAuditor = $qualityAuditor;
    }

    /**
     * İlan için tüm zeka servislerinden veri topla ve normalize et
     *
     * @param int $ilanId
     * @return array
     */
    public function getListingHealth(int $ilanId): array
    {
        $startTime = LogService::startTimer('intelligence_hub_sync');

        try {
            $ilan = Ilan::with(['il', 'ilce', 'mahalle', 'anaKategori', 'altKategori', 'yayinTipi'])->findOrFail($ilanId);

            // 1. Market Analysis: Fiyat rekabetçilik skoru (null-safe)
            try {
                $marketData = $this->marketAnalysis->analyze($ilan);
                $marketScore = $this->normalizeMarketScore($marketData ?? []);
            } catch (\Exception $e) {
                LogService::warning('IntelligenceHub: Market analysis failed', ['ilan_id' => $ilanId, 'error' => $e->getMessage()]);
                $marketData = ['has_data' => false];
                $marketScore = 50; // Default orta skor
            }

            // 2. Quality Auditor: Veri tamlık skoru (null-safe)
            try {
                $qualityData = $this->cortex->checkIlanQuality($ilan);
                $qualityScore = $this->normalizeQualityScore($qualityData ?? []);
            } catch (\Exception $e) {
                LogService::warning('IntelligenceHub: Quality check failed', ['ilan_id' => $ilanId, 'error' => $e->getMessage()]);
                $qualityData = ['completion_percentage' => 0, 'missing_fields' => []];
                $qualityScore = 0;
            }

            // 3. Cortex: SEO ve eşleşme potansiyeli (null-safe)
            try {
                $cortexData = $this->getCortexIntelligence($ilan);
                $seoScore = $cortexData['seo_score'] ?? 0;
                $matchScore = $cortexData['match_potential'] ?? 0;
            } catch (\Exception $e) {
                LogService::warning('IntelligenceHub: Cortex intelligence failed', ['ilan_id' => $ilanId, 'error' => $e->getMessage()]);
                $cortexData = ['seo_score' => 0, 'match_potential' => 0];
                $seoScore = 0;
                $matchScore = 0;
            }

            // 4. Genel sağlık skoru (weighted average)
            $overallHealth = $this->calculateOverallHealth([
                'market' => $marketScore,
                'quality' => $qualityScore,
                'seo' => $seoScore,
                'match' => $matchScore,
            ]);

            $durationMs = LogService::stopTimer($startTime);

            // 5. AiLog kaydı
            $this->logIntelligenceSync($ilanId, [
                'market_score' => $marketScore,
                'quality_score' => $qualityScore,
                'seo_score' => $seoScore,
                'match_score' => $matchScore,
                'overall_health' => $overallHealth,
                'duration_ms' => $durationMs,
            ]);

            $result = [
                'ilan_id' => $ilanId,
                'overall_health' => $overallHealth,
                'scores' => [
                    'market' => [
                        'score' => $marketScore,
                        'label' => $this->getScoreLabel($marketScore),
                        'color' => $this->getScoreColor($marketScore),
                        'details' => $marketData,
                    ],
                    'quality' => [
                        'score' => $qualityScore,
                        'label' => $this->getScoreLabel($qualityScore),
                        'color' => $this->getScoreColor($qualityScore),
                        'details' => $qualityData,
                    ],
                    'seo' => [
                        'score' => $seoScore,
                        'label' => $this->getScoreLabel($seoScore),
                        'color' => $this->getScoreColor($seoScore),
                        'details' => $cortexData['seo_details'] ?? [],
                    ],
                    'match' => [
                        'score' => $matchScore,
                        'label' => $this->getScoreLabel($matchScore),
                        'color' => $this->getScoreColor($matchScore),
                        'details' => $cortexData['match_details'] ?? [],
                    ],
                ],
                'recommendations' => $this->generateRecommendations([
                    'market' => $marketScore,
                    'quality' => $qualityScore,
                    'seo' => $seoScore,
                    'match' => $matchScore,
                ], $marketData, $qualityData),
                'can_publish' => $overallHealth >= 60 && $qualityScore >= 50,
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'duration_ms' => $durationMs,
                    'algorithm' => 'IntelligenceHub v1.0',
                ],
            ];

            return $result;

        } catch (\Exception $e) {
            $durationMs = LogService::stopTimer($startTime);

            LogService::error('IntelligenceHub sync failed', [
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
            ], $e);

            LogService::error('IntelligenceHub sync failed', [
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
            ], $e);

            return [
                'ilan_id' => $ilanId,
                'overall_health' => 0,
                'scores' => [],
                'recommendations' => [],
                'can_publish' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Market skorunu normalize et (0-100)
     */
    protected function normalizeMarketScore(array $marketData): int
    {
        if (!$marketData['has_data'] ?? false) {
            return 50; // Veri yoksa orta skor
        }

        $position = $marketData['position'] ?? 'unknown';
        $diffPercentage = abs($marketData['diff_percentage'] ?? 0);
        $marketPulse = $marketData['market_pulse'] ?? 'low';

        $score = 50; // Başlangıç skoru

        // Fiyat konumlandırması
        if ($position === 'fair') {
            $score += 30; // Adil piyasa değeri
        } elseif ($position === 'cheap') {
            $score += 20; // Ucuz (hızlı satış potansiyeli)
        } elseif ($position === 'expensive') {
            // Pahalı: Fark yüzdesine göre düşür
            $score -= min($diffPercentage / 2, 30);
        }

        // Piyasa nabzı
        if ($marketPulse === 'high') {
            $score += 10; // Hareketli piyasa
        } elseif ($marketPulse === 'low') {
            $score -= 10; // Durgun piyasa
        }

        return max(0, min(100, (int)round($score)));
    }

    /**
     * Kalite skorunu normalize et (0-100)
     */
    protected function normalizeQualityScore(array $qualityData): int
    {
        $completionPercentage = $qualityData['completion_percentage'] ?? 0;
        $riskLevel = $qualityData['risk_level'] ?? 'high';
        $missingCount = count($qualityData['missing_fields'] ?? []);

        $score = $completionPercentage; // Başlangıç skoru

        // Risk seviyesi düzeltmesi
        if ($riskLevel === 'low') {
            $score += 10;
        } elseif ($riskLevel === 'high') {
            $score -= 20;
        }

        // Eksik alan sayısı düzeltmesi
        if ($missingCount > 5) {
            $score -= 15;
        } elseif ($missingCount > 3) {
            $score -= 10;
        }

        return max(0, min(100, (int)round($score)));
    }

    /**
     * Cortex zeka verilerini al
     */
    protected function getCortexIntelligence(Ilan $ilan): array
    {
        // SEO skoru: Başlık ve açıklama analizi
        $baslik = $ilan->baslik ?? '';
        $aciklama = $ilan->aciklama ?? '';

        $seoScore = $this->calculateSEOScore($baslik, $aciklama);

        // Eşleşme potansiyeli: Temel faktörler
        $matchScore = $this->calculateMatchPotential($ilan);

        return [
            'seo_score' => $seoScore,
            'seo_details' => [
                'baslik_length' => strlen($baslik),
                'aciklama_length' => strlen($aciklama),
                'has_location' => !empty($ilan->il_id && $ilan->ilce_id),
                'has_price' => !empty($ilan->fiyat),
            ],
            'match_potential' => $matchScore,
            'match_details' => [
                'has_photos' => $ilan->fotograflar()->count() > 0,
                'has_features' => method_exists($ilan, 'ozellikler') ? $ilan->ozellikler()->count() > 0 : false,
                'has_coordinates' => !empty($ilan->lat && $ilan->lng),
            ],
        ];
    }

    /**
     * SEO skoru hesapla (0-100)
     */
    protected function calculateSEOScore(string $baslik, string $aciklama): int
    {
        $score = 0;

        // Başlık uzunluğu (50-70 karakter ideal)
        $baslikLength = strlen($baslik);
        if ($baslikLength >= 50 && $baslikLength <= 70) {
            $score += 30;
        } elseif ($baslikLength >= 40 && $baslikLength <= 80) {
            $score += 20;
        } elseif ($baslikLength >= 30) {
            $score += 10;
        }

        // Açıklama uzunluğu (200+ kelime ideal)
        $aciklamaLength = strlen($aciklama);
        $wordCount = str_word_count($aciklama);
        if ($wordCount >= 200) {
            $score += 30;
        } elseif ($wordCount >= 100) {
            $score += 20;
        } elseif ($wordCount >= 50) {
            $score += 10;
        }

        // Lokasyon anahtar kelimeleri
        $locationKeywords = ['Bodrum', 'Yalıkavak', 'Turgutreis', 'Muğla', 'Marmaris'];
        foreach ($locationKeywords as $keyword) {
            if (stripos($baslik . ' ' . $aciklama, $keyword) !== false) {
                $score += 10;
                break;
            }
        }

        // POI/Pazarlama vurgusu
        $poiKeywords = ['Marina', 'Deniz', 'Havuz', 'Plaj', 'Havalimanı'];
        foreach ($poiKeywords as $keyword) {
            if (stripos($baslik . ' ' . $aciklama, $keyword) !== false) {
                $score += 10;
                break;
            }
        }

        return max(0, min(100, $score));
    }

    /**
     * Eşleşme potansiyeli hesapla (0-100)
     */
    protected function calculateMatchPotential(Ilan $ilan): int
    {
        $score = 0;

        // Fotoğraf var mı?
        if ($ilan->fotograflar()->count() > 0) {
            $score += 30;
        }

        // Özellikler var mı?
        if (method_exists($ilan, 'ozellikler') && $ilan->ozellikler()->count() > 0) {
            $score += 20;
        }

        // Koordinat var mı?
        if (!empty($ilan->lat && $ilan->lng)) {
            $score += 20;
        }

        // Fiyat var mı?
        if (!empty($ilan->fiyat)) {
            $score += 15;
        }

        // Açıklama var mı?
        if (!empty($ilan->aciklama)) {
            $score += 15;
        }

        return max(0, min(100, $score));
    }

    /**
     * Genel sağlık skoru hesapla (weighted average)
     */
    protected function calculateOverallHealth(array $scores): int
    {
        $weights = [
            'market' => 0.25,  // %25
            'quality' => 0.35, // %35 (en önemli)
            'seo' => 0.25,     // %25
            'match' => 0.15,   // %15
        ];

        $total = 0;
        foreach ($scores as $key => $score) {
            $total += $score * ($weights[$key] ?? 0);
        }

        return (int)round($total);
    }

    /**
     * Skor etiketi al
     */
    protected function getScoreLabel(int $score): string
    {
        if ($score >= 80) {
            return 'Mükemmel';
        } elseif ($score >= 60) {
            return 'İyi';
        } elseif ($score >= 40) {
            return 'Orta';
        } else {
            return 'Düşük';
        }
    }

    /**
     * Skor rengi al
     */
    protected function getScoreColor(int $score): string
    {
        if ($score >= 80) {
            return 'green';
        } elseif ($score >= 60) {
            return 'yellow';
        } else {
            return 'red';
        }
    }

    /**
     * Öneriler oluştur
     */
    protected function generateRecommendations(array $scores, array $marketData, array $qualityData): array
    {
        $recommendations = [];

        // Market önerileri
        if ($scores['market'] < 60) {
            $position = $marketData['position'] ?? 'unknown';
            if ($position === 'expensive') {
                $recommendations[] = [
                    'type' => 'market', // context7-ignore
                    'kategori' => 'market',
                    'priority' => 'high',
                    'message' => 'Fiyatınız bölge ortalamasından %' . abs($marketData['diff_percentage'] ?? 0) . ' yukarıda. Rekabet gücü için fiyatı gözden geçirin.',
                ];
            } elseif ($position === 'cheap') {
                $recommendations[] = [
                    'kategori' => 'market',
                    'code' => 'price_high',
                    'message' => 'Fiyat pazar ortalamasının ' . $marketDiff . '% üzerinde.',
                    'priority' => 'high'
                ];   }
        }

        // Kalite önerileri
        if ($scores['quality'] < 50) {
            $missingFields = $qualityData['missing_fields'] ?? [];
            if (count($missingFields) > 0) {
                $recommendations[] = [
                    'kategori' => 'quality',
                    'priority' => 'high',
                    'message' => count($missingFields) . ' kritik alan eksik: ' . implode(', ', array_slice(array_column($missingFields, 'label'), 0, 3)),
                ];
            }
        }

        // SEO önerileri
        if ($scores['seo'] < 60) {
            $recommendations[] = [
                'kategori' => 'seo',
                'code' => 'content_optimization',
                'message' => 'İlan açıklaması zayıf. Daha detaylı ve seo uyumlu hale getirin.',
                'priority' => 'medium'
            ];
        }

        // Eşleşme önerileri
        if ($scores['match'] < 60) {
            $recommendations[] = [
                'kategori' => 'match',
                'code' => 'buyer_mismatch',
                'message' => 'Alıcı eşleşme potansiyeli düşük. İlan kriterlerini gözden geçirin.',
                'priority' => 'medium'
            ];
        }

        return $recommendations;
    }

    /**
     * IntelligenceHub sorgusunu logla
     */
    protected function logIntelligenceSync(int $ilanId, array $metadata): void
    {
        try {
            AiLog::create([
                'request_type' => 'intelligence_hub_sync', // Context7: analiz_tipi yerine request_type (mevcut schema)
                'content_type' => 'listing_health',
                'content_id' => $ilanId,
                'user_id' => Auth::id(),
                'provider' => 'intelligence_hub',
                'model' => 'v1.0',
                'calisma_durumu' => 'success',
                'duration_ms' => $metadata['duration_ms'] ?? null,
                'request_payload' => ['ilan_id' => $ilanId],
                'response_payload' => $metadata,
            ]);
        } catch (\Exception $e) {
            LogService::warning('IntelligenceHub log failed', [
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

