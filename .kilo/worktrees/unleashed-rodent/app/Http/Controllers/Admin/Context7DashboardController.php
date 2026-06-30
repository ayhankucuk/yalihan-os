<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Cache\CacheHelper;

class Context7DashboardController extends Controller
{
    public function index()
    {
        $minute = now()->format('YmdHi');
        $rpm = (int) CacheHelper::get('api', 'rpm', 0, ['minute' => $minute]);
        $sum = (int) CacheHelper::get('api', 'duration_sum', 0, ['minute' => $minute]);
        $count = (int) CacheHelper::get('api', 'duration_count', 0, ['minute' => $minute]);
        $avg = $count > 0 ? (int) round($sum / $count) : 0;

        $bootTs = (int) CacheHelper::get('system', 'boot_ts', 0);
        $uptime = $bootTs > 0 ? now()->timestamp - $bootTs : 0;

        $path = base_path('.sab/authority.json');
        $rules = [
            'version' => null,
            'forbidden_count' => 0,
            'required_count' => 0,
        ];
        if (file_exists($path)) {
            $json = json_decode(file_get_contents($path), true);
            $rules['version'] = $json['version'] ?? null;
            $rules['forbidden_count'] = isset($json['forbidden_patterns']) && is_array($json['forbidden_patterns']) ? count($json['forbidden_patterns']) : 0;
            $rules['required_count'] = isset($json['required_patterns']) && is_array($json['required_patterns']) ? count($json['required_patterns']) : 0;
            $rules['forbidden'] = $json['forbidden_patterns'] ?? [];
            $rules['required'] = $json['required_patterns'] ?? [];
        }

        // Percentile ve trendleri API uçlarıyla aynı hesapla (son 5dk over window)
        $win5 = [];
        for ($i = 0; $i < 5; $i++) {
            $win5[] = now()->copy()->subMinutes($i)->format('YmdHi');
        }
        $bks = ['b_0_100', 'b_100_200', 'b_200_500', 'b_500_1000', 'b_1000_plus'];
        $bounds = [100, 200, 500, 1000, 1500];
        $counts = [];
        foreach ($bks as $i => $bk) {
            $counts[$i] = 0;
            foreach ($win5 as $m) {
                $counts[$i] += (int) CacheHelper::get('api', $bk, 0, ['minute' => $m]);
            }
        }
        $total = array_sum($counts);
        $calc = function (float $p) use ($counts, $bounds, $total) {
            if ($total === 0) {
                return 0;
            } $t = $total * $p;
            $r = 0;
            foreach ($counts as $i => $c) {
                $r += $c;
                if ($r >= $t) {
                    return $bounds[$i];
                }
            }

return end($bounds);
        };
        $p95 = (int) $calc(0.95);
        $p99 = (int) $calc(0.99);

        // 15/60dk rpm ve avg
        $win15 = [];
        for ($i = 0; $i < 15; $i++) {
            $win15[] = now()->copy()->subMinutes($i)->format('YmdHi');
        }
        $rpm15 = 0;
        $sum15 = 0;
        $cnt15 = 0;
        foreach ($win15 as $m) {
            $rpm15 += (int) CacheHelper::get('api', 'rpm', 0, ['minute' => $m]);
            $sum15 += (int) CacheHelper::get('api', 'duration_sum', 0, ['minute' => $m]);
            $cnt15 += (int) CacheHelper::get('api', 'duration_count', 0, ['minute' => $m]);
        }
        $avg15 = $cnt15 > 0 ? (int) round($sum15 / $cnt15) : 0;
        $win60 = [];
        for ($i = 0; $i < 60; $i++) {
            $win60[] = now()->copy()->subMinutes($i)->format('YmdHi');
        }
        $rpm60 = 0;
        $sum60 = 0;
        $cnt60 = 0;
        foreach ($win60 as $m) {
            $rpm60 += (int) CacheHelper::get('api', 'rpm', 0, ['minute' => $m]);
            $sum60 += (int) CacheHelper::get('api', 'duration_sum', 0, ['minute' => $m]);
            $cnt60 += (int) CacheHelper::get('api', 'duration_count', 0, ['minute' => $m]);
        }
        $avg60 = $cnt60 > 0 ? (int) round($sum60 / $cnt60) : 0;

        return view('admin.context7.index', [
            'metrics' => [
                'rpm' => $rpm,
                'avg_ms' => $avg,
                'uptime' => $uptime,
                'p95_ms' => $p95,
                'p99_ms' => $p99,
                'rpm15' => $rpm15,
                'avg15' => $avg15,
                'rpm60' => $rpm60,
                'avg60' => $avg60,
            ],
            'rules' => $rules,
        ]);
    }
}
