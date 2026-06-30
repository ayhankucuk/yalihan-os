<?php

namespace App\Services\Matching;

use App\Models\MatchingFeedback;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🎯 MATCHING FEEDBACK SERVICE (SAB v6.0)
 *
 * Handles administrative feedback lifecycle, statistics, and cache management.
 *
 * Context7: yayin_durumu_log
 */
class MatchingFeedbackService
{
    /**
     * Store or update matching feedback.
     *
     * @param array $data
     * @param int $danismanId
     * @return MatchingFeedback
     */
    public function storeFeedback(array $data, int $danismanId): MatchingFeedback
    {
        $feedback = MatchingFeedback::updateOrCreate(
            [
                'talep_id' => $data['talep_id'],
                'ilan_id' => $data['ilan_id'],
                'danisman_id' => $danismanId,
            ],
            [
                'feedback_tipi' => $data['feedback_tipi'],
                'cortex_score_at_time' => $data['cortex_score_at_time'],
                'match_breakdown' => $data['match_breakdown'] ?? null,
                'danisman_notu' => $data['danisman_notu'] ?? null,
                'yayin_durumu_log' => 'accepted' // Feedback verildiğine göre kabul edildi
            ]
        );

        // Cache invalidate for learning triggers
        Cache::forget('matching_weights_optimized');

        return $feedback;
    }

    /**
     * Get feedback history for a danışman.
     *
     * @param int $danismanId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHistory(int $danismanId, int $limit = 20)
    {
        return MatchingFeedback::with(['talep', 'ilan'])
            ->where('danisman_id', $danismanId)
            ->orderByDesc('created_at') // context7-ignore
            ->limit($limit)
            ->get();
    }

    /**
     * Get aggregated feedback stats.
     *
     * @param int $danismanId
     * @return array
     */
    public function getStats(int $danismanId): array
    {
        $baseQuery = MatchingFeedback::where('danisman_id', $danismanId);

        $total = (clone $baseQuery)->count();
        $positive = (clone $baseQuery)->whereIn('feedback_tipi', ['thumbs_up', 'perfect_match'])->count();

        return [
            'total' => $total,
            'positive' => $positive,
            'negative' => (clone $baseQuery)->where('feedback_tipi', 'thumbs_down')->count(),
            'perfect_matches' => (clone $baseQuery)->where('feedback_tipi', 'perfect_match')->count(),
            'avg_score' => (int) (clone $baseQuery)->avg('cortex_score_at_time'),
            'success_rate' => $total > 0 ? round(($positive / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Mark a result as created (success signal).
     *
     * @param int $id
     * @param int $currentDanismanId
     * @return MatchingFeedback
     * @throws \Exception
     */
    public function markResultCreated(int $id, int $currentDanismanId): MatchingFeedback
    {
        $feedback = MatchingFeedback::findOrFail($id);

        if ($feedback->danisman_id !== $currentDanismanId) {
            throw new \Exception('Bu işlem için yetkiniz yok.');
        }

        $feedback->update([
            'sonuc_olusturuldu' => true,
            'sonuc_tarihi' => now(),
        ]);

        return $feedback;
    }

    /**
     * Get high-score matches for a group of listings.
     *
     * @param array $ilanIds
     * @param int $minScore
     * @return \Illuminate\Support\Collection
     */
    public function getHighScoreMatches(array $ilanIds, int $minScore = 95): \Illuminate\Support\Collection
    {
        return MatchingFeedback::whereIn('ilan_id', $ilanIds)
            ->where('cortex_score_at_time', '>=', $minScore)
            ->where('sonuc_olusturuldu', false)
            ->get();
    }
}

