<?php

use App\Models\IlanKategori;
use App\Models\IlanKategoriYayinTipi;
use App\Models\Deprecated\IlanTemplate;
use Illuminate\Support\Facades\File;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function performAudit() {
    echo "Starting Template Coverage Audit...\n";

    $activeCategories = IlanKategori::where('aktiflik_durumu', true)->whereNull('parent_id')->get();
    $activeSubCategories = IlanKategori::where('aktiflik_durumu', true)->whereNotNull('parent_id')->get();
    $activeYayinTipleri = IlanKategoriYayinTipi::where('aktiflik_durumu', true)->get();

    $matrix = [];
    $missing = [];
    $zeroFg = [];

    // Audit focused on combinations from the controller/wizard perspective
    // Usually: Ana Kategori -> Alt Kategori -> Yayın Tipi
    foreach ($activeSubCategories as $altCat) {
        $anaCat = $altCat->parent;
        if (!$anaCat || !$anaCat->aktiflik_durumu) continue;

        foreach ($activeYayinTipleri as $yt) {
            // Check if this YT is linked to this category or generally active
            // In the current system, templates are linked to kategori_id (alt) and yayin_tipi (string name or ID)
            // The template resolution usually uses IlanKategoriYayinTipi bridging.

            $template = IlanTemplate::where('kategori_id', $altCat->id)
                ->where('yayin_tipi_id', $yt->id)
                ->first();

            $key = "{$anaCat->name} > {$altCat->name} [{$yt->yayin_tipi}]";
            
            if ($template) {
                $fgCount = count($template->feature_groups ?? []);
                $matrix[$key] = [
                    'status' => 'OK',
                    'template_id' => $template->id,
                    'fg_count' => $fgCount
                ];
                if ($fgCount === 0) {
                    $zeroFg[] = $key;
                }
            } else {
                $matrix[$key] = [
                    'status' => 'MISSING'
                ];
                $missing[] = [
                    'ana_kategori' => $anaCat->name,
                    'alt_kategori' => $altCat->name,
                    'yayin_tipi' => $yt->yayin_tipi,
                    'ana_kategori_id' => $anaCat->id,
                    'alt_kategori_id' => $altCat->id,
                    'yayin_tipi_id' => $yt->id
                ];
            }
        }
    }

    echo "Audit Complete. Found " . count($missing) . " missing templates.\n";
    
    File::put('missing_templates_phase4.json', json_encode($missing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Output summary
    echo "\nSummary matrix written to missing_templates_phase4.json\n";
    if (!empty($zeroFg)) {
        echo "Alert: Templates with 0 feature groups: " . implode(', ', $zeroFg) . "\n";
    }
}

performAudit();
