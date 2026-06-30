<?php

declare(strict_types=1);

namespace Tests\Feature\Governance;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Observers\FeatureAssignmentObserver;
use App\Services\Ups\UpsCacheService;
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
}
