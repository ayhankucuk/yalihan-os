<?php

namespace App\Console\Commands;

use App\Models\FeatureAssignment;
use App\Models\YayinTipiSablonu;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Residential Spine Restoration Command
 *
 * Context7: Konut Neural Sync - Satılık & Kiralık Template Binding
 *
 * @package App\Console\Commands
 */
class KonutNeuralSyncCommand extends Command
{
    protected $signature = 'konut:neural-sync';
    protected $description = '🏠 Residential Spine Restoration - Konut özelliklerini şablonlara bağla';

    public function handle(): int
    {
        $this->info('🏠 RESIDENTIAL SPINE RESTORATION');
        $this->info('Context7: Konut Neural Sync başlatılıyor...');
        $this->newLine();

        $satilik = YayinTipiSablonu::where('slug', 'satilik')->first();
        $kiralik = YayinTipiSablonu::where('slug', 'kiralik')->first();

        if (!$satilik || !$kiralik) {
            $this->error('❌ Satılık veya Kiralık şablonu bulunamadı!');
            return self::FAILURE;
        }

        // Satılık için özellikler
        $satilikFeatures = [
            // Zorunlu (is_required = 1)
            1 => 1,  // brut-alan
            11 => 1, // oda-sayisi
            12 => 1, // banyo-sayisi
            7 => 1,  // bulundugu-kat
            6 => 1,  // kat-sayisi
            // Opsiyonel (is_required = 0)
            2 => 0,  // net-alan
            4 => 0,  // bina-yasi
            15 => 0, // isitma-tipi
            18 => 0, // asansor
            19 => 0, // otopark
        ];

        $this->info('📌 Satılık şablonuna özellikler bağlanıyor...');
        foreach ($satilikFeatures as $featureId => $isRequired) {
            // Governance Enforcement: DB bypass kaldırıldı.
            // updateOrCreate → Observer::created/updated → invalidateForJunction + ChangeLog
            FeatureAssignment::updateOrCreate(
                [
                    'feature_id'      => $featureId,
                    'assignable_type' => YayinTipiSablonu::class,
                    'assignable_id'   => $satilik->id,
                ],
                [
                    'is_required'     => (bool) $isRequired,
                    'is_visible'      => true,
                    'aktiflik_durumu' => true,
                    'source_type'     => 'neural_sync',
                ]
            );
        }
        $this->info("  ✅ {$satilik->ad}: " . count($satilikFeatures) . ' özellik bağlandı');

        // Kiralık için özellikler
        $kiralikFeatures = [
            // Zorunlu (is_required = 1)
            1 => 1,  // brut-alan
            11 => 1, // oda-sayisi
            12 => 1, // banyo-sayisi
            7 => 1,  // bulundugu-kat
            22 => 1, // esyali (KİRALIKTA ZORUNLU!)
            // Opsiyonel (is_required = 0)
            2 => 0,  // net-alan
            6 => 0,  // kat-sayisi
            4 => 0,  // bina-yasi
            15 => 0, // isitma-tipi
            18 => 0, // asansor
            19 => 0, // otopark
        ];

        $this->info('📌 Kiralık şablonuna özellikler bağlanıyor...');
        foreach ($kiralikFeatures as $featureId => $isRequired) {
            // Governance Enforcement: DB bypass kaldırıldı.
            FeatureAssignment::updateOrCreate(
                [
                    'feature_id'      => $featureId,
                    'assignable_type' => YayinTipiSablonu::class,
                    'assignable_id'   => $kiralik->id,
                ],
                [
                    'is_required'     => (bool) $isRequired,
                    'is_visible'      => true,
                    'aktiflik_durumu' => true,
                    'source_type'     => 'neural_sync',
                ]
            );
        }
        $this->info("  ✅ {$kiralik->ad}: " . count($kiralikFeatures) . ' özellik bağlandı');

        $this->newLine();
        $this->info('✅ RESIDENTIAL SPINE RESTORATION TAMAMLANDI!');

        // Verification
        $this->newLine();
        $this->info('🔍 Doğrulama:');
        $verification = DB::select("
            SELECT
                yts.ad as 'Şablon',
                COUNT(fa.id) as 'Toplam',
                SUM(CASE WHEN fa.is_required = 1 THEN 1 ELSE 0 END) as 'Zorunlu',
                SUM(CASE WHEN fa.is_required = 0 THEN 1 ELSE 0 END) as 'Opsiyonel'
            FROM yayin_tipi_sablonlari yts
            LEFT JOIN feature_assignments fa
                ON fa.assignable_type = 'App\\\\Models\\\\YayinTipiSablonu'
                AND fa.assignable_id = yts.id
            WHERE yts.slug IN ('satilik', 'kiralik')
            GROUP BY yts.id, yts.ad
            ORDER BY yts.id
        ");

        foreach ($verification as $row) {
            $this->line(sprintf(
                "  %s: %d toplam (%d zorunlu + %d opsiyonel)",
                $row->Şablon,
                $row->Toplam,
                $row->Zorunlu,
                $row->Opsiyonel
            ));
        }

        return self::SUCCESS;
    }
}
