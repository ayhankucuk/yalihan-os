<?php

namespace App\Services\Matching;

use App\Models\MatchingFeedback;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🎯 MATCH SERVICE (SAB v6.0)
 *
 * Handles match-related business logic, events, and Cortex learning integration.
 * Refactored from MatchController for layer discipline.
 *
 * Context7: yayin_durumu_log
 */
class MatchService
{
    public function __construct(
        protected \App\Services\CRM\MatchingAuthorityService $authorityService
    ) {}

    /**
     * Log matching events for potential learning.
     *
     * @param int $ilanId
     * @param array $matches
     * @return void
     */
    public function logMatchingEvent(int $ilanId, array $matches): void
    {
        // Top 5 eşleşmeyi feedback havuzuna kaydet
        $topMatches = array_slice($matches, 0, 5);

        foreach ($topMatches as $match) {
            $this->authorityService->logPotentialMatch(
                $ilanId,
                $match['talep_id'],
                (float) ($match['match_score'] ?? ($match['skor'] ?? 0)),
                auth()->user()
            );
        }

        Log::info('Match events logged via Authority for Ilan #' . $ilanId, [
            'count' => count($topMatches)
        ]);
    }

    /**
     * Refuse a match and record learning signal for Cortex.
     *
     * @param int $feedbackId
     * @param string $reason
     * @param int $danismanId
     * @return MatchingFeedback
     * @throws \Exception
     */
    public function refuseMatch(int $feedbackId, string $reason, int $danismanId): MatchingFeedback
    {
        return $this->authorityService->recordFeedback(
            $feedbackId,
            'rejected',
            $reason,
            auth()->user()
        );
    }

    /**
     * Get summarized learnings for the dashboard.
     *
     * @return array
     */
    public function getCortexLearnings(): array
    {
        return [
            'total_feedback' => MatchingFeedback::count(),
            'rejected_count' => MatchingFeedback::where('yayin_durumu_log', 'rejected')->count(),
            'accepted_count' => MatchingFeedback::where('yayin_durumu_log', 'accepted')->count(),
            'latest_feedbacks' => MatchingFeedback::with(['ilan', 'talep'])
                ->latest()
                ->take(10)
                ->get()
        ];
    }


    /**
     * Get IDs of properties blocked by a person.
     *
     * @param int $kisiId
     * @return array
     */
    public function getBlockedIlanIds(int $kisiId): array
    {
        return DB::table('ilan_kisi_eslesmeleri')
            ->where('kisi_id', $kisiId)
            ->where('eylem', 'bloke')
            ->pluck('ilan_id')
            ->toArray();
    }
}

