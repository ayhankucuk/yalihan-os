<?php

declare(strict_types=1);

namespace Tests\Feature\Governance;

use App\Exceptions\PropertyHub\TemplateResolutionException;
use App\Models\AltKategoriYayinTipi;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\Template\TemplateService;
use App\Services\Ups\FeatureTemplateResolver;
use App\Services\Ups\UpsCacheService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * ADR-003: SSOT & Determinism Anayasası — Enforcement Tests
 *
 * Bu testler ADR-003'ün 4 temel garantisini doğrular:
 *   1. Determinizm: aynı input → aynı pivot döner (ORDER BY garantili)
 *   2. Cache Authority: clearCache tüm namespace'leri temizler
 *   3. Fallback Propagation: exception yutulmaz, caller'a iletilir
 *   4. Observer Chain: Eloquent delete → invalidateForJunction tetiklenir
 *
 * @see docs/adr/2026-02-21-ssot-determinism-constitution.md
 */
class ConstitutionDeterminismTest extends TestCase
{

    // =========================================================================
    // TEST 1 — Determinizm: Aynı input → Aynı pivot döner
    // =========================================================================

    /**
     * [ADR-003 F-1] AltKategoriYayinTipi: UNIQUE constraint (alt_kategori_id, yayin_tipi_id)
     * sayesinde aynı kombinasyon için tek pivot vardır. ORDER BY display_order, id ise
     * gelecekteki constraint değişikliklerine karşı savunmacı deterministik kodunu garantiler.
     *
     * Test: Aynı input → aynı pivot, farklı sorgularda değişmez.
     *
     * @test
     */
    public function identical_inputs_always_return_same_pivot(): void
    {
        $kategori = IlanKategori::factory()->create(['parent_id' => null]);
        $yayinTipi = YayinTipiSablonu::create([
            'ad'              => 'Test YT',
            'slug'            => 'test-yt-det-' . uniqid(),
            'aktiflik_durumu' => 1,
            'display_order'   => 1,
            'kategori_id'     => $kategori->id,
        ]);

        $pivot = AltKategoriYayinTipi::create([
            'alt_kategori_id' => $kategori->id,
            'yayin_tipi_id'   => $yayinTipi->id,
            'aktiflik_durumu' => 1,
            'display_order'   => 1,
        ]);

        // Aynı sorgu 3 kez çalıştırıldığında her seferinde aynı pivot döner
        for ($i = 0; $i < 3; $i++) {
            $result = AltKategoriYayinTipi::where('alt_kategori_id', $kategori->id)
                ->where('yayin_tipi_id', $yayinTipi->id)
                ->active()
                ->orderBy('display_order')
                ->orderBy('id')
                ->first();

            $this->assertNotNull($result, "Pivot null döndü (iterasyon {$i})");
            $this->assertEquals(
                $pivot->id,
                $result->id,
                "Farklı iterasyonlarda farklı pivot döndü — determinism ihlali (iterasyon {$i})"
            );
        }
    }

    // =========================================================================
    // TEST 2 — Cache Authority: clearCache tüm namespace'leri temizler
    // =========================================================================

    /**
     * [ADR-003 F-4] TemplateService::clearCache çağrısı:
     *   - ups:templates namespace → temizlenmeli
     *   - ups:resolver namespace → temizlenmeli
     *   - ups:feature_grouped namespace → temizlenmeli
     *
     * @test
     */
    public function clear_cache_invalidates_all_three_namespaces(): void
    {
        /** @var UpsCacheService&MockInterface $cacheMock */
        $cacheMock = Mockery::mock(UpsCacheService::class);

        // templates namespace çağrısı
        $cacheMock->shouldReceive('invalidate')
            ->with('templates', Mockery::any())
            ->once();

        // resolver namespace çağrısı (tüm resolver cache'i temizler)
        $cacheMock->shouldReceive('invalidate')
            ->with('resolver')
            ->once();

        // feature_grouped namespace çağrısı
        $cacheMock->shouldReceive('invalidate')
            ->with('feature_grouped')
            ->once();

        $this->app->instance(UpsCacheService::class, $cacheMock);
        /** @var TemplateService $service */
        $service = $this->app->make(TemplateService::class);
        $service->clearCache(kategoriId: 42);
    }

    /**
     * [ADR-003 F-4] clearCache(null) çağrısında da tüm namespace'ler temizlenmeli.
     *
     * @test
     */
    public function clear_cache_without_kategori_id_invalidates_all_namespaces(): void
    {
        /** @var UpsCacheService&MockInterface $cacheMock */
        $cacheMock = Mockery::mock(UpsCacheService::class);

        $cacheMock->shouldReceive('invalidate')
            ->with('templates', null)
            ->once();
        $cacheMock->shouldReceive('invalidate')
            ->with('resolver')
            ->once();
        $cacheMock->shouldReceive('invalidate')
            ->with('feature_grouped')
            ->once();

        $this->app->instance(UpsCacheService::class, $cacheMock);
        /** @var TemplateService $service */
        $service = $this->app->make(TemplateService::class);
        $service->clearCache(null);
    }

    // =========================================================================
    // TEST 3 — Fallback Propagation: Exception yutulmaz
    // =========================================================================

    /**
     * [ADR-003 F-3 + § Fallback Politikası]
     * FeatureTemplateResolver exception fırlattığında TemplateService
     * bu exception'ı swallow etmemeli, caller'a iletmelidir.
     *
     * @test
     */
    public function resolve_features_propagates_exception_without_swallowing(): void
    {
        /** @var FeatureTemplateResolver&MockInterface $resolverMock */
        $resolverMock = Mockery::mock(FeatureTemplateResolver::class);
        $resolverMock->shouldReceive('resolve')
            ->andThrow(new \RuntimeException('DB connection lost — test injection'));

        $this->app->instance(FeatureTemplateResolver::class, $resolverMock);
        /** @var TemplateService $service */
        $service = $this->app->make(TemplateService::class);

        // Exception fırlatılmalı — sessizce hardcoded set DÖNMEMELİ
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DB connection lost — test injection');

        // resolveTemplateFeatures private; reflection ile test et
        $ref = new \ReflectionMethod($service, 'resolveTemplateFeatures');
        $ref->setAccessible(true);
        $ref->invoke($service, 1, 1, ['required' => [], 'optional' => [], 'hidden' => [], 'fields' => []]);
    }

    /**
     * [ADR-003 F-3] getMinimalFeatureSet metodu app/ kaynak kodunda mevcut OLMAMALI.
     * (Silme doğrulaması — CI Guard RULE-3 ile paralel)
     *
     * @test
     */
    public function minimal_feature_set_method_does_not_exist_in_production(): void
    {
        $ref = new \ReflectionClass(\App\Services\Template\TemplateService::class);

        $this->assertFalse(
            $ref->hasMethod('getMinimalFeatureSet'),
            'getMinimalFeatureSet metodu TemplateService içinde hala mevcut — ADR-003 F-3 ihlali'
        );
    }

    // =========================================================================
    // TEST 4 — Observer Chain: Eloquent delete → invalidateForJunction
    // =========================================================================

    /**
     * [ADR-003 § Write-path Governance]
     * FeatureAssignment Eloquent delete → Observer::deleted() →
     * UpsCacheService::invalidateForJunction çağrılmalıdır.
     *
     * @test
     */
    public function feature_assignment_eloquent_delete_triggers_cache_invalidation(): void
    {
        /** @var UpsCacheService&MockInterface $cacheMock */
        $cacheMock = Mockery::mock(UpsCacheService::class);
        $cacheMock->shouldReceive('invalidateForJunction')
            ->once()
            ->withArgs(function (int $junctionId): bool {
                return $junctionId > 0;
            });

        $this->app->instance(UpsCacheService::class, $cacheMock);

        $junction = YayinTipiSablonu::create([
            'ad'              => 'Const Test Sablon',
            'slug'            => 'const-test-sablon',
            'aktiflik_durumu' => 1,
            'display_order'   => 1,
        ]);

        $feature = Feature::create([
            'name'            => 'Const Test Feature',
            'slug'            => 'const-test-feature',
            'type'            => 'text',
            'aktiflik_durumu' => 1,
        ]);

        $assignment = FeatureAssignment::create([
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id'   => $junction->id,
            'feature_id'      => $feature->id,
            'display_order'   => 1,
            'is_required'     => false,

        ]);

        // created() için çağrılan invalidateForJunction'ı sıfırla
        Mockery::resetContainer();
        $cacheMock2 = Mockery::mock(UpsCacheService::class);
        $cacheMock2->shouldReceive('invalidateForJunction')
            ->once()
            ->withArgs(fn(int $junctionId): bool => $junctionId === $junction->id);
        $this->app->instance(UpsCacheService::class, $cacheMock2);

        // Eloquent delete → Observer::deleted() → invalidateForJunction
        $assignment->delete();
    }
}
