<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * DashboardProjectionService
 * SAB §1: Service katmanı — Controller iş mantığı içermez.
 * SAB §6: Projection tabloları dışına çıkılamaz.
 * Tüm metodlar null yerine default 0 + meta döner.
 */
class DashboardProjectionService
{
    /**
     * KPI snapshot + gerçek zamanlı proj_listings sayacı
     *
     * @return array{value: int, calisma_durumu: string, last_updated_at: string|null, meta: array}
     */
    public function getKpiSnapshot(): array
    {
        $snapshot = DB::table('proj_kpi_snapshots')
            ->whereNull('danisman_id')
            ->orderByDesc('tarih') // context7-ignore
            ->first();

        $aktifCount = DB::table('proj_listings')
            ->where('yayin_durumu', 1)
            ->count();

        $toplamDeger = (float) DB::table('proj_listings')
            ->where('yayin_durumu', 1)
            ->sum('fiyat');

        return [
            'toplam_portfolio_degeri' => [
                'value'          => $toplamDeger > 0 ? $toplamDeger : ($snapshot->toplam_portfoy_degeri ?? 0),
                'calisma_durumu' => 'ok',
                'last_updated_at' => now()->toISOString(),
                'meta'           => ['para_birimi' => 'TRY'],
            ],
            'aktif_ilan_sayisi' => [
                'value'          => $aktifCount > 0 ? $aktifCount : ($snapshot->aktif_ilan_sayisi ?? 0),
                'calisma_durumu' => 'ok',
                'last_updated_at' => now()->toISOString(),
                'meta'           => [],
            ],
            'ortalama_yas' => [
                'value'          => $snapshot->ortalama_satista_kalma_suresi ?? 0,
                'calisma_durumu' => $snapshot ? 'ok' : 'stale',
                'last_updated_at' => $snapshot->created_at ?? null,
                'meta'           => [],
            ],
            'lead_7g' => [
                'value'          => $snapshot->yeni_talep_sayisi_7_gun ?? 0,
                'calisma_durumu' => $snapshot ? 'ok' : 'stale',
                'last_updated_at' => $snapshot->created_at ?? null,
                'meta'           => [],
            ],
            'donusum_orani' => [
                'value'          => $snapshot->cevirim_orani ?? 0,
                'calisma_durumu' => $snapshot ? 'ok' : 'stale',
                'last_updated_at' => $snapshot->created_at ?? null,
                'meta'           => ['format' => 'percent'],
            ],
        ];
    }

    /**
     * İlan listesi (proj_listings)
     *
     * @param array $filters yayin_durumu, sort, limit
     * @return Collection
     */
    public function getListings(array $filters = []): Collection
    {
        $yayinDurumu = isset($filters['yayin_durumu']) ? (int) $filters['yayin_durumu'] : 1;
        $sort        = $filters['sort'] ?? 'price_desc';
        $limit       = min((int) ($filters['limit'] ?? 50), 200);

        $query = DB::table('proj_listings')
            ->where('yayin_durumu', $yayinDurumu);

        match ($sort) {
            'newest'     => $query->orderByDesc('created_at'), // context7-ignore
            'price_asc'  => $query->orderBy('fiyat'), // context7-ignore
            default      => $query->orderByDesc('fiyat'), // context7-ignore
        };

        return $query->limit($limit)->get();
    }

    /**
     * Aktivite akışı (proj_activity_stream)
     */
    public function getActivity(int $limit = 50): Collection
    {
        return DB::table('proj_activity_stream')
            ->orderByDesc('occurred_at') // context7-ignore
            ->limit(min($limit, 200))
            ->get();
    }

    /**
     * Sistem sağlığı
     *
     * @return array{calisma_durumu: string, value: int, last_updated_at: string, meta: array}
     */
    public function getHealth(): array
    {
        $listingsCount = DB::table('proj_listings')->count();
        $dlqCount      = DB::table('proj_dlq')->count();
        $offsetsCount  = DB::table('proj_event_offsets')->count();

        $calisma = $dlqCount > 0 ? 'stale' : 'ok';

        return [
            'calisma_durumu'  => $calisma,
            'value'           => $listingsCount,
            'last_updated_at' => now()->toISOString(),
            'meta'            => [
                'listings_synced'   => $listingsCount,
                'dlq_boyutu'        => $dlqCount,
                'islenen_event'     => $offsetsCount,
                'aciklama'          => $dlqCount > 0
                    ? "{$dlqCount} başarısız event yeniden kuyruğa alınmalı."
                    : 'Sistem sağlıklı.',
            ],
        ];
    }

    /**
     * Günlük lead trendi (proj_leads_daily)
     */
    public function getLeadsTrend(int $days = 7): Collection
    {
        return DB::table('proj_leads_daily')
            ->orderByDesc('tarih') // context7-ignore
            ->limit($days)
            ->get(['tarih', 'lead_count', 'converted_count']);
    }
}
