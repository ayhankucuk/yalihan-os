<?php

declare(strict_types=1);

namespace Tests\Feature\Governance;

use App\Console\Commands\UpsCleanupOrphans;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\YayinTipiSablonu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * UpsCleanupOrphans — Governance Enforcement Tests
 *
 * `ups:cleanup-orphans` komutunun yalnızca orphan kayıtları sildiğini,
 * geçerli kayıtlara dokunmadığını doğrular.
 *
 * Observer zinciri (cache + changelog) FeatureAssignmentObserverTest'te
 * ayrıca test edilmiştir. Bu dosya komutun seçici davranışına odaklanır.
 *
 * @see app/Console/Commands/UpsCleanupOrphans.php
 * @see tests/Feature/Governance/FeatureAssignmentObserverTest.php
 * @see docs/adr/2026-02-21-governance-enforcement-layer.md
 */
class UpsCleanupOrphansGovernanceTest extends TestCase
{

    /**
     * Var olmayan feature_id ile orphan kayıt silinmelidir.
     *
     * @test
     */
    public function cleanup_deletes_assignment_with_missing_feature(): void
    {
        $junction = YayinTipiSablonu::create([
            'ad'              => 'Orphan Test Sablon',
            'slug'            => 'orphan-test-sablon',
            'aktiflik_durumu' => 1,
            'display_order'   => 1,
        ]);

        // SQLite FK constraint'i atlamak için orphan kaydı doğrudan insert et.
        // feature_id=99999 → features tablosunda yok → gerçek orphan.
        Schema::disableForeignKeyConstraints();
        $orphanId = DB::table('feature_assignments')->insertGetId([
            'feature_id'      => 99999, // var olmayan feature
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id'   => $junction->id,
            'is_required'     => 0,
            'is_visible'      => 1,
            'display_order'   => 1,
            'aktiflik_durumu' => 1,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
        Schema::enableForeignKeyConstraints();

        $this->assertDatabaseHas('feature_assignments', ['id' => $orphanId]);

        $this->artisan('ups:cleanup-orphans')
            ->assertExitCode(UpsCleanupOrphans::SUCCESS);

        // Orphan Eloquent ile silindi (Observer zinciri tetiklendi)
        $this->assertDatabaseMissing('feature_assignments', ['id' => $orphanId]);
    }

    /**
     * Birden fazla orphan varsa hepsi silinmelidir.
     *
     * @test
     */
    public function cleanup_deletes_all_assignments_with_missing_feature(): void
    {
        $feature = Feature::create([
            'name'            => 'Multi-Orphan Feature',
            'slug'            => 'multi-orphan-feature',
            'type'            => 'text',
            'aktiflik_durumu' => 1,
        ]);

        $junctions = collect(range(1, 3))->map(fn (int $i) => YayinTipiSablonu::create([
            'ad'              => "Multi Orphan Sablon {$i}",
            'slug'            => "multi-orphan-sablon-{$i}",
            'aktiflik_durumu' => 1,
            'display_order'   => $i,
        ]));

        $assignments = $junctions->map(fn (YayinTipiSablonu $j) => FeatureAssignment::create([
            'feature_id'      => $feature->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id'   => $j->id,
            'is_required'     => false,
            'is_visible'      => true,
            'display_order'   => 1,
            'aktiflik_durumu' => true,
        ]));

        DB::table('features')->where('id', $feature->id)->delete();

        $this->artisan('ups:cleanup-orphans')
            ->assertExitCode(UpsCleanupOrphans::SUCCESS);

        $assignments->each(fn (FeatureAssignment $a) =>
            $this->assertDatabaseMissing('feature_assignments', ['id' => $a->id])
        );
    }

    /**
     * Orphan olmayan (geçerli) atamalar silinmemelidir.
     *
     * @test
     */
    public function cleanup_does_not_delete_valid_assignments(): void
    {
        $junction = YayinTipiSablonu::create([
            'ad'              => 'Valid Sablon',
            'slug'            => 'valid-sablon',
            'aktiflik_durumu' => 1,
            'display_order'   => 1,
        ]);

        $feature = Feature::create([
            'name'            => 'Valid Feature',
            'slug'            => 'valid-feature',
            'type'            => 'text',
            'aktiflik_durumu' => 1,
        ]);

        $assignment = FeatureAssignment::create([
            'feature_id'      => $feature->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id'   => $junction->id,
            'is_required'     => false,
            'is_visible'      => true,
            'display_order'   => 1,
            'aktiflik_durumu' => true,
        ]);

        $this->artisan('ups:cleanup-orphans')
            ->assertExitCode(UpsCleanupOrphans::SUCCESS);

        // Geçerli kayıt silinmemeli
        $this->assertDatabaseHas('feature_assignments', ['id' => $assignment->id]);
    }

    /**
     * Var olmayan assignable_id'ye sahip YayinTipiSablonu orphan silinir.
     *
     * @test
     */
    public function cleanup_deletes_assignment_with_missing_yayin_tipi_sablonu(): void
    {
        $feature = Feature::create([
            'name'            => 'Valid Feature VA',
            'slug'            => 'valid-feature-va',
            'type'            => 'text',
            'aktiflik_durumu' => 1,
        ]);

        $junction = YayinTipiSablonu::create([
            'ad'              => 'Ghost Sablon',
            'slug'            => 'ghost-sablon',
            'aktiflik_durumu' => 1,
            'display_order'   => 1,
        ]);

        $assignment = FeatureAssignment::create([
            'feature_id'      => $feature->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id'   => $junction->id,
            'is_required'     => false,
            'is_visible'      => true,
            'display_order'   => 1,
            'aktiflik_durumu' => true,
        ]);

        // Junction'ı sil → assignable_id artık geçersiz
        DB::table('yayin_tipi_sablonlari')->where('id', $junction->id)->delete();

        $this->artisan('ups:cleanup-orphans')
            ->assertExitCode(UpsCleanupOrphans::SUCCESS);

        $this->assertDatabaseMissing('feature_assignments', ['id' => $assignment->id]);
    }
}
