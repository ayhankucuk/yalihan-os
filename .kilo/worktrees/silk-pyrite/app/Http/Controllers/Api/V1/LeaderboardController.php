<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Analytics\CortexAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __construct(
        private readonly CortexAnalyticsService $analyticsService
    ) {}

    public function leaderboard(Request $request): JsonResponse
    {
        try {
            $limit = $request->integer('limit', 50);
            $period = $request->string('period', 'all');

            $leaderboard = $this->analyticsService->getDanismanLeaderboard($limit, $period);

            $ranked = $leaderboard->map(function($item, $index) {
                $avgScore = (float) $item->average_score;
                return [
                    'rank' => $index + 1,
                    'danisman_id' => $item->danisman_id,
                    'ad_soyad' => $item->ad_soyad,
                    'average_score' => round($avgScore, 2),
                    'score_badge' => $this->getScoreBadge($avgScore),
                    'total_ilanlar' => $item->total_ilanlar,
                    'bosch_count' => $item->bosch_count,
                    'flir_count' => $item->flir_count,
                    'bosch_usage_percent' => $item->total_ilanlar > 0
                        ? round(($item->bosch_count / $item->total_ilanlar) * 100, 2)
                        : 0,
                    'flir_usage_percent' => $item->total_ilanlar > 0
                        ? round(($item->flir_count / $item->total_ilanlar) * 100, 2)
                        : 0,
                    'badge_details' => $this->getBadgeDetails($item),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $period,
                    'total_danismanlar' => $ranked->count(),
                    'leaderboard' => $ranked->values(),
                ],
                'message' => 'Leaderboard başarıyla yüklendi',
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function getDanismanRank(int $danismanId): JsonResponse
    {
        try {
            $rankData = $this->analyticsService->getDanismanRankData($danismanId);

            if ($rankData['score'] === null) {
                return response()->json(['success' => false, 'error' => 'Danışmanın performance verisi yok'], 404);
            }

            $danismanData = User::find($danismanId);

            return response()->json([
                'success' => true,
                'data' => [
                    'danisman_id' => $danismanId,
                    'ad_soyad' => $danismanData->ad_soyad ?? 'Unknown',
                    'average_score' => round($rankData['score'], 2),
                    'rank' => $rankData['rank'],
                    'score_badge' => $this->getScoreBadge($rankData['score']),
                    'message' => "Sıradaki yeri: #" . $rankData['rank'],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    private function getScoreBadge(float $score): string
    {
        return match (true) {
            $score >= 90 => '⭐ Teknik Lider',
            $score >= 80 => '🥇 Uzman',
            $score >= 70 => '🥈 Deneyimli',
            $score >= 60 => '🥉 Profesyonel',
            default => '📊 Gelişen',
        };
    }

    private function getBadgeDetails($item): array
    {
        $total = $item->total_ilanlar;
        $boschPercent = $total > 0 ? ($item->bosch_count / $total) * 100 : 0;
        $flirPercent = $total > 0 ? ($item->flir_count / $total) * 100 : 0;
        $avgScore = (float) $item->average_score;

        $badges = [];
        if ($avgScore >= 90) $badges[] = '⭐ Teknik Lider';
        if ($boschPercent >= 75) $badges[] = '🔧 Bosch Uzmanı';
        if ($flirPercent >= 60) $badges[] = '🌡️ FLIR Sertifikalı';
        if ($boschPercent >= 70 && $flirPercent >= 50) $badges[] = '🛡️ Tam Donanımlı';

        return [
            'badges' => $badges,
            'bosch_expert' => $boschPercent >= 75,
            'flir_certified' => $flirPercent >= 60,
            'fully_equipped' => $boschPercent >= 70 && $flirPercent >= 50,
        ];
    }
}
