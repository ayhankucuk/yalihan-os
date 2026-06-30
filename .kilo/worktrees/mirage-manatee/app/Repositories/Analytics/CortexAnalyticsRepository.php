<?php

namespace App\Repositories\Analytics;

use Illuminate\Support\Facades\DB;

class CortexAnalyticsRepository
{
    public function getDanismanLeaderboard(int $limit = 50, string $period = 'all')
    {
        $query = DB::table('danismanlar_performance_metrics as dpm')
            ->join('users as u', 'dpm.danisman_id', '=', 'u.id')
            ->where('dpm.aktiflik_durumu', true)
            ->where('u.aktiflik_durumu', true);

        if ($period === 'month') {
            $query->whereBetween('dpm.created_at', [now()->startOfMonth(), now()->endOfMonth()]);
        } elseif ($period === 'week') {
            $query->whereBetween('dpm.created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        }

        $results = $query
            ->select(
                'u.id as danisman_id',
                'u.ad_soyad'
            )
            ->groupBy('u.id', 'u.ad_soyad')
            ->get();

        // Her danışman için aggregate işlemlerini PHP tarafında hesapla
        foreach ($results as $row) {
            $metrics = DB::table('danismanlar_performance_metrics as dpm')
                ->where('dpm.danisman_id', $row->danisman_id)
                ->where('dpm.aktiflik_durumu', true);
            if ($period === 'month') {
                $metrics->whereBetween('dpm.created_at', [now()->startOfMonth(), now()->endOfMonth()]);
            } elseif ($period === 'week') {
                $metrics->whereBetween('dpm.created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            }
            $row->average_score = round($metrics->avg('overall_score'), 2);
            $row->total_ilanlar = $metrics->distinct('ilan_id')->count('ilan_id');
            $row->bosch_count = $metrics->where('bosch_m2_score', '>', 0)->count();
            $row->flir_count = $metrics->where('flir_thermal_score', '>', 0)->count();
        }

        // average_score'a göre sıralama ve limit
        $sorted = $results->sortByDesc('average_score')->values()->take($limit);
        return $sorted;
    }

    public function getLocationPerformance(string $startDate, int $limit = 5)
    {
        $results = DB::table('ilan_goruntulenme_gunluk as v')
            ->join('ilanlar as i', 'v.ilan_id', '=', 'i.id')
            ->leftJoin('iller as il', 'i.il_id', '=', 'il.id')
            ->where('v.tarih', '>=', $startDate)
            ->selectRaw('il.il_adi as name, SUM(v.adet) as views, COUNT(DISTINCT i.id) as listings, ? as _dummy', [1])
            ->groupBy('il.il_adi')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
        // Fallback: name null ise 'Lokasyon' yaz
        foreach ($results as $row) {
            if (is_null($row->name)) {
                $row->name = 'Lokasyon';
            }
        }
        return $results;
    }

    public function getCategoryPerformance(string $startDate, int $limit = 5)
    {
        $results = DB::table('ilan_goruntulenme_gunluk as v')
            ->join('ilanlar as i', 'v.ilan_id', '=', 'i.id')
            ->leftJoin('ilan_kategorileri as k', 'i.ana_kategori_id', '=', 'k.id')
            ->where('v.tarih', '>=', $startDate)
            ->selectRaw('k.name as name, SUM(v.adet) as views, COUNT(DISTINCT i.id) as listings, ? as _dummy', [1])
            ->groupBy('k.name')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
        // Fallback: name null ise 'Kategori' yaz
        foreach ($results as $row) {
            if (is_null($row->name)) {
                $row->name = 'Kategori';
            }
        }
        return $results;
    }

    public function getCoreStats(): array
    {
        return [
            'total_listings' => DB::table('ilanlar')->count(),
            'active_listings' => DB::table('ilanlar')->where('yayin_durumu', 'active')->count(),
            'kategori_count' => DB::table('ilan_kategorileri')->count(),
            'feature_count' => DB::table('features')->count(),
            'arsa_properties' => DB::table('ilan_arsa_details')->count(),
            'tourism_properties' => DB::table('ilan_turizm_details')->count(),
        ];
    }

    public function getVisualAutomationScores()
    {
        return DB::table('ilanlar')
            ->whereNotNull('additional_metadata->cortex_ai->visual_analysis')
            ->select('additional_metadata->cortex_ai->visual_analysis->automation_score as automation_score')
            ->pluck('automation_score')
            ->map(fn($s) => (int)$s);
    }
}
