<?php

namespace App\Services\AI;

use App\Models\Ilan;
use App\Services\AIMatch\BuyerMatchDetectionService;
use App\Services\AIMatch\BuyerMatchScoringService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * 🏢 SAB SEALED
 * AI Buyer Match Queue (Deal Maker Engine)
 *
 * Advisor-facing sales execution surface. Wraps existing match detection
 * to generate actionable sales intelligence (urgency, priority, action).
 */
class BuyerMatchQueueService
{
    public function __construct(
        private BuyerMatchDetectionService $detectionService,
    ) {}

    /**
     * Get prioritized buyer matches for the queue dashboard.
     *
     * @param Ilan $ilan
     * @param int $limit
     * @return array
     */
    public function getMatchesForQueue(Ilan $ilan, int $limit = 15): array
    {
        try {
            // Leverage existing architecture to get scored candidates
            $rawMatches = $this->detectionService->detectForListing($ilan, $limit);

            $queueMatches = $rawMatches->map(function ($match) use ($ilan) {
                $buyer = $match['buyer'];
                $scoreData = $match['score'];
                $breakdown = $scoreData['breakdown'] ?? [];

                $totalScore = $scoreData['total'] ?? 0;
                $churnScore = $breakdown['churn'] ?? 0; // The actual risk component

                $tier = $this->determineMatchTier($totalScore);
                $urgency = $this->determineUrgencySignal($breakdown, $churnScore);

                $reasonsList = $this->generateReasonList($breakdown);

                return [
                    'buyer_id' => $buyer->id,
                    'buyer_name' => trim(($buyer->ad ?? '') . ' ' . ($buyer->soyad ?? '')),
                    'buyer_phone' => $buyer->telefon ?? '',
                    'match_score' => round($totalScore, 1),
                    'match_tier' => $tier,
                    'primary_reason' => $this->generatePrimaryReason($breakdown),
                    'match_reasons' => $reasonsList,
                    'urgency_signal' => $urgency,
                    'suggested_action' => $this->generateSuggestedAction($tier, $urgency),
                    'contact_priority' => $this->calculateContactPriority($totalScore, $urgency, $churnScore),
                ];
            });

            // Format as expected by UI and API
            $sortedQueue = $this->sortByContactPriority($queueMatches->toArray());

            return [
                'listing' => [
                    'id' => $ilan->id,
                    'title' => $ilan->baslik ?? '',
                    'price' => $ilan->fiyat ?? 0,
                    'location' => ($ilan->il?->il_adi ?? '') . ' / ' . ($ilan->ilce?->ilce_adi ?? ''),
                ],
                'total_matches' => count($sortedQueue),
                'average_match_score' => $queueMatches->avg('match_score') ? round($queueMatches->avg('match_score'), 1) : 0,
                'hot_matches' => $queueMatches->where('match_tier', 'HOT')->count(),
                'matches' => $sortedQueue,
            ];

        } catch (\Throwable $e) {
            Log::error('BuyerMatchQueueService: Failed to generate queue', [
                'ilan_id' => $ilan->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'listing' => ['id' => $ilan->id, 'title' => $ilan->baslik ?? '', 'price' => 0, 'location' => ''],
                'total_matches' => 0,
                'average_match_score' => 0,
                'hot_matches' => 0,
                'matches' => [],
                'error' => 'Kuyruk yüklenirken bir sorun oluştu.',
            ];
        }
    }

    /**
     * Classifies the score into a sales Tier.
     */
    private function determineMatchTier(float $score): string
    {
        if ($score >= 85) return 'HOT';
        if ($score >= 70) return 'WARM';
        if ($score >= 55) return 'WATCH';
        return 'LOW';
    }

    /**
     * Determines the behavioral urgency based on score components.
     */
    private function determineUrgencySignal(array $breakdown, float $churnScore): string
    {
        $intent = $breakdown['intent'] ?? 0;
        $activity = $breakdown['activity'] ?? 0;

        if ($churnScore > 3) return 'AT_RISK'; // High churn risk taking precedence
        if ($intent > 7 && $activity > 3) return 'HIGH_INTENT';
        if ($intent > 4 || $activity > 2) return 'ACTIVE_SEARCH';

        return 'PASSIVE';
    }

    /**
     * Extracts the most prominent reason for the match.
     */
    private function generatePrimaryReason(array $breakdown): string
    {
        arsort($breakdown);
        $topFactor = array_key_first($breakdown);

        $messages = [
            'price' => 'Bütçe uyumu (%90+ eşleşme)',
            'location' => 'İlan bölgesi ile birebir konum tercihi',
            'features' => 'İstenen özelliklerin büyük bir kısmı mevcut',
            'rooms' => 'Oda sayısı tercihi tam uyuşuyor',
            'reason_category' => 'Arayışındaki emlak sınıfıyla (kayit_tipi) örtüşüyor', // context7 safe
            'intent' => 'Yakın zamanda acil alım niyeti sinyali verdi',
            'activity' => 'Platformda son 7 gün içinde yüksek hareketlilik',
            'action' => 'Geçmiş aksiyon profiline dayalı yüksek uyum',
        ];

        return $messages[$topFactor] ?? 'Genel kriter eşleşmesi';
    }

    /**
     * Generates a list of all significant contributing reasons.
     */
    private function generateReasonList(array $breakdown): array
    {
        $reasons = [];
        arsort($breakdown);

        $messages = [
            'price' => 'Bütçe beklentisine harika uyum',
            'location' => 'Tam olarak aradığı mahalle/ilçe veya yakın çevresi',
            'features' => 'Talep ettiği sosyal/teknik donatılar ilanla eşleşiyor',
            'rooms' => 'İstediği oda büyüklüğü ve sayısıyla örtüşüyor',
            'reason_category' => 'İstenen gayrimenkul sınıfında bir seçenek', // context7 safe
            'intent' => 'Satın alma motivasyonu yüksek profilli alıcı',
            'activity' => 'Sistemde aktif olarak benzer ilanlarla ilgileniyor',
        ];

        foreach ($breakdown as $factor => $score) {
            // Only add significant factors
            if ($score > 3 && isset($messages[$factor])) {
                $reasons[] = $messages[$factor];
            }
        }

        return array_slice($reasons, 0, 4); // Max 4 reasons
    }

    /**
     * Suggests a concrete sales action to the advisor.
     */
    private function generateSuggestedAction(string $tier, string $urgency): string
    {
        if ($urgency === 'AT_RISK') {
            return "Kaybetmeden yeniden temas kur, ihtiyaç güncellemesi al.";
        }

        if ($tier === 'HOT' && $urgency === 'HIGH_INTENT') {
            return "Kritik Fırsat! Hemen telefonla ulaş, aynı gün gösterim planla.";
        }

        if ($tier === 'HOT' || $urgency === 'ACTIVE_SEARCH') {
            return "Tercihleri çok uygun. Bugün içinde şahsen ara ve randevu talep et.";
        }

        if ($tier === 'WARM') {
            return "WhatsApp veya e-posta ile ilan gönder, 24-48 saat içinde takip araması yap.";
        }

        if ($tier === 'WATCH') {
            return "Portföy bilgilendirmesi yap. Yanında alternatif 1-2 ilan daha sun.";
        }

        return "Düzenli bülten ile haberdar et.";
    }

    /**
     * Assigns a numeric priority for sorting (1 is highest priority).
     */
    private function calculateContactPriority(float $score, string $urgency, float $churnScore): int
    {
        // Absolute priority: AT_RISK or HIGH_INTENT + >85 score
        if ($churnScore > 4) return 1; // Immediate intervention needed
        if ($urgency === 'HIGH_INTENT' && $score >= 85) return 2;
        if ($urgency === 'ACTIVE_SEARCH' && $score >= 70) return 3;
        if ($score >= 85) return 4;
        if ($score >= 70) return 5;
        if ($score >= 55) return 6;

        return 7;
    }

    /**
     * Sorts the final queue array by the calculated contact priority, then by score.
     */
    private function sortByContactPriority(array $matches): array
    {
        usort($matches, function ($a, $b) {
            if ($a['contact_priority'] === $b['contact_priority']) {
                // Secondary sort: Match score descending
                return $b['match_score'] <=> $a['match_score'];
            }
            // Primary sort: Priority (1 is highest, ascending sort)
            return $a['contact_priority'] <=> $b['contact_priority'];
        });

        return $matches;
    }
}
