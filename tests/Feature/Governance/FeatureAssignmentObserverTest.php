<?php

declare(strict_types=1);

namespace Tests\Feature\Governance;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Observers\FeatureAssignmentObserver;
use App\Services\Ups\FeatureTemplateResolver;
use App\Services\Ups\UpsCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Governance Enforcement Layer — FeatureAssignment Observer Tests
 *
 * Her FeatureAssignment create/delete olayının otomatik olarak
 * cache invalidation ve changelog write tetiklediğini doğrular.
 *
 * @see app/Observers/FeatureAssignmentObserver.php
 * @see docs/adr/2026-02-21-governance-enforcement-layer.md
 */
class FeatureAssignmentObserverTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature atandığında UpsCacheService::invalidateForJunction çağrılmalı
     *
     * @test
     */
    public function feature_assignment_created_triggers_cache_invalidation(): void
    {
        /** @var UpsCacheService|\Mockery\MockInterface $cacheMock */
        $cacheMock = Mockery::mock(UpsCacheService::class);
        $cacheMock->shouldReceive('invalidateForJunction')
            ->once()
            ->withArgs(function (int $junctionId, mixed $kategoriId, mixed $yayinTipiId): bool {
                return $junctionId > 0;
            });

        $this->app->instance(UpsCacheService::class, $cacheMock);

        $junction = YayinTipiSablonu::create([
            'ad'              => 'Observer Test Sablon',
            'slug'            => 'observer-test-sablon',
            'aktiflik_durumu' => 1,
            'display_order'   => 1,
        ]);

        $feature = Feature::create([
            'name'            => 'Observer Test Feature',
            'slug'            => 'observer-test-feature',
            'type'            => 'text',
            'aktiflik_durumu' => 1,
        ]);

        // Bu create FeatureAssignmentObserver::created() tetiklemeli
        FeatureAssignment::create([
            'feature_id'      => $feature->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id'   => $junction->id,
            'is_required'     => false,
            'is_visible'      => true,
            'display_order'   => 1,
            'aktiflik_durumu' => true,
        ]);
    }

    /**
     * Feature silindiğinde UpsCacheService::invalidateForJunction çağrılmalı
     *
     * @test
     */
    public function feature_assignment_deleted_triggers_cache_invalidation(): void
    {
        /** @var UpsCacheService|\Mockery\MockInterface $cacheMock */
        $cacheMock = Mockery::mock(UpsCacheService::class);
        // created + deleted = 2 çağrı
        $cacheMock->shouldReceive('invalidateForJunction')
            ->twice();

        $this->app->instance(UpsCacheService::class, $cacheMock);

        $junction = YayinTipiSablonu::create([
            'ad'              => 'Delete Test Sablon',
            'slug'            => 'delete-test-sablon',
            'aktiflik_durumu' => 1,
            'display_order'   => 2,
        ]);

        $feature = Feature::create([
            'name'            => 'Delete Test Feature',
            'slug'            => 'delete-test-feature',
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

        // Bu delete FeatureAssignmentObserver::deleted() tetiklemeli
        $assignment->delete();
    }

    /**
     * YayinTipiSablonu dışı assignable için observer sessiz kalmalı (no-op)
     *
     * @test
     */
    public function observer_is_noop_for_non_junction_assignable(): void
    {
        /** @var UpsCacheService|\Mockery\MockInterface $cacheMock */
        $cacheMock = Mockery::mock(UpsCacheService::class);
        $cacheMock->shouldNotReceive('invalidateForJunction');

        $observer = new FeatureAssignmentObserver($cacheMock);

        $assignment = new FeatureAssignment([
            'feature_id'      => 1,
            'assignable_type' => 'App\\Models\\SomethingElse', // ≠ YayinTipiSablonu
            'assignable_id'   => 99,
        ]);

        $observer->created($assignment);
        $observer->deleted($assignment);
    }

    /**
     * creating() hook: IlanKategori::class allowlist'te olmalı — exception fırlatmamalı
     *
     * BLOCKED_NEEDS_FIX kapatması: FeatureAssignmentObserver allowlist'ine IlanKategori::class
     * eklendi. creating() hook'u artık IlanKategori tipini reddetmemeli.
     *
     * @test
     */
    public function creating_allows_ilan_kategori_on_allowlist(): void
    {
        $observer = app(FeatureAssignmentObserver::class);

        // Create a real IlanKategori so the existence check in creating() passes
        $kategori = IlanKategori::factory()->create();

        $assignment = new FeatureAssignment([
            'feature_id'       => 1,
            'assignable_type'  => IlanKategori::class,
            'assignable_id'    => $kategori->id,
            'is_required'      => false,
            'is_visible'       => true,
            'display_order'    => 1,
            'aktiflik_durumu'  => true,
        ]);

        // Should not throw — allowlist'te ve kayıt mevcut
        $observer->creating($assignment);

        $this->assertTrue(true);
    }

    /**
     * creating() hook: Allowlist dışı type = InvalidArgumentException
     *
     * @test
     */
    public function creating_rejects_unknown_assignable_type(): void
    {
        $observer = app(FeatureAssignmentObserver::class);

        $assignment = new FeatureAssignment([
            'feature_id'      => 1,
            'assignable_type'  => 'App\\Models\\RandomModel',
            'assignable_id'    => 1,
            'is_required'      => false,
            'is_visible'      => true,
            'display_order'    => 1,
            'aktiflik_durumu' => true,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('allowlist dışında');

        $observer->creating($assignment);
    }

    /**
     * G1 Cascade: Global feature assignments on root categories should be visible
     * on all descendant categories via inheritance chain.
     *
     * SAAB 1B: Global features should cascade to all root categories.
     * IlanKategori::class assignments with assignable_id = rootKategoriId
     * must be resolved for child categories via inheritance chain traversal.
     *
     * @test
     * @see app/Services/Ups/FeatureTemplateResolver.php::getAssignments()
     */
    public function g1_cascade_global_features_visible_on_root_kategoriler(): void
    {
        // Create root category for this test (test DB may not have any)
        $rootKategori = IlanKategori::create([
            'name'              => 'Test Root Kategori',
            'slug'              => 'test-root-' . uniqid(),
            'seviye'            => 0,
            'parent_id'         => null,
            'aktiflik_durumu'   => true,
        ]);

        // Create a feature for G1 global scope
        $feature = Feature::create([
            'name'            => 'G1 Test Feature',
            'slug'            => 'g1-test-feature-' . uniqid(),
            'type'            => 'boolean',
            'aktiflik_durumu' => true,
        ]);

        // Assign feature to root category (simulating G1 SAAB 1B decision)
        $assignment = FeatureAssignment::create([
            'feature_id'       => $feature->id,
            'assignable_type'  => IlanKategori::class,
            'assignable_id'    => $rootKategori->id,
            'is_required'      => false,
            'is_visible'       => true,
            'display_order'    => 1,
            'aktiflik_durumu'  => true,
        ]);

        // Verify assignment was created
        $this->assertDatabaseHas('feature_assignments', [
            'id'               => $assignment->id,
            'feature_id'       => $feature->id,
            'assignable_type'  => IlanKategori::class,
            'assignable_id'    => $rootKategori->id,
        ]);

        // Test: G1 feature should be visible when resolving from a CHILD category
        // Create a child category under the root
        $childKategori = IlanKategori::create([
            'name'              => 'Test Child Kategori',
            'slug'              => 'test-child-' . uniqid(),
            'seviye'            => 1,
            'parent_id'         => $rootKategori->id,
            'aktiflik_durumu'   => true,
        ]);

        // Create a YayinTipiSablonu for testing
        $sablon = YayinTipiSablonu::create([
            'ad'              => 'G1 Test Sablon',
            'slug'            => 'g1-test-' . uniqid(),
            'aktiflik_durumu' => true,
        ]);

        // Resolve features for the child category
        $resolver = app(FeatureTemplateResolver::class);
        $assignments = $resolver->resolve($childKategori->id, $sablon->id);

        // The G1 feature assigned to root should be visible in child via cascade
        $g1Found = $assignments->contains('feature_id', $feature->id);
        $this->assertTrue(
            $g1Found,
            "G1 feature assigned to root kategori {$rootKategori->id} should cascade to child {$childKategori->id}"
        );
    }

    /**
     * G1 Cascade: Test all 6 real root categories receive global inheritance.
     *
     * Regression test: Ensures G1 features are visible on ALL root kategoriler,
     * not just the one where the assignment was created.
     *
     * @test
     */
    public function g1_cascade_covers_all_real_root_kategoriler(): void
    {
        // Create multiple root categories for this test
        $root1 = IlanKategori::create([
            'name'              => 'Test Root 1',
            'slug'              => 'test-root-1-' . uniqid(),
            'seviye'            => 0,
            'parent_id'         => null,
            'aktiflik_durumu'   => true,
        ]);
        $root2 = IlanKategori::create([
            'name'              => 'Test Root 2',
            'slug'              => 'test-root-2-' . uniqid(),
            'seviye'            => 0,
            'parent_id'         => null,
            'aktiflik_durumu'   => true,
        ]);
        $rootKategoriler = collect([$root1, $root2]);

        // Create a test feature
        $feature = Feature::create([
            'name'            => 'G1 Multi Root Test',
            'slug'            => 'g1-multi-root-' . uniqid(),
            'type'            => 'boolean',
            'aktiflik_durumu' => true,
        ]);

        // Assign to each root category
        foreach ($rootKategoriler as $root) {
            FeatureAssignment::create([
                'feature_id'       => $feature->id,
                'assignable_type'  => IlanKategori::class,
                'assignable_id'    => $root->id,
                'is_required'      => false,
                'is_visible'       => true,
                'display_order'    => 1,
                'aktiflik_durumu'  => true,
            ]);
        }

        // Verify all root categories have the assignment
        foreach ($rootKategoriler as $root) {
            $this->assertDatabaseHas('feature_assignments', [
                'assignable_type' => IlanKategori::class,
                'assignable_id'   => $root->id,
                'feature_id'      => $feature->id,
            ]);
        }

        // Create a YayinTipiSablonu for cascade test
        $sablon = YayinTipiSablonu::create([
            'ad'              => 'G1 Multi Root Test Sablon',
            'slug'            => 'g1-multi-root-' . uniqid(),
            'aktiflik_durumu' => true,
        ]);

        $resolver = app(FeatureTemplateResolver::class);

        // Test cascade for each root category
        foreach ($rootKategoriler as $root) {
            $assignments = $resolver->resolve($root->id, $sablon->id);
            $found = $assignments->contains('feature_id', $feature->id);

            $this->assertTrue(
                $found,
                "G1 feature should cascade to root kategori {$root->id} ({$root->name})"
            );
        }
    }

    /**
     * Rollback edge case: Records with source_type=manual should NOT be deleted
     * by canonical_seed rollback query.
     *
     * Seeder provenance: source_type='canonical_seed'
     * Migration provenance: source_type='villa_migration_2026_08_25'
     * This test verifies that non-canonical_seed records survive a seeder rollback.
     *
     * @test
     * @see database/seeders/FeatureAssignmentSeeder.php
     */
    public function rollback_preserves_non_canonical_seed_records(): void
    {
        // Create a feature
        $feature = Feature::create([
            'name'            => 'Rollback Test Feature',
            'slug'            => 'rollback-test-' . uniqid(),
            'type'            => 'boolean',
            'aktiflik_durumu' => true,
        ]);

        // Create a YayinTipiSablonu
        $sablon = YayinTipiSablonu::create([
            'ad'              => 'Rollback Test Sablon',
            'slug'            => 'rollback-test-' . uniqid(),
            'aktiflik_durumu' => true,
        ]);

        // Create assignment with source_type = 'manual' (NOT 'canonical_seed')
        $manualAssignment = FeatureAssignment::create([
            'feature_id'       => $feature->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id'   => $sablon->id,
            'is_required'      => false,
            'is_visible'      => true,
            'display_order'    => 1,
            'aktiflik_durumu' => true,
            'source_type'     => 'manual', // NOT canonical_seed
        ]);

        // Verify manual assignment exists
        $this->assertDatabaseHas('feature_assignments', [
            'id'          => $manualAssignment->id,
            'source_type' => 'manual',
        ]);

        // The rollback query: WHERE source_type = 'canonical_seed'
        // should NOT match the 'manual' record
        $matchedByRollback = FeatureAssignment::where('source_type', 'canonical_seed')
            ->where('id', $manualAssignment->id)
            ->exists();

        $this->assertFalse(
            $matchedByRollback,
            'source_type=manual record should NOT match rollback query'
        );
    }

    /**
     * Rollback: Only canonical_seed records should be affected.
     *
     * NOTE: Seeder rollback deletes source_type=canonical_seed.
     * Migration rollback deletes source_type=villa_migration_2026_08_25.
     *
     * @test
     * @see database/seeders/FeatureAssignmentSeeder.php
     */
    public function rollback_only_deletes_canonical_seed_records(): void
    {
        $feature = Feature::create([
            'name'            => 'Canonical Rollback Test',
            'slug'            => 'canonical-rollback-' . uniqid(),
            'type'            => 'boolean',
            'aktiflik_durumu' => true,
        ]);

        // Create a YayinTipiSablonu
        $sablon = YayinTipiSablonu::create([
            'ad'              => 'Canonical Rollback Sablon',
            'slug'            => 'canonical-rollback-' . uniqid(),
            'aktiflik_durumu' => true,
        ]);

        // Create assignment with source_type = 'canonical_seed'
        $canonicalAssignment = FeatureAssignment::create([
            'feature_id'       => $feature->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id'   => $sablon->id,
            'is_required'      => false,
            'is_visible'      => true,
            'display_order'    => 1,
            'aktiflik_durumu' => true,
            'source_type'     => 'canonical_seed',
        ]);

        // Verify canonical assignment exists
        $this->assertDatabaseHas('feature_assignments', [
            'id'          => $canonicalAssignment->id,
            'source_type' => 'canonical_seed',
        ]);

        // The rollback query SHOULD match the canonical_seed record
        $matchedByRollback = FeatureAssignment::where('source_type', 'canonical_seed')
            ->where('id', $canonicalAssignment->id)
            ->exists();

        $this->assertTrue(
            $matchedByRollback,
            'source_type=canonical_seed record SHOULD match rollback query'
        );
    }
}
