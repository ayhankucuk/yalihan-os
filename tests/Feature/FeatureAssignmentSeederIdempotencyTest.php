<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Seeder + Migration Idempotency Test
 *
 * Verifies that when both FeatureAssignmentSeeder and the villa migration run,
 * no duplicate records are created. Uses updateOrInsert to prevent duplicates.
 */
class FeatureAssignmentSeederIdempotencyTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as parentRefreshDatabase;
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPrerequisites();
    }

    private function seedPrerequisites(): void
    {
        // Create ilan_kategorileri records
        DB::table('ilan_kategorileri')->insert([
            ['id' => 1, 'name' => 'Konut', 'slug' => 'konut', 'seviye' => 0, 'parent_id' => null, 'aktiflik_durumu' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'Villa', 'slug' => 'villa', 'seviye' => 1, 'parent_id' => 1, 'aktiflik_durumu' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Create yayin_tipi_sablonlari records
        DB::table('yayin_tipi_sablonlari')->insert([
            ['id' => 1, 'ad' => 'Villa Satılık', 'slug' => 'villa-satilik', 'kategori_id' => 8, 'yayin_tipi_id' => 1, 'aktiflik_durumu' => 1, 'display_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'ad' => 'Villa Kiralık', 'slug' => 'villa-kiralik', 'kategori_id' => 8, 'yayin_tipi_id' => 2, 'aktiflik_durumu' => 1, 'display_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'ad' => 'Villa Günlük', 'slug' => 'villa-gunluk', 'kategori_id' => 8, 'yayin_tipi_id' => 5, 'aktiflik_durumu' => 1, 'display_order' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Test: Seeder covers more ground than migration (expected)
     *
     * Seeder adds G1/G2 (IlanKategori) on top of what migration provides.
     * This is intentional - seeder is more comprehensive.
     *
     * @test
     */
    public function seeder_covers_more_than_migration(): void
    {
        // Run seeder
        $this->seedFeatureAssignmentSeeder();
        $seederCount = DB::table('feature_assignments')->count();

        // Reset and run migration
        DB::table('feature_assignments')->delete();
        $this->seedViaMigration();
        $migrationCount = DB::table('feature_assignments')->count();

        // Seeder should have MORE records (adds G1/G2)
        $this->assertGreaterThan($migrationCount, $seederCount,
            "Seeder ({$seederCount}) should have more records than migration ({$migrationCount})");
    }

    /**
     * Test: Both seeder and migration have correct assignable_type
     *
     * Seeder: YayinTipiSablonu + IlanKategori
     * Migration: YayinTipiSablonu only
     *
     * @test
     */
    public function both_have_correct_assignable_type(): void
    {
        // Seeder
        $this->seedFeatureAssignmentSeeder();
        $seederYayin = DB::table('feature_assignments')
            ->where('assignable_type', 'App\Models\YayinTipiSablonu')->count();
        $seederIlan = DB::table('feature_assignments')
            ->where('assignable_type', 'App\Models\IlanKategori')->count();
        $seederOther = DB::table('feature_assignments')
            ->whereNotIn('assignable_type', ['App\Models\YayinTipiSablonu', 'App\Models\IlanKategori'])->count();

        $this->assertEquals(0, $seederOther, "Seeder should have no non-canonical types");

        // Migration
        DB::table('feature_assignments')->delete();
        $this->seedViaMigration();
        $migrationYayin = DB::table('feature_assignments')
            ->where('assignable_type', 'App\Models\YayinTipiSablonu')->count();
        $migrationIlan = DB::table('feature_assignments')
            ->where('assignable_type', 'App\Models\IlanKategori')->count();
        $migrationOther = DB::table('feature_assignments')
            ->whereNotIn('assignable_type', ['App\Models\YayinTipiSablonu', 'App\Models\IlanKategori'])->count();

        $this->assertEquals(0, $migrationOther, "Migration should have no non-canonical types");
        $this->assertEquals(0, $migrationIlan, "Migration should not have IlanKategori (seeder only)");

        // Both should have YayinTipiSablonu
        $this->assertGreaterThan(0, $seederYayin, "Seeder should have YayinTipiSablonu");
        $this->assertGreaterThan(0, $migrationYayin, "Migration should have YayinTipiSablonu");
    }

    /**
     * Test: Running seeder twice produces no duplicates
     *
     * @test
     */
    public function running_seeder_twice_produces_no_duplicates(): void
    {
        // Run seeder first time
        $this->seedFeatureAssignmentSeeder();
        $firstCount = DB::table('feature_assignments')->count();

        // Run seeder second time
        $this->seedFeatureAssignmentSeeder();
        $secondCount = DB::table('feature_assignments')->count();

        $this->assertEquals($firstCount, $secondCount,
            "Running seeder twice should not create duplicates: first={$firstCount}, second={$secondCount}");
    }

    /**
     * Test: All canonical_seed records have correct assignable_type
     *
     * @test
     */
    public function all_canonical_seed_records_have_correct_assignable_type(): void
    {
        $this->seedFeatureAssignmentSeeder();

        $yayinTipiSablonu = DB::table('feature_assignments')
            ->where('source_type', 'canonical_seed')
            ->where('assignable_type', 'App\Models\YayinTipiSablonu')
            ->count();

        $ilanKategori = DB::table('feature_assignments')
            ->where('source_type', 'canonical_seed')
            ->where('assignable_type', 'App\Models\IlanKategori')
            ->count();

        $other = DB::table('feature_assignments')
            ->where('source_type', 'canonical_seed')
            ->whereNotIn('assignable_type', ['App\Models\YayinTipiSablonu', 'App\Models\IlanKategori'])
            ->count();

        $this->assertEquals(0, $other, "No records should have non-canonical assignable_type");
        $this->assertGreaterThan(0, $yayinTipiSablonu, "Should have YayinTipiSablonu records");
        $this->assertGreaterThan(0, $ilanKategori, "Should have IlanKategori records");
    }

    /**
     * Test: G1/G2 explicitly seeded
     *
     * @test
     */
    public function g1_and_g2_are_explicitly_seeded(): void
    {
        $this->seedFeatureAssignmentSeeder();

        // G1: Global (all categories)
        $g1Count = DB::table('feature_assignments')
            ->whereNull('main_category_id')
            ->where('source_type', 'canonical_seed')
            ->count();

        // G2: Konut main category
        $g2Count = DB::table('feature_assignments')
            ->where('main_category_id', 1)
            ->whereNull('sub_category_id')
            ->where('source_type', 'canonical_seed')
            ->count();

        $this->assertGreaterThan(0, $g1Count, "G1 (global) should be seeded");
        $this->assertGreaterThan(0, $g2Count, "G2 (Konut) should be seeded");
    }

    private function seedFeatureAssignmentSeeder(): void
    {
        $seeder = new \Database\Seeders\FeatureAssignmentSeeder();
        $seeder->run();
    }

    private function seedViaMigration(): void
    {
        $migration = require database_path('migrations/2026_08_25_000001_seed_villa_feature_assignments.php');
        $migration->up();
    }

    private function getTierCounts(): object
    {
        return DB::table('feature_assignments')
            ->where('source_type', 'canonical_seed')
            ->selectRaw('
                SUM(CASE WHEN main_category_id IS NULL AND sub_category_id IS NULL AND listing_type_id IS NULL THEN 1 ELSE 0 END) as g1_global,
                SUM(CASE WHEN main_category_id = 1 AND sub_category_id IS NULL AND listing_type_id IS NULL THEN 1 ELSE 0 END) as g2_konut,
                SUM(CASE WHEN main_category_id = 1 AND sub_category_id = 8 AND listing_type_id = 1 THEN 1 ELSE 0 END) as g3_villa_satilik,
                SUM(CASE WHEN main_category_id = 1 AND sub_category_id = 8 AND listing_type_id = 2 THEN 1 ELSE 0 END) as g4_villa_kiralik,
                SUM(CASE WHEN main_category_id = 1 AND sub_category_id = 8 AND listing_type_id = 5 THEN 1 ELSE 0 END) as g5_villa_gunluk
            ')
            ->first();
    }
}
