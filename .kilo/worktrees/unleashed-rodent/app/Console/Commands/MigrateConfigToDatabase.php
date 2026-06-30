<?php

namespace App\Console\Commands;

use App\Models\ConfigOption;
use App\Models\IlanKategori;
use App\Helpers\ConfigOptionHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Config Dosyasını Database'e Aktar
 *
 * Mevcut config/yali_options.php dosyasındaki tüm seçenekleri
 * database'e aktarır
 * Context7: C7-CONFIG-MIGRATION-2025-12-15
 */
class MigrateConfigToDatabase extends Command
{
    protected $signature = 'config:migrate-to-database {--force : Mevcut kayıtları sil ve yeniden oluştur}';

    protected $description = 'Mevcut config/yali_options.php dosyasını database\'e aktar';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Config seçenekleri database\'e aktarılıyor...');

        if ($this->option('force')) {
            $this->warn('⚠️  Mevcut tüm config seçenekleri silinecek!');
            if (!$this->confirm('Devam etmek istediğinize emin misiniz?')) {
                $this->info('İşlem iptal edildi.');
                return;
            }
            ConfigOption::truncate();
            $this->info('✅ Mevcut kayıtlar silindi.');
        }

        $config = config('yali_options');

        $mappings = $this->getConfigMappings();

        $bar = $this->output->createProgressBar(count($mappings));
        $bar->start();

        foreach ($mappings as $mapping) {
            $optionKey = $mapping['key'];
            $optionValue = $config[$optionKey] ?? null;

            if ($optionValue === null) {
                $bar->advance();
                continue;
            }

            // Option type'ı belirle
            $optionType = $this->determineOptionType($optionValue);

            ConfigOptionHelper::set(
                $optionKey,
                $optionValue,
                $optionType,
                $mapping['kategori_id'] ?? null,
                $mapping['yayin_tipi_id'] ?? null,
                [
                    'label' => $mapping['label'] ?? null,
                    'description' => $mapping['description'] ?? null,
                    'icon' => $mapping['icon'] ?? null,
                    'status' => true,
                    'display_order' => $mapping['display_order'] ?? 0,
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('✅ Config seçenekleri başarıyla database\'e aktarıldı!');
        $this->info('📊 Toplam: ' . count($mappings) . ' config seçeneği işlendi.');
    }

    /**
     * Config key'lerini kategori ve yayın tipi ile eşleştir
     */
    private function getConfigMappings()
    {
        return [
            // Arsa Kategorisi
            ['key' => 'imar_durumu', 'kategori_id' => 1, 'label' => 'İmar Durumu Seçenekleri', 'icon' => '📋', 'display_order' => 1],
            ['key' => 'kaks_ranges', 'kategori_id' => 1, 'label' => 'KAKS Aralıkları', 'icon' => '📐', 'display_order' => 2],
            ['key' => 'taks_ranges', 'kategori_id' => 1, 'label' => 'TAKS Aralıkları', 'icon' => '📐', 'display_order' => 3],
            ['key' => 'gabari_ranges', 'kategori_id' => 1, 'label' => 'Gabari Aralıkları', 'icon' => '📏', 'display_order' => 4],
            ['key' => 'altyapi', 'kategori_id' => 1, 'label' => 'Altyapı Seçenekleri', 'icon' => '⚡', 'display_order' => 5],
            ['key' => 'arsa_tipleri', 'kategori_id' => 1, 'label' => 'Arsa Tipleri', 'icon' => '🏗️', 'display_order' => 6],
            ['key' => 'yola_cephe_tipleri', 'kategori_id' => 1, 'label' => 'Yola Cephe Tipleri', 'icon' => '📍', 'display_order' => 7],
            ['key' => 'parsel_nitelikleri', 'kategori_id' => 1, 'label' => 'Parsel Nitelikleri', 'icon' => '🗺️', 'display_order' => 8],

            // Konut Kategorisi
            ['key' => 'oda_sayisi_options', 'kategori_id' => 2, 'label' => 'Oda Sayısı Seçenekleri', 'icon' => '🏠', 'display_order' => 1],
            ['key' => 'banyo_sayisi_options', 'kategori_id' => 2, 'label' => 'Banyo Sayısı Seçenekleri', 'icon' => '🚿', 'display_order' => 2],
            ['key' => 'salon_sayisi_options', 'kategori_id' => 2, 'label' => 'Salon Sayısı Seçenekleri', 'icon' => '🛋️', 'display_order' => 3],
            ['key' => 'isitma_tipi_options', 'kategori_id' => 2, 'label' => 'Isıtma Tipi Seçenekleri', 'icon' => '🔥', 'display_order' => 4],
            ['key' => 'esyali_options', 'kategori_id' => 2, 'label' => 'Eşyalı Seçenekleri', 'icon' => '🛋️', 'display_order' => 5],
            ['key' => 'tapu_tipi_options', 'kategori_id' => 2, 'label' => 'Tapu Tipi Seçenekleri', 'icon' => '📄', 'display_order' => 6],

            // Yazlık Kategorisi
            ['key' => 'check_in_hours', 'kategori_id' => 5, 'label' => 'Check-in Saatleri', 'icon' => '🕐', 'display_order' => 1],
            ['key' => 'check_out_hours', 'kategori_id' => 5, 'label' => 'Check-out Saatleri', 'icon' => '🕐', 'display_order' => 2],
            ['key' => 'iptal_politikasi_options', 'kategori_id' => 5, 'label' => 'İptal Politikası Seçenekleri', 'icon' => '📋', 'display_order' => 3],
            ['key' => 'sezon_tipleri', 'kategori_id' => 5, 'label' => 'Sezon Tipleri', 'icon' => '☀️', 'display_order' => 4],
            ['key' => 'pricing_rules', 'kategori_id' => 5, 'label' => 'Fiyatlandırma Kuralları', 'icon' => '💰', 'display_order' => 5],

            // Genel
            ['key' => 'para_birimleri', 'kategori_id' => null, 'label' => 'Para Birimleri', 'icon' => '💰', 'display_order' => 1],
            ['key' => 'status_options', 'kategori_id' => null, 'label' => 'Status Seçenekleri', 'icon' => '📊', 'display_order' => 2],
            ['key' => 'konum_avantajlari', 'kategori_id' => null, 'label' => 'Konum Avantajları', 'icon' => '📍', 'display_order' => 3],
        ];
    }

    /**
     * Option type'ı belirle
     */
    private function determineOptionType($value)
    {
        if (!is_array($value)) {
            return 'simple';
        }

        // Nested array kontrolü
        if (isset($value['discounts']) || isset($value['seasonal_multipliers'])) {
            return 'nested';
        }

        // Object array kontrolü (ilk eleman object ise)
        $firstKey = array_key_first($value);
        if ($firstKey !== null && is_array($value[$firstKey]) && isset($value[$firstKey]['label'])) {
            return 'object_array';
        }

        // Associative array kontrolü (key'ler string ve numeric değilse)
        if (!isset($value[0]) && !is_numeric($firstKey)) {
            return 'associative';
        }

        // Simple array
        return 'simple';
    }
}
