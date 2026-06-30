<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\Dashboard\DashboardProjectionService;
use App\Services\Listing\ListingScoreService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * DashboardCqrsController
 * SAB §3: Controller iş mantığı içermez — yalnızca yönlendirme.
 * Tüm sorgular DashboardProjectionService üzerinden geçer.
 */
class DashboardCqrsController extends Controller
{
    public function __construct(
        private readonly DashboardProjectionService $service,
        private readonly ListingScoreService $scoreService
    ) {}

    /**
     * KPI verilerini döner (projection-only)
     */
    public function getKpis(Request $request): JsonResponse
    {
        $data = $this->service->getKpiSnapshot();

        return response()->json([
            'islem_durumu' => 'ok',
            'data'         => $data,
        ]);
    }

    /**
     * İlan listesi (projection-only, pagination destekli) + Score Breakdown
     */
    public function getListings(Request $request): JsonResponse
    {
        $filters = $request->only(['yayin_durumu', 'sort', 'limit']);
        $data    = $this->service->getListings($filters);

        // Completion & Quality mapping
        $ilanIds = $data->pluck('id')->toArray();
        if (!empty($ilanIds)) {
            $ilanlar = Ilan::with('fotograflar')->whereIn('id', $ilanIds)->get()->keyBy('id');

            $data->transform(function ($item) use ($ilanlar) {
                if ($ilan = $ilanlar->get($item->id)) {
                    $item->completion_score = $this->scoreService->computeCompletionScore($ilan);
                    $item->quality_score    = $this->scoreService->computeQualityScore($ilan);
                    $item->breakdown        = $this->scoreService->computeBreakdown($ilan);
                }
                return $item;
            });
        }

        return response()->json([
            'islem_durumu' => 'ok',
            'data'         => $data,
            'meta'         => ['count' => $data->count()],
        ]);
    }

    /**
     * Aktivite akışı (projection-only)
     */
    public function getActivity(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 50);
        $data  = $this->service->getActivity($limit);

        return response()->json([
            'islem_durumu' => 'ok',
            'data'         => $data,
        ]);
    }

    /**
     * Read Model sistem sağlığı
     */
    public function health(): JsonResponse
    {
        $health = $this->service->getHealth();

        $httpSifir = $health['calisma_durumu'] === 'ok' ? 200 : 207;

        return response()->json([
            'islem_durumu' => $health['calisma_durumu'],
            'value'        => $health['value'],
            'last_updated_at' => $health['last_updated_at'],
            'meta'         => $health['meta'],
        ], $httpSifir);
    }

    /**
     * Günlük lead trendi
     */
    public function getLeadsTrend(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 7);
        $data = $this->service->getLeadsTrend($days);

        return response()->json([
            'islem_durumu' => 'ok',
            'data'         => $data,
        ]);
    }
}
