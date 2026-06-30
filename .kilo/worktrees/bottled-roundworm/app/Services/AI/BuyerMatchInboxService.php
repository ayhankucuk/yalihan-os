<?php

namespace App\Services\AI;

use App\Models\Ilan;
use App\Services\AIMatch\BuyerMatchDetectionService;
use App\Services\AIMatch\BuyerMatchFormatterService;
use App\Services\AIMatch\BuyerMatchTelemetryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * 🤝 Buyer Match Inbox Service (Production)
 *
 * Phase 18 MVP: AI Alıcı Bulucu ürün yüzeyi servis katmanı.
 *
 * SAB §1: Controller iş mantığı içermez — tüm logic burada.
 * Guard: AI scoring algoritmasını değiştirmez, mevcut motorları orkestre eder.
 *
 * Akış:
 *   ilan → candidate pooling (projections) → scoring → formatting → sıralama
 *
 * Kullanılan motorlar:
 *   - BuyerMatchDetectionService (candidate pooling + scoring)
 *   - BuyerMatchFormatterService (6-dil açıklama)
 *   - BuyerMatchTelemetryService (log)
 */
class BuyerMatchInboxService
{
    public function __construct(
        private BuyerMatchDetectionService $detectionService,
        private BuyerMatchFormatterService $formatterService,
        private BuyerMatchTelemetryService $telemetryService,
    ) {}

    /**
     * İlan için en uygun alıcı eşleşmelerini döndür.
     *
     * @param Ilan $ilan Eşleşme yapılacak ilan
     * @param int  $limit Maksimum eşleşme sayısı
     * @return array{listing_id: int, matches: array, meta: array}
     */
    public function getMatchesForListing(Ilan $ilan, int $limit = 10): array
    {
        try {
            // 1. Detection: Candidate pool + scoring (mevcut motorlar)
            $rawMatches = $this->detectionService->detectForListing($ilan, $limit);

            // 2. Format: Skoru, nedeni ve intent sinyaliyle birlikte döndür
            $matches = $rawMatches->map(function ($match) {
                $buyer = $match['buyer'];
                $scoreData = $match['score'];
                $breakdown = $scoreData['breakdown'] ?? [];

                // Intent signal: intent + activity + action ağırlıklarına göre
                $intentScore = ($breakdown['intent'] ?? 0) + ($breakdown['activity'] ?? 0) + ($breakdown['action'] ?? 0);
                $intentSignal = $this->classifyIntent($intentScore);

                // Eşleşme gerekçesi (6 dilde)
                $reason = $this->formatterService->formatReasons($breakdown);

                return [
                    'buyer_id' => $buyer->id,
                    'buyer_adi' => $buyer->ad ?? '',
                    'buyer_soyadi' => $buyer->soyad ?? '',
                    'buyer_telefon' => $buyer->telefon ?? null,
                    'buyer_email' => $buyer->email ?? null,
                    'talep_id' => $match['talep_id'] ?? null,
                    'match_score' => round($scoreData['total'], 1),
                    'breakdown' => $breakdown,
                    'reason' => $reason,
                    'intent_signal' => $intentSignal,
                ];
            })->values()->toArray();

            // 3. Telemetry: Eşleşme işlemini logla
            $topMatch = $matches[0] ?? null;
            $this->telemetryService->recordSnapshot(
                $ilan,
                count($matches),
                $topMatch['match_score'] ?? 0,
                $topMatch['buyer_id'] ?? null,
            );

            return [
                'listing_id' => $ilan->id,
                'matches' => $matches,
                'meta' => [
                    'total' => count($matches),
                    'listing_baslik' => $ilan->baslik ?? '',
                    'listing_fiyat' => $ilan->fiyat ?? 0,
                    'listing_emlak_tipi' => $ilan->emlak_tipi ?? '',
                    'listing_il' => $ilan->il?->il_adi ?? '',
                    'listing_ilce' => $ilan->ilce?->ilce_adi ?? '',
                    'generated_at' => now()->toISOString(),
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('BuyerMatchInboxService: Match generation failed', [
                'ilan_id' => $ilan->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'listing_id' => $ilan->id,
                'matches' => [],
                'meta' => [
                    'total' => 0,
                    'error' => 'Eşleşme hesaplanırken hata oluştu.',
                    'generated_at' => now()->toISOString(),
                ],
            ];
        }
    }

    /**
     * Intent sinyalini sınıflandır.
     */
    private function classifyIntent(float $intentScore): string
    {
        if ($intentScore >= 15) return 'high';
        if ($intentScore >= 8) return 'medium';
        return 'low';
    }
}
