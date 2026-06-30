<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Exceptions\CriticalGovernanceException;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use Illuminate\Http\Request;
use App\Services\Ups\UpsHealthOptimizerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Throwable;

class UpsHealthController extends Controller
{
    /**
     * UPS Sistem Sağlık Paneli
     *
     * Tüm Kategori x Yayın Tipi kombinasyonları için Şablon (UPS) ve Özellik Ataması
     * durumunu görselleştirir (Heatmap).
     */
    public function index()
    {
        // 1. Tüm kategorileri ve altındaki yayın tiplerini (upsTemplate ve features ile) getir
        // Context7: Legacy IlanTemplate yerine UpsTemplate (SSOT) kullanılıyor.
        $categories = IlanKategori::with([
            'yayinTipleri' => function ($q) {
                $q->select('yayin_tipi_sablonlari.*');
            },
            'yayinTipleri.upsTemplate',
            'yayinTipleri.featureAssignments' => function ($q) {
                // Sadece aktif özellik atamalarını say
                $q->where('feature_assignments.aktiflik_durumu', true);
            }
        ])->get();

        // 2. Heatmap kolonları için tüm benzersiz yayın tiplerini al
        $allYayinTipleri = YayinTipiSablonu::select('ad')
            ->distinct()
            ->orderBy('ad') // context7-ignore
            ->pluck('ad')
            ->toArray();

        // 3. Matriks ve Heatmap verisini oluştur
        $healthMatrix = [];
        $heatmapData = [];
        $stats = [
            'total_combinations' => 0,
            'missing_templates' => 0, // Legacy counter (sum of issues)
            'missing_features' => 0,
            'critical_missing' => 0,
            'partial_empty' => 0,
            'whitelist_drop' => 0,
            'healthy_combinations' => 0,
        ];

        foreach ($categories as $category) {
            $heatmapRow = [
                'category_name' => $category->name,
                'category_id' => $category->id,
                'cells' => [],
            ];

            // Tüm hücreleri varsayılan olarak boş başlat
            foreach ($allYayinTipleri as $yayinTipiName) {
                $heatmapRow['cells'][$yayinTipiName] = [
                    'exists' => false,
                    'health_state' => 'empty',
                    'feature_count' => 0,
                ];
            }

            foreach ($category->yayinTipleri as $yayinTipi) {
                $stats['total_combinations']++;

                // UPS SSOT Logic
                $upsTemplateRelation = $yayinTipi->upsTemplate;
                $upsTemplate = $upsTemplateRelation instanceof \Illuminate\Support\Collection
                    ? $upsTemplateRelation->first()
                    : $upsTemplateRelation;
                $featureCount = $yayinTipi->featureAssignments->count();

                // DAP Protocol Risk Buckets
                $riskBucket = 'HEALTHY';
                $healthState = 'healthy';

                if ($upsTemplate === null) {
                    $riskBucket = 'CRITICAL_TEMPLATE_MISSING';
                    $healthState = 'missing_template';
                    $stats['critical_missing']++;
                    $stats['missing_templates']++;
                } elseif (empty($upsTemplate->template_json['ui_ipuclari'])) {
                    $riskBucket = 'PARTIAL_UI_IPUCLARI_EMPTY';
                    $healthState = 'partial_empty';
                    $stats['partial_empty']++;
                    $stats['missing_templates']++;
                } elseif ($featureCount === 0) {
                    // DAP Rule: Feature assignment var ama schema 0 (basitleştirilmiş: count 0)
                    $riskBucket = 'WHITELIST_DROP';
                    $healthState = 'no_features';
                    $stats['missing_features']++;
                    $stats['whitelist_drop']++;
                    $stats['missing_templates']++;
                } else {
                    $stats['healthy_combinations']++;
                }

                // Update heatmap cell
                $heatmapRow['cells'][$yayinTipi->yayin_tipi] = [
                    'exists' => true,
                    'health_state' => $healthState,
                    'risk_bucket' => $riskBucket, // New DAP field
                    'feature_count' => $featureCount,
                    'yayin_tipi_id' => $yayinTipi->id,
                    'template_id' => $upsTemplate?->id,
                ];

                $healthMatrix[] = [
                    'category_name' => $category->name,
                    'category_id' => $category->id,
                    'yayin_tipi_name' => $yayinTipi->yayin_tipi,
                    'yayin_tipi_id' => $yayinTipi->id,
                    'template_exists' => !!$upsTemplate,
                    'template_code' => $upsTemplate?->template_version,
                    'feature_count' => $featureCount,
                    'health_state' => $healthState,
                    'risk_bucket' => $riskBucket,
                ];
            }

            $heatmapData[] = $heatmapRow;
        }

        // Sağlık Skoru Hesabı
        $healthScore = $stats['total_combinations'] > 0
            ? round(($stats['healthy_combinations'] / $stats['total_combinations']) * 100)
            : 0;

        return view('admin.ups.health.index', compact(
            'healthMatrix',
            'heatmapData',
            'allYayinTipleri',
            'stats',
            'healthScore'
        ));
    }

    /**
     * UPS sağlık matrisi için eksik düğümleri optimize eder.
     */
    public function repair(Request $request, \App\Actions\Admin\Ups\RepairUpsHealthAction $action)
    {
        try {
            $result = $action->handle();

            return redirect()
                ->route('admin.ups.health')
                ->with('success', sprintf(
                    'UPS optimizasyonu tamamlandi. Olusturulan: %d, Toplam Dugum: %d, Durum: %s',
                    (int) ($result['created'] ?? 0),
                    (int) ($result['total_nodes'] ?? 0),
                    (string) ($result['durum'] ?? 'UNKNOWN')
                ));
        } catch (CriticalGovernanceException $e) {
            Log::warning('ups_health_repair_blocked', [
                'hata_mesaji' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.ups.health')
                ->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('ups_health_repair_failed', [
                'hata_mesaji' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.ups.health')
                ->with('error', 'UPS optimizasyonu sirasinda beklenmeyen bir hata olustu.');
        }
    }

}
