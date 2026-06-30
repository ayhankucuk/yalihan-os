<?php

namespace Tests\Feature;

use App\Services\Wizard\PricingSuggestionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Helpers\TestFixtureHelper;
use App\Models\Ilan;

/**
 * PricingSuggestionService — unit-level acceptance tests.
 *
 * Covers: happy path, category/il guard, comparable fallback,
 * confidence scaling, m²-based pricing, empty-result scenario.
 *
 * Uses direct DB seeding of the `ilanlar` table to keep tests
 * deterministic and free of AI provider calls.
 */
class PricingSuggestionServiceTest extends TestCase
{
    use TestFixtureHelper;

    private PricingSuggestionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        \Illuminate\Database\Eloquent\Model::unguard();

        // 🛡️ Manual Hygiene Cleanup (Total Isolation)
        DB::table('ilanlar')->delete();
        // Note: We don't delete lookup tables like iller/kategoriler 
        // because ensureX handles them idempotently and deleting them 
        // might cause foreign key issues if not handled carefully.
        
        $this->service = app(PricingSuggestionService::class);
    }

    // ── GUARD CONDITIONS ─────────────────────────────────────────

    /** @test */
    public function returns_failure_when_category_id_is_missing(): void
    {
        $il = $this->ensureIl(48);
        $result = $this->service->suggest([
            'il_id' => $il->id,
            // ana_kategori_id missing
        ]);

        $this->assertFalse($result['basarili']);
        $this->assertArrayHasKey('hata_mesaji', $result);
    }

    /** @test */
    public function returns_failure_when_il_id_is_missing(): void
    {
        $kategori = $this->ensureKategori('konut');
        $result = $this->service->suggest([
            'ana_kategori_id' => $kategori->id,
            // il_id missing
        ]);

        $this->assertFalse($result['basarili']);
        $this->assertArrayHasKey('hata_mesaji', $result);
    }

    /** @test */
    public function returns_failure_when_no_comparable_listings_exist(): void
    {
        $il = $this->ensureIl(48);
        $result = $this->service->suggest([
            'ana_kategori_id' => 9999, // no listings for this category
            'il_id' => $il->id,
        ]);

        $this->assertFalse($result['basarili']);
    }

    // ── HAPPY PATH ───────────────────────────────────────────────

    /** @test */
    public function returns_suggestion_when_comparable_listings_exist(): void
    {
        $il = $this->ensureIl(48);
        $kategori = $this->ensureKategori('konut');
        $this->seedActiveListings(ilId: $il->id, categoryId: $kategori->id, count: 10, basePrice: 2_000_000);

        $result = $this->service->suggest([
            'ana_kategori_id' => $kategori->id,
            'il_id' => $il->id,
        ]);

        $this->assertTrue($result['basarili']);
        $this->assertGreaterThan(0, $result['suggested_price']);
    }

    /** @test */
    public function response_includes_correct_comparable_count(): void
    {
        $il = $this->ensureIl(48);
        $kategori = $this->ensureKategori('ticari');
        $this->seedActiveListings(ilId: $il->id, categoryId: $kategori->id, count: 7, basePrice: 3_000_000);

        $result = $this->service->suggest([
            'ana_kategori_id' => $kategori->id,
            'il_id' => $il->id,
        ]);

        $this->assertTrue($result['basarili']);
        $this->assertEquals(7, $result['comparable_count']);
    }

    // ── CONFIDENCE SCALING ───────────────────────────────────────

    /** @test */
    public function confidence_is_0_35_when_fewer_than_5_comparables(): void
    {
        $il = $this->ensureIl(48);
        $kategori = $this->ensureKategori('arsa');
        $this->seedActiveListings(ilId: $il->id, categoryId: $kategori->id, count: 3, basePrice: 1_500_000);

        $result = $this->service->suggest([
            'ana_kategori_id' => $kategori->id,
            'il_id' => $il->id,
        ]);

        $this->assertTrue($result['basarili']);
        $this->assertEquals(0.35, $result['confidence']);
    }

    /** @test */
    public function confidence_is_0_55_when_between_5_and_14_comparables(): void
    {
        $il = $this->ensureIl(48);
        $kategori = $this->ensureKategori('bina');
        $this->seedActiveListings(ilId: $il->id, categoryId: $kategori->id, count: 8, basePrice: 2_000_000);

        $result = $this->service->suggest([
            'ana_kategori_id' => $kategori->id,
            'il_id' => $il->id,
        ]);

        $this->assertTrue($result['basarili']);
        $this->assertEquals(0.55, $result['confidence']);
    }

    /** @test */
    public function confidence_is_0_70_when_between_15_and_29_comparables(): void
    {
        $il = $this->ensureIl(48);
        $kategori = $this->ensureKategori('devre-mulk');
        $this->seedActiveListings(ilId: $il->id, categoryId: $kategori->id, count: 20, basePrice: 2_500_000);

        $result = $this->service->suggest([
            'ana_kategori_id' => $kategori->id,
            'il_id' => $il->id,
        ]);

        $this->assertTrue($result['basarili']);
        $this->assertEquals(0.70, $result['confidence']);
    }

    /** @test */
    public function confidence_is_0_85_when_30_or_more_comparables(): void
    {
        $il = $this->ensureIl(48);
        $kategori = $this->ensureKategori('projeler');
        $this->seedActiveListings(ilId: $il->id, categoryId: $kategori->id, count: 35, basePrice: 3_000_000);

        $result = $this->service->suggest([
            'ana_kategori_id' => $kategori->id,
            'il_id' => $il->id,
        ]);

        $this->assertTrue($result['basarili']);
        $this->assertEquals(0.85, $result['confidence']);
    }

    // ── M² BASED PRICING ────────────────────────────────────────

    /** @test */
    public function uses_m2_based_pricing_when_alan_m2_is_provided(): void
    {
        $il = $this->ensureIl(48);
        $kategori = $this->ensureKategori('daire');
        // Seed listings where all have known fiyat + alan_m2 = 100.000 TL/m²
        $this->seedActiveListingsWithArea(
            ilId: $il->id,
            categoryId: $kategori->id,
            count: 10,
            pricePerM2: 100_000,
            areaM2: 100
        );

        $result = $this->service->suggest([
            'ana_kategori_id' => $kategori->id,
            'il_id' => $il->id,
            'alan_m2' => 120, // different area than seed data
        ]);

        $this->assertTrue($result['basarili']);
        // Expected: 100_000 * 120 = 12_000_000 (rounded to nearest 100)
        $this->assertEqualsWithDelta(12_000_000, $result['suggested_price'], 10_000);
    }

    // ── ILCE FALLBACK ────────────────────────────────────────────

    /** @test */
    public function falls_back_to_il_wide_query_when_ilce_has_no_results(): void
    {
        $il = $this->ensureIl(48);
        $ilce1 = $this->ensureIlce(1, $il->id);
        $ilce2 = $this->ensureIlce(2, $il->id);
        $kategori = $this->ensureKategori('villa');

        // Seed listings for il=48 but ilce=1 (Bodrum), nothing in ilce=2 (Marmaris)
        $this->seedActiveListings(ilId: $il->id, categoryId: $kategori->id, count: 6, basePrice: 2_000_000, ilceId: $ilce1->id);

        $result = $this->service->suggest([
            'ana_kategori_id' => $kategori->id,
            'il_id' => $il->id,
            'ilce_id' => $ilce2->id, // Marmaris — no data → should fall back to il-wide
        ]);

        // Service should find il=48 listings via fallback
        $this->assertTrue($result['basarili']);
        $this->assertGreaterThan(0, $result['comparable_count']);
    }

    /** @test */
    public function min_price_is_less_than_or_equal_to_suggested_price(): void
    {
        $il = $this->ensureIl(48);
        $kategori = $this->ensureKategori('mustakil');
        $this->seedActiveListings(ilId: $il->id, categoryId: $kategori->id, count: 10, basePrice: 2_000_000);

        $result = $this->service->suggest([
            'ana_kategori_id' => $kategori->id,
            'il_id' => $il->id,
        ]);

        $this->assertLessThanOrEqual($result['suggested_price'], $result['min_price'] + 1);
    }

    /** @test */
    public function max_price_is_greater_than_or_equal_to_min_price(): void
    {
        $il = $this->ensureIl(48);
        $kategori = $this->ensureKategori('residence');
        $this->seedActiveListings(ilId: $il->id, categoryId: $kategori->id, count: 10, basePrice: 2_000_000);

        $result = $this->service->suggest([
            'ana_kategori_id' => $kategori->id,
            'il_id' => $il->id,
        ]);

        $this->assertGreaterThanOrEqual($result['min_price'], $result['max_price']);
    }

    // ── HELPERS ──────────────────────────────────────────────────

    /**
     * Seed active listings with incrementing prices around a base.
     */
    private function seedActiveListings(
        int $ilId,
        int $categoryId,
        int $count,
        int $basePrice,
        ?int $ilceId = null,
    ): void {
        for ($i = 0; $i < $count; $i++) {
            Ilan::factory()->create([
                'ana_kategori_id' => $categoryId,
                'il_id' => $ilId,
                'ilce_id' => $ilceId ?? $this->ensureIlce(1, $ilId)->id,
                'yayin_durumu' => 'yayinda',
                'fiyat' => $basePrice + ($i * 50_000),
            ]);
        }
    }

    /**
     * Seed active listings with consistent area and price-per-m2.
     */
    private function seedActiveListingsWithArea(
        int $ilId,
        int $categoryId,
        int $count,
        float $pricePerM2,
        float $areaM2,
    ): void {
        for ($i = 0; $i < $count; $i++) {
            Ilan::factory()->create([
                'ana_kategori_id' => $categoryId,
                'il_id' => $ilId,
                'ilce_id' => $this->ensureIlce(1, $ilId)->id,
                'yayin_durumu' => 'yayinda',
                'fiyat' => (int) ($pricePerM2 * $areaM2),
                'alan_m2' => $areaM2,
            ]);
        }
    }
}
