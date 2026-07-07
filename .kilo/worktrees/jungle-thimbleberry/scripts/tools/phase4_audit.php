<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\Deprecated\IlanTemplate;

function runAudit() {
    echo "Starting Phase 4 Template Coverage Audit...\n";

    // 1. Get Alt Kategoriler (seviye 1) and their parents
    $kategoriler = IlanKategori::where('seviye', 1)
        ->where('aktiflik_durumu', true)
        ->with('parent')
        ->get();

    // 2. Get Yayin Tipleri (from bridge table which is effectively our publication types registry)
    $yayinTipleri = YayinTipiSablonu::where('aktiflik_durumu', true)
        ->select('yayin_tipi', 'id')
        ->distinct()
        ->get();

    $report = [
        'summary' => [
            'total_combinations' => 0,
            'covered' => 0,
            'low_quality' => 0,
            'missing' => 0,
        ],
        'details' => [],
        'missing_list' => []
    ];

    foreach ($kategoriler as $kat) {
        foreach ($yayinTipleri as $yt) {
            $report['summary']['total_combinations']++;
            
            // Note: In our system, templates are linked by kategori_id and yayin_tipi_id (which refers to YayinTipiSablonu.id)
            $template = IlanTemplate::where('kategori_id', $kat->id)
                ->where('yayin_tipi_id', $yt->id)
                ->first();

            $mevcutluk_durumu = 'MISSING';
            $fgCount = 0;

            if ($template) {
                // Decode feature_groups (it might be string or array depending on casts)
                $fgs = $template->feature_groups;
                if (is_string($fgs)) {
                    $fgs = json_decode($fgs, true) ?: [];
                }
                $fgCount = count($fgs);

                if ($fgCount > 0) {
                    $mevcutluk_durumu = 'COVERED';
                    $report['summary']['covered']++;
                } else {
                    $mevcutluk_durumu = 'LOW_QUALITY';
                    $report['summary']['low_quality']++;
                }
            } else {
                $report['summary']['missing']++;
                $report['missing_list'][] = [
                    'kategori_id' => $kat->id,
                    'kategori_adi' => $kat->name,
                    'parent_adi' => $kat->parent ? $kat->parent->name : 'N/A',
                    'yayin_tipi_id' => $yt->id,
                    'yayin_tipi_adi' => $yt->yayin_tipi // From YayinTipiSablonu
                ];
            }

            $report['details'][] = [
                'kategori' => ($kat->parent->name ?? 'N/A') . " > {$kat->name}",
                'yayin_tipi' => $yt->yayin_tipi,
                'gecerlilik_durumu' => $mevcutluk_durumu,
                'fg_count' => $fgCount
            ];
        }
    }

    file_put_contents(__DIR__ . '/template_coverage_report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    file_put_contents(__DIR__ . '/missing_templates_phase4.json', json_encode($report['missing_list'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo "Audit complete.\n";
    echo "Total: {$report['summary']['total_combinations']}\n";
    echo "Covered: {$report['summary']['covered']}\n";
    echo "Low Quality: {$report['summary']['low_quality']}\n";
    echo "Missing: {$report['summary']['missing']}\n";
    echo "Reports generated: scripts/template_coverage_report.json, scripts/missing_templates_phase4.json\n";
}

runAudit();
