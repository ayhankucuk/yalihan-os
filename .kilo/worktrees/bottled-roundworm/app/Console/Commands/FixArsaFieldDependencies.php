<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixArsaFieldDependencies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'field-deps:fix-arsa {--dry-run : Sadece göster, düzeltme}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Arsa Field Dependencies\'deki unit eksikliklerini düzelt';

    /**
     * Yayın tipi ID mapping
     */
    private array $yayinTipiMapping = [
        3 => 'Satılık',
        4 => 'Kiralık',
        16 => 'Kat Karşılığı',
        37 => 'Ticari',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('🏞️  Arsa Field Dependencies Unit Düzeltmesi Başlatılıyor...');
        $this->newLine();

        // Unit eksikliklerini düzelt
        $this->fixMissingUnits($dryRun);

        $this->newLine();
        $this->info('✅ Arsa Field Dependencies unit düzeltmesi tamamlandı!');
    }

    /**
     * Unit eksikliklerini düzelt
     */
    private function fixMissingUnits(bool $dryRun): void
    {
        $this->info('📏 Unit Eksikliklerini Düzeltme...');

        // Unit eksiklikleri ve düzeltmeleri
        $fixes = [
            ['yayin_tipi' => '16', 'field_slug' => 'kaks', 'unit' => '%', 'field_name' => 'KAKS'],
            ['yayin_tipi' => '37', 'field_slug' => 'gabari', 'unit' => 'm', 'field_name' => 'Gabari'],
            ['yayin_tipi' => '37', 'field_slug' => 'kaks', 'unit' => '%', 'field_name' => 'KAKS'],
            ['yayin_tipi' => '37', 'field_slug' => 'm2_fiyati', 'unit' => 'TL/m²', 'field_name' => 'm² Fiyatı'],
            ['yayin_tipi' => '4', 'field_slug' => 'kaks', 'unit' => '%', 'field_name' => 'KAKS'],
            ['yayin_tipi' => '4', 'field_slug' => 'm2_fiyati', 'unit' => 'TL/m²', 'field_name' => 'm² Fiyatı'],
        ];

        $this->info('  📊 Bulunan unit eksiklikleri: ' . count($fixes));

        foreach ($fixes as $fix) {
            $ytName = $this->yayinTipiMapping[$fix['yayin_tipi']] ?? 'ID ' . $fix['yayin_tipi'];

            $current = DB::table('kategori_yayin_tipi_field_dependencies')
                ->where('kategori_slug', 'arsa')
                ->where('yayin_tipi', $fix['yayin_tipi'])
                ->where('field_slug', $fix['field_slug'])
                ->first(['id', 'field_name', 'field_unit']);

            if (!$current) {
                $this->warn("    ⚠️  {$ytName} → {$fix['field_name']}: Field bulunamadı");
                continue;
            }

            $currentUnit = $current->field_unit ?? '(boş)';
            $this->line("    - {$ytName} → {$fix['field_name']}: {$currentUnit} → {$fix['unit']}");
        }

        if ($dryRun) {
            $this->warn('  ⚠️  DRY-RUN: Unit\'ler güncellenmeyecek.');
            return;
        }

        DB::beginTransaction();
        try {
            $count = 0;
            foreach ($fixes as $fix) {
                $updated = DB::table('kategori_yayin_tipi_field_dependencies')
                    ->where('kategori_slug', 'arsa')
                    ->where('yayin_tipi', $fix['yayin_tipi'])
                    ->where('field_slug', $fix['field_slug'])
                    ->whereNull('field_unit')
                    ->update(['field_unit' => $fix['unit']]);

                if ($updated > 0) {
                    $count++;
                }
            }

            $this->info("  ✅ {$count} field'a unit eklendi.");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('  ❌ Hata: ' . $e->getMessage());
            throw $e;
        }
    }
}
