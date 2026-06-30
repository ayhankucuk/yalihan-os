<?php

namespace App\Services\AI;

use App\Enums\IlanDurumu;

use App\Models\MarketListing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🎯 Opportunity Inbox Service (Production)
 *
 * Phase 18 MVP: AI Fırsat Avcısı ürün yüzeyi servis katmanı.
 *
 * SAB §1: Controller iş mantığı içermez — tüm logic burada.
 * CQRS: Yalnızca proj_listings projection tablosundan okur.
 * Guard: AI scoring algoritmasını değiştirmez, mevcut motorları orkestre eder.
 *
 * Akış:
 *   proj_listings → sinyal çıkarma → scoring → format → sıralama
 */
class OpportunityInboxService
{
    public function __construct(
        private OpportunityFormatterService $formatterService,
    ) {}

    /**
     * Fırsat inbox listesini döndür.
     *
     * proj_listings projection tablosundan aktif ilanları çeker,
     * her biri için sinyal tabanlı fırsat skoru hesaplar,
     * skor sırasına göre sıralı liste döndürür.
     *
     * @param int $minScore Minimum fırsat skoru (0-100)
     * @param int $limit Maksimum fırsat sayısı
     * @return Collection
     */
    public function getInbox(int $minScore = 60, int $limit = 50): Collection
    {
        // 1. proj_listings projection'dan aktif ilanları çek
        $listings = MarketListing::where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->orderBy('updated_at', 'desc') // context7-ignore
            ->limit(200)
            ->get();

        // 2. Ortalama fiyat hesapla (bölge bazlı scoring için)
        $avgPrice = $listings->avg('fiyat') ?: 1;

        // 3. Her ilan için sinyal tabanlı fırsat skoru hesapla
        $scored = $listings->map(function (MarketListing $listing) use ($avgPrice) {
            $signals = $this->detectSignals($listing, $avgPrice);
            $score = $this->calculateScore($signals);
            $reason = $this->buildReason($signals);
            $trend = $this->detectTrend($listing);

            $formattedExplanation = $this->formatterService->formatExplanation([
                'score' => $score,
                'reason' => $reason,
            ]);

            return [
                'id' => $listing->id,
                'ilan_id' => $listing->ilan_id,
                'baslik' => $listing->baslik ?? 'İlan bulunamadı',
                'fiyat' => $listing->fiyat ?? 0,
                'para_birimi' => $listing->para_birimi,
                'il_id' => $listing->il_id,
                'kategori_id' => $listing->kategori_id,
                'opportunity_score' => $score,
                'reason' => $reason,
                'explanation' => $formattedExplanation,
                'trend' => $trend,
                'signals' => $signals,
                'gecen_gun_sayisi' => $listing->gecen_gun_sayisi,
                'detected_at' => now()->toISOString(),
            ];
        });

        // 4. Skora göre sırala, minimum skor filtrele, limit uygula
        return $scored
            ->filter(fn ($item) => $item['opportunity_score'] >= $minScore)
            ->sortByDesc('opportunity_score')
            ->take($limit)
            ->values();
    }

    /**
     * Tek bir fırsat detayını döndür (ilan_id bazlı).
     */
    public function getDetail(int $ilanId): ?array
    {
        $listing = MarketListing::where('ilan_id', $ilanId)->first();

        if (! $listing) {
            return null;
        }

        $avgPrice = MarketListing::where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->where('kategori_id', $listing->kategori_id)
            ->avg('fiyat') ?: 1;

        $signals = $this->detectSignals($listing, $avgPrice);
        $score = $this->calculateScore($signals);
        $reason = $this->buildReason($signals);
        $trend = $this->detectTrend($listing);

        return [
            'id' => $listing->id,
            'ilan_id' => $listing->ilan_id,
            'baslik' => $listing->baslik ?? 'İlan bulunamadı',
            'fiyat' => $listing->fiyat ?? 0,
            'para_birimi' => $listing->para_birimi,
            'il_id' => $listing->il_id,
            'kategori_id' => $listing->kategori_id,
            'opportunity_score' => $score,
            'reason' => $reason,
            'explanation' => $this->formatterService->formatExplanation([
                'score' => $score,
                'reason' => $reason,
            ]),
            'trend' => $trend,
            'signals' => $signals,
            'gecen_gun_sayisi' => $listing->gecen_gun_sayisi,
            'detected_at' => now()->toISOString(),
        ];
    }

    /**
     * Inbox özet istatistikleri.
     */
    public function getStats(): array
    {
        $total = MarketListing::where('yayin_durumu', IlanDurumu::YAYINDA->value)->count();
        $avgPrice = MarketListing::where('yayin_durumu', IlanDurumu::YAYINDA->value)->avg('fiyat');

        return [
            'total_active_listings' => $total,
            'avg_price' => round($avgPrice ?? 0, 2),
            'last_scan' => now()->toISOString(),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Sinyal Motoru (Mevcut scoring motorlarını DEĞİŞTİRMEZ)
    // proj_listings verisinden heuristic sinyaller çıkarır
    // ─────────────────────────────────────────────────────────────

    /**
     * Projection verisinden fırsat sinyalleri çıkar.
     * Guard: AI algoritmasını değiştirmez, ver tabanlı sinyal üretir.
     */
    private function detectSignals(MarketListing $listing, float $avgPrice): array
    {
        $signals = [];

        // 1️⃣ Fiyat düşüşü / avantajı sinyali
        if ($listing->fiyat > 0 && $avgPrice > 0) {
            $ratio = $listing->fiyat / $avgPrice;
            if ($ratio < 0.8) {
                $signals['price_drop_signal'] = ['value' => round((1 - $ratio) * 100), 'weight' => 30];
            } elseif ($ratio < 0.9) {
                $signals['price_drop_signal'] = ['value' => round((1 - $ratio) * 100), 'weight' => 15];
            }
        }

        // 2️⃣ Bölge trendi sinyali (gecen_gun_sayisi düşükse bölge aktif)
        $avgDays = MarketListing::where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->where('il_id', $listing->il_id)
            ->avg('gecen_gun_sayisi') ?: 30;

        if ($listing->gecen_gun_sayisi < $avgDays * 0.5) {
            $signals['market_trend_signal'] = ['value' => 'active_region', 'weight' => 15]; // context7-ignore
        }

        // 3️⃣ Talep yoğunluğu sinyali (aynı bölgede çok ilan = rekabet, az ilan = fırsat)
        $regionCount = MarketListing::where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->where('il_id', $listing->il_id)
            ->where('kategori_id', $listing->kategori_id)
            ->count();

        if ($regionCount <= 3) {
            $signals['buyer_demand_signal'] = ['value' => 'low_supply', 'weight' => 20];
        }

        // 4️⃣ Satış hızı sinyali (gecen_gun_sayisi düşükse likidite yüksek)
        if ($listing->gecen_gun_sayisi <= 7) {
            $signals['liquidity_signal'] = ['value' => 'high', 'weight' => 15];
        } elseif ($listing->gecen_gun_sayisi <= 14) {
            $signals['liquidity_signal'] = ['value' => 'medium', 'weight' => 8];
        }

        // 5️⃣ Yeni ilan sinyali
        if ($listing->gecen_gun_sayisi <= 3) {
            $signals['fresh_listing_signal'] = ['value' => 'new', 'weight' => 20];
        }

        return $signals;
    }

    /**
     * Sinyallerden toplam fırsat skoru hesapla (0-100).
     */
    private function calculateScore(array $signals): int
    {
        $total = 0;

        foreach ($signals as $signal) {
            $total += $signal['weight'] ?? 0;
        }

        return min(100, max(0, $total));
    }

    /**
     * Sinyallerden insan tarafından okunabilir gerekçe üret.
     */
    private function buildReason(array $signals): string
    {
        $reasons = [];

        if (isset($signals['price_drop_signal'])) {
            $pct = $signals['price_drop_signal']['value'];
            $reasons[] = "Fiyat avantajı (%{$pct} ortalamanın altında)";
        }

        if (isset($signals['fresh_listing_signal'])) {
            $reasons[] = 'Yeni ilan — erken hareket avantajı';
        }

        if (isset($signals['buyer_demand_signal'])) {
            $reasons[] = 'Düşük arz — bölgede az rakip';
        }

        if (isset($signals['liquidity_signal'])) {
            $val = $signals['liquidity_signal']['value'];
            if ($val === 'high') {
                $reasons[] = 'Yüksek likidite — hızlı satış potansiyeli';
            }
        }

        if (isset($signals['market_trend_signal'])) {
            $reasons[] = 'Aktif bölge — talep artışı';
        }

        return implode(' • ', $reasons) ?: 'Standart ilan';
    }

    /**
     * İlanın trend yönünü belirle.
     */
    private function detectTrend(MarketListing $listing): string
    {
        if ($listing->gecen_gun_sayisi <= 7) {
            return 'rising';
        }

        if ($listing->gecen_gun_sayisi >= 60) {
            return 'declining';
        }

        return 'stable';
    }
}
