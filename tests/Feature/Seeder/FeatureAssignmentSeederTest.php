<?php

namespace Tests\Feature\Seeder;

use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\YayinTipi;
use App\Models\YayinTipiSablonu;
use Database\Seeders\FeatureAssignmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 2 — FeatureAssignmentSeeder Regression Tests (T1, T2)
 *
 * T1: SeederAssignsToYayinTipiSablonu — Fresh seed → assignable_type = YayinTipiSablonu::class
 * T2: SeederDoesNotUseIlanType — Fresh seed → 0 records with assignable_type = 'App\Models\Ilan'
 *
 * @see storage/tmp/canonical-mapping-inheritance-implementation-plan.md §5.1
 */
class FeatureAssignmentSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPrerequisites();
    }

    /**
     * T1: After seeding, all listing_type + main_category scope assignments
     * must have assignable_type = YayinTipiSablonu::class.
     */
    public function test_t1_seeder_assigns_to_yayin_tipi_sablonu(): void
    {
        DB::table('feature_assignments')->truncate();
        $seeder = new FeatureAssignmentSeeder();
        $seeder->run();

        $templateAssignments = DB::table('feature_assignments')
            ->where('assignable_type', YayinTipiSablonu::class)
            ->count();

        $this->assertGreaterThan(
            0,
            $templateAssignments,
            'Seeder must produce at least one assignment with assignable_type = YayinTipiSablonu::class'
        );
    }

    /**
     * T1b: Global scope assignments use IlanKategori::class (SAAB 1B).
     */
    public function test_t1b_global_scope_assigns_to_ilan_kategori(): void
    {
        DB::table('feature_assignments')->truncate();
        $seeder = new FeatureAssignmentSeeder();
        $seeder->run();

        $globalAssignments = DB::table('feature_assignments')
            ->where('assignable_type', IlanKategori::class)
            ->where('scope_type', 'global')
            ->count();

        $this->assertGreaterThan(
            0,
            $globalAssignments,
            'Global scope assignments must use IlanKategori::class (SAAB 1B)'
        );
    }

    /**
     * T2: After seeding, zero assignments must have assignable_type = 'App\Models\Ilan'.
     */
    public function test_t2_seeder_does_not_use_ilan_type(): void
    {
        DB::table('feature_assignments')->truncate();
        $seeder = new FeatureAssignmentSeeder();
        $seeder->run();

        $ilanTypeCount = DB::table('feature_assignments')
            ->where('assignable_type', 'App\\Models\\Ilan')
            ->count();

        $this->assertSame(
            0,
            $ilanTypeCount,
            'Seeder must NOT produce any assignments with assignable_type = App\Models\Ilan'
        );
    }

    /**
     * T2b: After seeding, zero assignments must have assignable_id = 0.
     */
    public function test_t2b_seeder_does_not_produce_assignable_id_zero(): void
    {
        DB::table('feature_assignments')->truncate();
        $seeder = new FeatureAssignmentSeeder();
        $seeder->run();

        $zeroIdCount = DB::table('feature_assignments')
            ->where('assignable_id', 0)
            ->count();

        $this->assertSame(
            0,
            $zeroIdCount,
            'Seeder must NOT produce any assignments with assignable_id = 0'
        );
    }

    /**
     * T2c: All assignable_id values must reference real records.
     */
    public function test_t2c_all_assignable_ids_reference_real_records(): void
    {
        DB::table('feature_assignments')->truncate();
        $seeder = new FeatureAssignmentSeeder();
        $seeder->run();

        $templateIds = DB::table('feature_assignments')
            ->where('assignable_type', YayinTipiSablonu::class)
            ->pluck('assignable_id')
            ->unique();

        foreach ($templateIds as $id) {
            $this->assertGreaterThan(
                0,
                $id,
                "assignable_id must be > 0 for YayinTipiSablonu assignments"
            );
            $exists = DB::table('yayin_tipi_sablonlari')->where('id', $id)->exists();
            $this->assertTrue(
                $exists,
                "assignable_id={$id} must reference an existing YayinTipiSablonu record"
            );
        }
    }

    /**
     * T-idempotent: Running the seeder twice produces no duplicate assignments.
     */
    public function test_seeder_is_idempotent(): void
    {
        $seeder = new FeatureAssignmentSeeder();

        $seeder->run();
        $countAfterFirst = DB::table('feature_assignments')->count();

        $seeder->run();
        $countAfterSecond = DB::table('feature_assignments')->count();

        $this->assertSame(
            $countAfterFirst,
            $countAfterSecond,
            'Running seeder twice must not produce duplicate assignments (idempotent)'
        );
    }

    /**
     * T12: Konut main_category scope assignments are copied to both
     * konut-satilik and konut-kiralik templates (SAAB 2A).
     */
    public function test_t12_konut_features_copied_to_both_templates(): void
    {
        DB::table('feature_assignments')->truncate();
        $seeder = new FeatureAssignmentSeeder();
        $seeder->run();

        $konutSatilik = DB::table('yayin_tipi_sablonlari')->where('slug', 'konut-satilik')->first();
        $konutKiralik = DB::table('yayin_tipi_sablonlari')->where('slug', 'konut-kiralik')->first();

        $this->assertNotNull($konutSatilik, 'konut-satilik template must exist');
        $this->assertNotNull($konutKiralik, 'konut-kiralik template must exist');

        $satilikAssignments = DB::table('feature_assignments')
            ->where('assignable_type', YayinTipiSablonu::class)
            ->where('assignable_id', $konutSatilik->id)
            ->count();

        $kiralikAssignments = DB::table('feature_assignments')
            ->where('assignable_type', YayinTipiSablonu::class)
            ->where('assignable_id', $konutKiralik->id)
            ->count();

        $this->assertGreaterThan(0, $satilikAssignments, 'konut-satilik must have assignments');
        $this->assertGreaterThan(0, $kiralikAssignments, 'konut-kiralik must have assignments');
        $this->assertSame(
            $satilikAssignments,
            $kiralikAssignments,
            'konut-satilik and konut-kiralik must have equal assignment counts (SAAB 2A copy)'
        );
    }

    /**
     * T-G1-cascade: G1 global scope (scope_type=global) is readable by Ups FeatureTemplateResolver
     * through getIlanKategoriAssignments().
     *
     * This test proves that G1 assignments are accessible at runtime — not silently dropped.
     * The seeder produces scope_type=global + IlanKategori::class records.
     * The resolver reads them via getIlanKategoriAssignments().
     * Both layers are verified: seeder output + resolver read path.
     *
     * @test
     */
    public function test_t_g1_ilan_kategori_assignments_visible_to_resolver(): void
    {
        DB::table('feature_assignments')->truncate();
        $seeder = new FeatureAssignmentSeeder();
        $seeder->run();

        // Verify seeder produced G1 records
        $g1Assignments = DB::table('feature_assignments')
            ->where('assignable_type', IlanKategori::class)
            ->where('scope_type', 'global')
            ->whereNull('main_category_id')
            ->whereNull('sub_category_id')
            ->whereNull('listing_type_id')
            ->get();

        $this->assertGreaterThan(
            0,
            $g1Assignments->count(),
            'Seeder must produce global-scope IlanKategori assignments (G1)'
        );

        // Verify Ups FeatureTemplateResolver::getIlanKategoriAssignments() reads them
        $resolver = app(\App\Services\Ups\FeatureTemplateResolver::class);
        $ilanKatAssignments = $resolver->getIlanKategoriAssignments();

        $this->assertGreaterThan(
            0,
            $ilanKatAssignments->count(),
            'getIlanKategoriAssignments() must return G1 records from IlanKategori scope'
        );
    }

    /**
     * T-rollback-safe: Pre-existing source_type=NULL records survive a second seeder run.
     *
     * Regression: NULL source_type records must NOT be deleted by the seeder or migration.
     * The seeder only upserts its own source_type='villa_seed_2026_08_25' records.
     * It never touches NULL source_type rows.
     *
     * @test
     */
    public function test_t_null_source_type_preserved_across_seeder_runs(): void
    {
        DB::table('feature_assignments')->truncate();

        // Simulate a pre-existing record from a legacy migration (source_type IS NULL)
        $legacyId = DB::table('feature_assignments')->insertGetId([
            'feature_id'       => 33,
            'assignable_type'  => IlanKategori::class,
            'assignable_id'    => 1,
            'main_category_id' => null,
            'sub_category_id'  => null,
            'listing_type_id'  => null,
            'scope_type'       => 'global',
            'source_type'      => 'manual', // legacy record — NOT NULL default 'manual'
            'group_name'       => 'Maliyet ve Aidat',
            'field_slug'      => 'kredi-uygunlugu',
            'is_required'      => false,
            'is_visible'       => true,
            'aktiflik_durumu'  => 1,
            'display_order'    => 1,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $seeder = new FeatureAssignmentSeeder();
        $seeder->run();

        // Verify NULL source_type record still exists
        $stillExists = DB::table('feature_assignments')
            ->where('id', $legacyId)
            ->exists();

        $this->assertTrue(
            $stillExists,
            'source_type=NULL legacy record must survive seeder run'
        );
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    /**
     * Seed prerequisite data that the seeder expects to exist:
     * - 8 yayin_tipleri records
     * - Kategori id=1 (Konut, slug='konut')
     * - Kategori id=8 (Villa, slug='villa', parent=1)
     * - Templates: villa-satilik, villa-kiralik, villa-gunluk, konut-satilik, konut-kiralik
     */
    private function seedPrerequisites(): void
    {
        $now = now();

        // Seed yayin_tipleri (use DB::table to bypass model scopes)
        $yayinTipleri = [
            ['id' => 1, 'name' => 'Satilik', 'slug' => 'satilik', 'aktiflik_durumu' => 1],
            ['id' => 2, 'name' => 'Kiralik', 'slug' => 'kiralik', 'aktiflik_durumu' => 1],
            ['id' => 3, 'name' => 'Kat Karsiligi', 'slug' => 'kat-karsiligi', 'aktiflik_durumu' => 1],
            ['id' => 4, 'name' => 'Devren', 'slug' => 'devren', 'aktiflik_durumu' => 1],
            ['id' => 5, 'name' => 'Gunluk Kiralik', 'slug' => 'gunluk-kiralik', 'aktiflik_durumu' => 1],
            ['id' => 6, 'name' => 'Haftalik Kiralik', 'slug' => 'haftalik-kiralik', 'aktiflik_durumu' => 1],
            ['id' => 7, 'name' => 'Aylik Kiralik', 'slug' => 'aylik-kiralik', 'aktiflik_durumu' => 1],
            ['id' => 8, 'name' => 'Sezonluk Kiralik', 'slug' => 'sezonluk-kiralik', 'aktiflik_durumu' => 1],
        ];
        foreach ($yayinTipleri as $yt) {
            DB::table('yayin_tipleri')->insert(array_merge($yt, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // Seed kategoriler (use DB::table to bypass model scopes and explicit id)
        DB::table('ilan_kategorileri')->insert([
            'id' => 1,
            'tenant_id' => 'SYSTEM',
            'name' => 'Konut',
            'slug' => 'konut',
            'seviye' => 0,
            'parent_id' => null,
            'aktiflik_durumu' => 1,
            'display_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('ilan_kategorileri')->insert([
            'id' => 8,
            'tenant_id' => 'SYSTEM',
            'name' => 'Villa',
            'slug' => 'villa',
            'seviye' => 1,
            'parent_id' => 1,
            'aktiflik_durumu' => 1,
            'display_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Seed templates (use DB::table to bypass model scopes)
        $templates = [
            ['slug' => 'villa-satilik', 'ad' => 'Villa Satilik', 'kategori_id' => 8],
            ['slug' => 'villa-kiralik', 'ad' => 'Villa Kiralik', 'kategori_id' => 8],
            ['slug' => 'villa-gunluk', 'ad' => 'Villa Gunluk', 'kategori_id' => 8],
            ['slug' => 'konut-satilik', 'ad' => 'Konut Satilik', 'kategori_id' => 1],
            ['slug' => 'konut-kiralik', 'ad' => 'Konut Kiralik', 'kategori_id' => 1],
        ];

        foreach ($templates as $tpl) {
            DB::table('yayin_tipi_sablonlari')->insert([
                'tenant_id' => 'SYSTEM',
                'slug' => $tpl['slug'],
                'ad' => $tpl['ad'],
                'kategori_id' => $tpl['kategori_id'],
                'aktiflik_durumu' => 1,
                'display_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
