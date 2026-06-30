<?php

namespace App\Services\Dashboard;

use App\Services\AI\ChurnRiskService;
use App\Services\Market\MarketAnalysisService;
use App\Services\Matching\DemandMatchingEngine;
use App\Models\Ilan;
use App\Models\Talep;
use App\Enums\IlanDurumu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Cortex Action Center Service
 *
 * Context7 Standard: C7-ACTION-CENTER-2026-01-06
 *
 * Sorumluluk:
 * Tüm Cortex modüllerinden (Churn, Market, Match) gelen sinyalleri
 * toplayıp danışmana öncelikli aksiyon listesi sunar.
 */
class ActionCenterService
{
    protected ChurnRiskService $churnService;
    protected DemandMatchingEngine $matchingEngine;

    public function __construct(
        ChurnRiskService $churnService,
        DemandMatchingEngine $matchingEngine
    ) {
        $this->churnService = $churnService;
        $this->matchingEngine = $matchingEngine;
    }

    /**
     * Danışman için günlük aksiyon listesini al
     *
     * @param int|null $userId Danışman ID (null = tüm sistem)
     * @return Collection
     */
    public function getDailyActions(?int $userId = null): Collection
    {
        $cacheKey = $userId ? "action_center_user_{$userId}" : 'action_center_global';

        return Cache::remember($cacheKey, 60 * 30, function () use ($userId) {
            $actions = collect();

            // 1. Kritik Churn Riski
            $actions = $actions->merge($this->getChurnActions($userId));

            // 2. Fiyat Fırsatları
            $actions = $actions->merge($this->getPricingActions($userId));

            // 3. Sıcak Eşleşmeler
            $actions = $actions->merge($this->getHotMatches($userId));

            // Önceliğe göre sırala
            return $actions->sortByDesc('priority')->values();
        });
    }

    /**
     * Churn riski yüksek ilanlar için aksiyonlar
     */
    protected function getChurnActions(?int $userId): Collection
    {
        $actions = collect();

        try {
            $query = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)
                ->where('crm_only', false);

            if ($userId) {
                $query->where('danisman_id', $userId);
            }

            $ilanlar = $query->with(['ilanSahibi', 'ilce'])
                ->latest('updated_at')
                ->limit(50)
                ->get();

            foreach ($ilanlar as $ilan) {
                $churnScore = $this->churnService->calculateChurnRisk($ilan);

                if ($churnScore >= 70) {
                    $actions->push([
                        'type' => 'churn', // context7-ignore
                        'priority' => $this->calculatePriority($churnScore, 'churn'),
                        'ilan_id' => $ilan->id,
                        'ilan' => $ilan,
                        'title' => "⚠️ Yüksek Kayıp Riski: {$ilan->baslik}",
                        'description' => "Churn skoru: {$churnScore}%. Müşteri ilanını kapatabileceğini düşünüyor.",
                        'action_label' => '📞 Müşteriyi Ara',
                        'action_type' => 'call',
                        'action_data' => [
                            'phone' => $ilan->ilanSahibi->telefon ?? null,
                            'owner_name' => $ilan->ilanSahibi ? "{$ilan->ilanSahibi->ad} {$ilan->ilanSahibi->soyad}" : 'İlan Sahibi',
                        ],
                        'score' => $churnScore,
                        'timestamp' => now(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Churn actions failed', ['error' => $e->getMessage()]);
        }

        return $actions;
    }

    /**
     * Fiyat optimizasyonu gerektiren ilanlar
     */
    protected function getPricingActions(?int $userId): Collection
    {
        $actions = collect();

        try {
            $thirtyDaysAgo = Carbon::now()->subDays(30);

            $query = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)
                ->where('created_at', '<=', $thirtyDaysAgo)
                ->whereNotNull('fiyat');

            if ($userId) {
                $query->where('danisman_id', $userId);
            }

            $ilanlar = $query->with(['ilce', 'mahalle'])->take(20)->get();

            foreach ($ilanlar as $ilan) {
                // Market analysis yap
                $analysisService = app(MarketAnalysisService::class);
                $marketData = $analysisService->analyze($ilan);

                if ($marketData['has_data'] && $marketData['diff_percentage'] > 20) {
                    $actions->push([
                        'type' => 'pricing', // context7-ignore
                        'priority' => $this->calculatePriority($marketData['diff_percentage'], 'pricing'),
                        'ilan_id' => $ilan->id,
                        'ilan' => $ilan,
                        'title' => "💰 Fiyat Fırsatı: {$ilan->baslik}",
                        'description' => "Bölge ortalamasının %{$marketData['diff_percentage']} üzerinde. 30+ gündür satılmadı.",
                        'action_label' => '📉 Fiyat İndirimi Öner',
                        'action_type' => 'price_review',
                        'action_data' => [
                            'current_price' => $ilan->fiyat,
                            'avg_price' => $marketData['avg_price'],
                            'suggested_price' => $marketData['avg_price'] * 1.05, // %5 üzerine öner
                        ],
                        'score' => $marketData['diff_percentage'],
                        'timestamp' => now(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Pricing actions failed', ['error' => $e->getMessage()]);
        }

        return $actions;
    }

    /**
     * Son 24 saatte bulunmuş sıcak eşleşmeler
     */
    protected function getHotMatches(?int $userId): Collection
    {
        $actions = collect();

        try {
            $query = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value);

            if ($userId) {
                $query->where('danisman_id', $userId);
            }

            $ilanlar = $query->with(['ilce', 'mahalle'])->take(15)->get();

            foreach ($ilanlar as $ilan) {
                $matches = $this->matchingEngine->findPotentialBuyers($ilan, 85); // 85%+ skor

                // Son 24 saatte oluşturulmuş talepleri filtrele
                $recentMatches = $matches->filter(function ($match) {
                    return $match['talep']->created_at &&
                           $match['talep']->created_at->greaterThan(Carbon::now()->subDay());
                });

                if ($recentMatches->isNotEmpty()) {
                    $bestMatch = $recentMatches->first();
                    $talep = $bestMatch['talep'];
                    $kisi = $talep->kisi;

                    $actions->push([
                        'type' => 'match', // context7-ignore
                        'priority' => $this->calculatePriority($bestMatch['skor'], 'match'),
                        'ilan_id' => $ilan->id,
                        'talep_id' => $talep->id,
                        'ilan' => $ilan,
                        'talep' => $talep,
                        'title' => "🔥 Sıcak Eşleşme: {$kisi->ad} {$kisi->soyad}",
                        'description' => "Yeni talep, ilanınızla %{$bestMatch['yuzde']} uyumlu. " . implode(', ', $bestMatch['eslesme_nedenleri'] ?? []),
                        'action_label' => '📱 WhatsApp Mesajı Gönder',
                        'action_type' => 'send_message',
                        'action_data' => [
                            'buyer_name' => "{$kisi->ad} {$kisi->soyad}",
                            'buyer_phone' => $kisi->telefon,
                            'match_score' => $bestMatch['yuzde'],
                        ],
                        'score' => $bestMatch['skor'],
                        'timestamp' => $talep->created_at,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Hot matches failed', ['error' => $e->getMessage()]);
        }

        return $actions;
    }

    /**
     * Öncelik hesapla (0-100)
     */
    protected function calculatePriority(float $score, string $type): int
    {
        $basePriority = match ($type) {
            'churn' => 90,    // En yüksek öncelik (müşteriyi kaybedeceğiz!)
            'match' => 80,    // İkinci öncelik (sıcak fırsat!)
            'pricing' => 70,  // Üçüncü öncelik (optimizasyon)
            default => 50,
        };

        // Skoru önceliğe ekle (normalize et)
        $normalizedScore = min(10, ($score / 10));

        return min(100, $basePriority + $normalizedScore);
    }

    /**
     * Cache'i temizle
     */
    public function clearCache(?int $userId = null): void
    {
        if ($userId) {
            Cache::forget("action_center_user_{$userId}");
        } else {
            Cache::forget('action_center_global');
        }
    }

    /**
     * Aksiyon istatistikleri
     */
    public function getStats(?int $userId = null): array
    {
        $actions = $this->getDailyActions($userId);

        return [
            'total' => $actions->count(),
            'by_type' => [
                'churn' => $actions->where('type', 'churn')->count(), // context7-ignore
                'pricing' => $actions->where('type', 'pricing')->count(), // context7-ignore
                'match' => $actions->where('type', 'match')->count(), // context7-ignore
            ],
            'high_priority' => $actions->where('priority', '>=', 90)->count(),
        ];
    }
}
