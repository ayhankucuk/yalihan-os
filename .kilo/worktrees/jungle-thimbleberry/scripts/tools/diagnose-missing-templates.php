<?php

namespace scripts;

use App\Models\IlanKategori;
use App\Models\IlanKategoriYayinTipi;
use App\Services\Wizard\WizardContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 🛰️ Property Hub: Deterministic Template Audit Script (Refined)
 *
 * Amaç:
 * 1. "8 Eksik Şablon" durumunu doğrula.
 * 2. Riskli kombinasyonları categorize et.
 * 3. Wizard Step 2 context doğrulaması yap.
 */
class DiagnoseMissingTemplates
{
    public function run()
    {
        echo "🚀 Property Hub: Deterministik Şablon Denetimi Başlıyor...\n";
        echo "--------------------------------------------------------\n";

        // 1. Sadece aktif junction'ları al
        $junctions = IlanKategoriYayinTipi::with(['kategori', 'upsTemplate', 'defaultTemplate'])
            ->where('aktiflik_durumu', 1)
            ->get();

        $report = [
            'total_junctions' => $junctions->count(),
            'missing_legacy_template' => 0,
            'missing_ups_template' => 0,
            'categories' => [
                'Turistik Tesisler' => [],
                'Projeden Satış' => [],
                'Konut Projesi' => [],
                'Diğer' => []
            ],
            'risks' => [
                'CRITICAL' => 0,
                'PARTIAL' => 0,
                'WHITELIST_DROP' => 0,
                'SAFE' => 0
            ],
            'fix_plan' => []
        ];

        $wizardService = app(WizardContextService::class);

        foreach ($junctions as $j) {
            $catId = $j->kategori_id;
            $jId = $j->id;

            // Context Resolver Verisi
            $context = $wizardService->resolve($catId, $jId);
            $templateData = $context['context']['template'] ?? null;
            $featuresData = $context['context']['features'] ?? null;

            $templateFieldsCount = count($templateData['fields'] ?? []);
            $featureSchemaCount = count($featuresData['feature_schema'] ?? []);

            // Şablon Durumu
            $hasUps = ($j->upsTemplate !== null && $j->upsTemplate->aktiflik_durumu == 1);
            $hasLegacy = ($j->defaultTemplate !== null);

            if (!$hasLegacy) $report['missing_legacy_template']++;
            if (!$hasUps) $report['missing_ups_template']++;

            $uiIpuclariEmpty = $hasUps && empty($j->upsTemplate->template_json['ui_ipuclari']);

            // Risk Sınıflandırması
            $risk = 'SAFE';
            if (!$hasUps && !$hasLegacy) {
                $risk = 'CRITICAL';
            } elseif ($hasUps && $uiIpuclariEmpty) {
                $risk = 'PARTIAL';
            } elseif ($featureSchemaCount === 0) {
                $risk = 'WHITELIST_DROP';
            }

            $report['risks'][$risk]++;

            // Kategori Gruplama
            $catName = $j->kategori->name ?? 'Bilinmeyen';
            $group = 'Diğer';
            if (Str::contains($catName, 'Turistik')) $group = 'Turistik Tesisler';
            elseif (Str::contains($catName, 'Projeden Satış')) $group = 'Projeden Satış';
            elseif (Str::contains($catName, 'Konut Projesi')) $group = 'Konut Projesi';

            $itemData = [
                'junction_id' => $j->id,
                'category' => $catName,
                'yayin_tipi' => $j->yayin_tipi,
                'has_template' => ($hasUps || $hasLegacy) ? 'EVET' : 'HAYIR',
                'ups_exists' => $hasUps ? 'EVET' : 'HAYIR',
                'ui_tips' => $hasUps ? ($uiIpuclariEmpty ? 'BOŞ' : 'OK') : 'N/A',
                'feature_schema' => $featureSchemaCount,
                'risk' => $risk
            ];

            $report['categories'][$group][] = $itemData;

            if ($risk === 'CRITICAL' || $risk === 'PARTIAL') {
                $report['fix_plan'][] = $this->generateFixProposal($j, $risk);
            }
        }

        $this->printReport($report);
        $this->exportJson($report);

        echo "\n✅ Rapor hazırlandı: scripts/missing-templates-audit.json\n";
    }

    protected function generateFixProposal($junction, $risk)
    {
        $catName = Str::lower($junction->kategori->name);
        $preset = 'generic';

        if (Str::contains($catName, ['konut', 'daire', 'villa', 'rezidans'])) $preset = 'konut';
        elseif (Str::contains($catName, ['arsa', 'arazi', 'tarla'])) $preset = 'arsa';
        elseif (Str::contains($catName, ['işyeri', 'ofis', 'dükkan'])) $preset = 'isyeri';
        elseif (Str::contains($catName, ['yazlık', 'turistik'])) $preset = 'yazlik';

        return [
            'junction_id' => $junction->id,
            'category' => $junction->kategori->name,
            'yayin_tipi' => $junction->yayin_tipi,
            'risk' => $risk,
            'suggested_preset' => $preset,
            'action' => ($risk === 'CRITICAL' ? 'CREATE_UPS_TEMPLATE' : 'UPDATE_UI_IPUCLARI'),
            'required_features' => ['baslik', 'fiyat', 'aciklama']
        ];
    }

    protected function printReport($report)
    {
        echo "\n📊 ÖZET\n";
        echo "--------------------------------------------------------\n";
        echo "Toplam Junction (Aktif): " . $report['total_junctions'] . "\n";
        echo "Property Hub Eksik (Legacy): " . $report['missing_legacy_template'] . "\n";
        echo "UPS Eksik: " . $report['missing_ups_template'] . "\n";
        echo "\nRISK ANALİZİ:\n";
        echo "🔴 CRITICAL: " . $report['risks']['CRITICAL'] . "\n";
        echo "🟠 PARTIAL: " . $report['risks']['PARTIAL'] . "\n";
        echo "🟡 WHITELIST_DROP: " . $report['risks']['WHITELIST_DROP'] . "\n";
        echo "🟢 SAFE: " . $report['risks']['SAFE'] . "\n";

        foreach ($report['categories'] as $group => $items) {
            if (empty($items)) continue;
            echo "\n📂 " . $group . " (" . count($items) . ")\n";
            echo str_repeat("-", 110) . "\n";
            echo sprintf("%-4s | %-25s | %-15s | %-6s | %-8s | %-8s | %-12s\n",
                "ID", "Kategori", "Yayın Tipi", "Templ", "UPS", "UI_Tips", "Risk");
            echo str_repeat("-", 110) . "\n";
            foreach ($items as $item) {
                echo sprintf("%-4d | %-25s | %-15s | %-6s | %-8s | %-8s | %-12s\n",
                    $item['junction_id'],
                    mb_substr($item['category'], 0, 25),
                    mb_substr($item['yayin_tipi'], 0, 15),
                    $item['has_template'],
                    $item['ups_exists'],
                    $item['ui_tips'],
                    $item['risk']
                );
            }
        }
    }

    protected function exportJson($report)
    {
        file_put_contents(base_path('scripts/missing-templates-audit.json'), json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

// Laravel bootstrap
require_once __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

(new DiagnoseMissingTemplates())->run();
