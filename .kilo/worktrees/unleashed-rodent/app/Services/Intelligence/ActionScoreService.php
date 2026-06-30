<?php

namespace App\Services\Intelligence;

use App\Enums\IlanDurumu;

use App\Models\Kisi;
use App\Services\AI\YalihanCortex;
use App\Services\AI\KisiChurnService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Action Score Service
 * Context7: Fırsat Sentezi (Opportunity Synthesis) için Action Score hesaplama servisi
 *
 * Action Score = (Match Score × 0.6) + (Churn Risk × 0.4)
 * Yüksek Action Score = Acil satış fırsatı
 */
class ActionScoreService
{
    public function __construct(
        private KisiChurnService $churn,
    ) {}

    /**
     * İşlem Riski Skoru Hesapla (0-100)
     * Yüksek = Fırsat, Müdahalesi Gerekir
     *
     * @param Kisi $kisi
     * @return array
     */
    public function calculateActionScore(Kisi $kisi): array
    {
        $cacheKey = "action_score:kisi:{$kisi->id}";

        return Cache::remember($cacheKey, 3600, function () use ($kisi) {
            $talep = $kisi->talepler()->whereIn('aktiflik_durumu', [IlanDurumu::YAYINDA->value, 1, true])->latest()->first();

            if (!$talep) {
                return [
                    'kisi_id' => $kisi->id,
                    'kisi_adi' => $kisi->tam_ad,
                    'match_score' => 0,
                    'churn_risk' => 0,
                    'action_score' => 0,
                    'priority_level' => 'DÜŞÜK',
                    'recommendation' => 'Talep bulunamadı',
                    'calculated_at' => now(),
                ];
            }

            try {
                // Match score (YalihanCortex kullanarak)
                $matchResult = app(\App\Services\AI\YalihanCortex::class)->matchForSale($talep);
                $matches = $matchResult['matches'] ?? [];
                $topMatch = $matches[0] ?? null;
                $matchScore = (float) ($topMatch['match_score'] ?? 0);

                // Churn risk
                $churnResult = $this->churn->calculateChurnRisk($kisi);
                $churnScore = (float) ($churnResult['risk_score'] ?? $churnResult['score'] ?? 0);

                // Action Score: (Match × 0.6) + (Churn × 0.4)
                $actionScore = ($matchScore * 0.6) + ($churnScore * 0.4);

                return [
                    'kisi_id' => $kisi->id,
                    'kisi_adi' => $kisi->tam_ad,
                    'talep_id' => $talep->id,
                    'talep_baslik' => $talep->baslik ?? 'Talep',
                    'match_score' => round($matchScore, 2),
                    'churn_risk' => round($churnScore, 2),
                    'action_score' => round($actionScore, 2),
                    'priority_level' => $this->determinePriority($actionScore),
                    'recommendation' => $this->generateRecommendation($kisi, $actionScore, $matchScore, $churnScore),
                    'top_match' => $topMatch ? [
                        'ilan_id' => $topMatch['ilan_id'] ?? null,
                        'baslik' => $topMatch['baslik'] ?? null,
                        'fiyat' => $topMatch['fiyat'] ?? null,
                    ] : null,
                    'calculated_at' => now(),
                ];
            } catch (\Exception $e) {
                Log::error('Action Score hesaplama hatası', [
                    'kisi_id' => $kisi->id,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'kisi_id' => $kisi->id,
                    'kisi_adi' => $kisi->tam_ad,
                    'match_score' => 0,
                    'churn_risk' => 0,
                    'action_score' => 0,
                    'priority_level' => 'DÜŞÜK',
                    'recommendation' => 'Hesaplama hatası: ' . $e->getMessage(),
                    'calculated_at' => now(),
                ];
            }
        });
    }

    /**
     * Top N Müşteri: Action Score'a göre sıralanmış
     *
     * @param int $limit
     * @return array
     */
    public function getTopOpportunities(int $limit = 5): array
    {
        $cacheKey = "top_opportunities:{$limit}";

        return Cache::remember($cacheKey, 1800, function () use ($limit) {
            $activeCustomers = Kisi::where('aktiflik_durumu', 1)
                ->has('talepler')
                ->with(['talepler' => function ($query) {
                    $query->where('aktiflik_durumu', true)->latest();
                }])
                ->get();

            $opportunities = [];

            foreach ($activeCustomers as $kisi) {
                $opportunity = $this->calculateActionScore($kisi);

                // Sadece action_score > 0 olanları ekle
                if ($opportunity['action_score'] > 0) {
                    $opportunities[] = $opportunity;
                }
            }

            // Action Score'a göre azalan sıra
            usort($opportunities, fn($a, $b) => $b['action_score'] <=> $a['action_score']);

            return array_slice($opportunities, 0, $limit);
        });
    }

    /**
     * Öncelik seviyesi belirle
     *
     * @param float $score
     * @return string
     */
    private function determinePriority(float $score): string
    {
        return match (true) {
            $score >= 75 => 'ACIL',
            $score >= 50 => 'YÜKSEK',
            $score >= 25 => 'ORTA',
            default => 'DÜŞÜK',
        };
    }

    /**
     * Öneri mesajı oluştur
     *
     * @param Kisi $kisi
     * @param float $actionScore
     * @param float $matchScore
     * @param float $churnScore
     * @return string
     */
    private function generateRecommendation(Kisi $kisi, float $actionScore, float $matchScore, float $churnScore): string
    {
        if ($actionScore >= 75) {
            return sprintf(
                "🔴 ACIL: %s, çok iyi eş bulunmuş (%%%.0f match). Yüksek churn riski (%%%.0f). Hemen telefon ara!",
                $kisi->ad,
                $matchScore,
                $churnScore
            );
        } elseif ($actionScore >= 50) {
            return sprintf(
                "🟠 YÜKSEK: %s ile bağlantı kurmaya çalış. Uygun mülk var (%%%.0f match).",
                $kisi->ad,
                $matchScore
            );
        } elseif ($actionScore >= 25) {
            return sprintf(
                "🟡 ORTA: %s için rutin follow-up yapılmalı. Match skoru: %%.0f%%.",
                $kisi->ad,
                $matchScore
            );
        } else {
            return sprintf(
                "⚪ DÜŞÜK: %s için daha fazla araştırma yapılmalı.",
                $kisi->ad
            );
        }
    }

    /**
     * Cache'i temizle (belirli bir müşteri için)
     *
     * @param int $kisiId
     * @return void
     */
    public function clearCache(int $kisiId): void
    {
        Cache::forget("action_score:kisi:{$kisiId}");
        Cache::forget("top_opportunities:5");
        Cache::forget("top_opportunities:10");
    }

    /**
     * Tüm cache'i temizle
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        Cache::forget("top_opportunities:5");
        Cache::forget("top_opportunities:10");
    }
}
