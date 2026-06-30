<?php

namespace App\Services\Analytics\ReadModels;

use App\Enums\IlanDurumu;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * [Phase 16] Analytics CQRS-lite Read-Model Service
 *
 * Fetches dashboard and reporting data ONLY from denormalized projection tables.
 * Strict SAB Rule: NO heavy joins on `ilanlar`, `kisi_etkilesimler`, or `users` here.
 */
class DashboardProjectionService
{
    /**
     * Get basic statistics for the top-level KPI cards.
     *
     * @param int|null $danismanId Filter by specific agent, or null for global
     * @return array
     */
    public function getKpiSummary(?int $danismanId = null): array
    {
        $query = DB::table('proj_listings')
            ->where('aktiflik_durumu', 1)
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value);

        if ($danismanId) {
            $query->where('danisman_id', $danismanId);
        }

        $activeListingsCount = $query->count();
        $totalPortfolioValue = $query->sum('fiyat');

        return [
            'active_listings' => $activeListingsCount, // context7-ignore
            'portfolio_value' => $totalPortfolioValue,
        ];
    }

    /**
     * Get the recent performance of agents based on denormalized data.
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getAgentLeaderboard(int $limit = 5)
    {
        $currentMonth = now()->format('Y-m');

        return DB::table('proj_agent_performance')
            ->join('users', 'proj_agent_performance.danisman_id', '=', 'users.id')
            ->where('proj_agent_performance.donem', $currentMonth)
            ->where('proj_agent_performance.aktiflik_durumu', 1)
            ->orderByDesc('proj_agent_performance.basari_puani') // context7-ignore
            ->limit($limit)
            ->select(
                'users.name',
                'users.last_name',
                'proj_agent_performance.basari_puani',
                'proj_agent_performance.kapatilan_islem_sayisi'
            )
            ->get();
    }

    /**
     * Get the timeline of active listings for the chart.
     *
     * @param int $days
     * @return \Illuminate\Support\Collection
     */
    public function getActiveListingsTimeline(int $days = 14)
    {
        $startDate = now()->subDays($days)->toDateString();

        return DB::table('proj_kpi_snapshots')
            ->where('danisman_id', null) // Global snapshots
            ->where('tarih', '>=', $startDate)
            ->orderBy('tarih', 'asc') // context7-ignore
            ->select('tarih', 'aktif_ilan_sayisi', 'toplam_portfoy_degeri')
            ->get();
    }
}
