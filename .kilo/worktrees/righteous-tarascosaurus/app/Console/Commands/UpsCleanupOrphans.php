<?php

namespace App\Console\Commands;

use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\UpsTemplate;
use App\Models\YayinTipiSablonu;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\Logging\LogService;

/**
 * 🛡️ UpsCleanupOrphans — Governance Enforcement Layer
 *
 * Tüm delete işlemleri Eloquent individual delete üzerinden yapılır.
 * Amaç: Observer::deleted() tetiklensin → UpsCacheService::invalidateForJunction()
 * çalışsın, TemplateChangeLog yazılsın.
 *
 * @see docs/adr/2026-02-21-governance-enforcement-layer.md
 */
class UpsCleanupOrphans extends Command
{
    protected $signature = 'ups:cleanup-orphans {--force}';
    protected $description = 'Clean orphan feature_assignments (missing feature or missing assignable)';

    public function handle(): int
    {
        $t0 = LogService::startTimer('ups_cleanup_orphans');

        // Governance Enforcement: DB::table->delete() KALDIRILDI.
        // Her kayıt tek tek Eloquent delete → Observer::deleted() → invalidateForJunction

        $deletedFeature = $this->deleteOrphans(
            fn ($q) => $q->whereNotIn('feature_id', DB::table('features')->select('id'))
        );

        $deletedYayinTipiSablonu = $this->deleteOrphans(
            fn ($q) => $q
                ->where('assignable_type', YayinTipiSablonu::class)
                ->whereNotIn('assignable_id', DB::table('yayin_tipi_sablonlari')->select('id'))
        );

        $deletedIlanKategori = $this->deleteOrphans(
            fn ($q) => $q
                ->where('assignable_type', IlanKategori::class)
                ->whereNotIn('assignable_id', DB::table('ilan_kategorileri')->select('id'))
        );

        $deletedUpsTemplate = 0;
        if (Schema::hasTable('ups_templates')) {
            $deletedUpsTemplate = $this->deleteOrphans(
                fn ($q) => $q
                    ->where('assignable_type', UpsTemplate::class)
                    ->whereNotIn('assignable_id', DB::table('ups_templates')->select('id'))
            );
        }

        $force = (bool) $this->option('force');

        $deletedInactiveFeature    = 0;
        $deletedDisabledYayinTipi  = 0;
        if ($force) {
            $deletedInactiveFeature = $this->deleteOrphans(
                fn ($q) => $q->whereIn(
                    'feature_id',
                    DB::table('features')->select('id')->where('aktif_mi', false)
                )
            );

            $deletedDisabledYayinTipi = $this->deleteOrphans(
                fn ($q) => $q
                    ->where('assignable_type', YayinTipiSablonu::class)
                    ->whereIn(
                        'assignable_id',
                        DB::table('yayin_tipi_sablonlari')->select('id')->where('aktif_mi', 0)
                    )
            );
        }

        LogService::info('ups_cleanup_orphans', [
            'deleted_missing_feature'                => $deletedFeature,
            'deleted_missing_assignable_yayin_tipi'  => $deletedYayinTipiSablonu,
            'deleted_missing_assignable_kategori'    => $deletedIlanKategori,
            'deleted_missing_assignable_template'    => $deletedUpsTemplate,
            'deleted_inactive_feature_assignments'   => $deletedInactiveFeature,
            'deleted_disabled_yayin_tipi_assignments' => $deletedDisabledYayinTipi,
            'duration_ms'                            => (int) LogService::stopTimer($t0),
        ]);

        $totalDeleted = $deletedFeature + $deletedYayinTipiSablonu + $deletedIlanKategori
            + $deletedUpsTemplate + $deletedInactiveFeature + $deletedDisabledYayinTipi;

        $this->info("Deleted orphan assignments: total={$totalDeleted} " .
            "(feature={$deletedFeature}, yayin_tipi={$deletedYayinTipiSablonu}, " .
            "kategori={$deletedIlanKategori}, template={$deletedUpsTemplate}, " .
            "inactive_feature={$deletedInactiveFeature}, disabled_yayin_tipi={$deletedDisabledYayinTipi})");

        return Command::SUCCESS;
    }

    /**
     * Builder callback ile filtrelenen FeatureAssignment satırlarını
     * Eloquent individual delete ile sil — Observer::deleted() tetiklenir.
     *
     * @param  callable(\Illuminate\Database\Eloquent\Builder): \Illuminate\Database\Eloquent\Builder  $filter
     */
    private function deleteOrphans(callable $filter): int
    {
        $query = $filter(FeatureAssignment::query());
        $rows  = $query->get();
        $count = $rows->count();
        $rows->each(fn (FeatureAssignment $fa) => $fa->delete());
        return $count;
    }
}

