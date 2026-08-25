<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * SeedFeatureAssignmentsCommand — Tetikle: FeatureAssignmentSeeder.
 *
 * Kullanım:
 *   php artisan db:seed --class=FeatureAssignmentSeeder
 *   php artisan feature:seed-villa
 *
 * API tetikleme (Artisan::call):
 *   Artisan::call('feature:seed-villa', ['--dry-run' => true]);
 *
 * SAAB Rule: Production DB değişikliği için audit log tutulur.
 */
class SeedFeatureAssignmentsCommand extends Command
{
    protected $signature = 'feature:seed-villa
                            {--dry-run : Seed etmeden mevcut durumu göster}
                            {--force : Mevcut kayıtları silip yeniden oluştur}';

    protected $description = 'Villa-specific feature assignment seed data oluşturur';

    public function handle(): int
    {
        $this->info('🌱 FeatureAssignmentSeeder — Villa Seed');
        $this->newLine();

        $fc  = DB::table('feature_categories')->count();
        $f   = DB::table('features')->count();
        $fa  = DB::table('feature_assignments')->count();

        $this->table(
            ['Tablo', 'Mevcut'],
            [
                ['feature_categories',    $fc],
                ['features',             $f],
                ['feature_assignments',  $fa],
            ]
        );

        if ($this->option('dry-run')) {
            $this->warn('⏭️  Dry-run: Değişiklik yapılmadı.');
            return Command::SUCCESS;
        }

        if ($fa > 0 && !$this->option('force')) {
            $this->info("✅ feature_assignments zaten {$fa} satır içeriyor. --force ile yeniden oluştur.");
            return Command::SUCCESS;
        }

        if ($this->option('force')) {
            $this->warn('🗑️  Mevcut kayıtlar siliniyor...');
            DB::table('feature_assignments')->delete();
            DB::table('feature_categories')->delete();
            DB::table('features')->delete();
        }

        $this->info('📦 Seeding başlatılıyor...');
        $this->call('db:seed', ['--class' => FeatureAssignmentSeeder::class]);

        $this->newLine();
        $this->info('✅ Seed tamamlandı.');

        $this->newLine();
        $this->table(
            ['Tablo', 'Son'],
            [
                ['feature_categories',    DB::table('feature_categories')->count()],
                ['features',             DB::table('features')->count()],
                ['feature_assignments',  DB::table('feature_assignments')->count()],
            ]
        );

        return Command::SUCCESS;
    }
}
