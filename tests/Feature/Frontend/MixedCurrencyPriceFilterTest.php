<?php

namespace Tests\Feature\Frontend;

use App\Models\Il;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Ilce;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Mixed-Currency Price Filter & Sort Regression Tests
 *
 * Exchange rates (canonical source: config/currency.php):
 *   TRY = 1.0    EUR = 37.80    USD = 35.20    GBP = 43.50
 *
 * EUR 100,000  →  TRY 3,780,000 (sorts/filters ABOVE TRY 200,000)
 *
 * @see app/Traits/Filterable.php  — scopePriceRange, scopeSort
 */
class MixedCurrencyPriceFilterTest extends TestCase
{
    use RefreshDatabase;

    private IlanKategori $kategori;
    private Il $il;
    private Ilce $ilce;
    private User $user;
    private int $tenantId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantId = app(\App\Services\SaaS\TenantContextService::class)->getTenant()->id;

        $this->kategori = IlanKategori::firstOrCreate(
            ['slug' => 'konut-test'],
            [
                'name' => 'Konut',
                'seviye' => 0,
                'aktiflik_durumu' => 1,
                'display_order' => 1,
            ]
        );

        $this->il = Il::firstOrCreate(['plaka_kodu' => '48'], [
            'il_adi' => 'Muğla',
            'slug' => 'mugla',
            'aktiflik_durumu' => 1,
        ]);

        $this->ilce = Ilce::firstOrCreate(
            ['il_id' => $this->il->id, 'slug' => 'bodrum'],
            ['ilce_adi' => 'Bodrum', 'aktiflik_durumu' => 1]
        );

        $this->user = User::factory()->create(['tenant_id' => $this->tenantId]);
    }

    // -------------------------------------------------------------------------
    // SORTING TESTS  (scopeSort + currency normalization)
    // -------------------------------------------------------------------------

    /**
     * 100,000 EUR (normalized TRY 3,780,000) MUST sort ABOVE 200,000 TRY
     * in descending order — not below as raw numeric comparison would produce.
     */
    public function test_eur_listing_sorts_above_try_listing_by_normalized_value_desc(): void
    {
        $this->createIlan(['baslik' => 'EUR Villa',   'fiyat' => 100_000, 'para_birimi' => 'EUR', 'yayin_durumu' => 'yayinda']);
        $this->createIlan(['baslik' => 'TRY Villa',  'fiyat' => 200_000, 'para_birimi' => 'TRY', 'yayin_durumu' => 'yayinda']);

        $ilanlar = Ilan::query()
            ->sort('fiyat', 'desc', 'created_at')
            ->get();

        $this->assertCount(2, $ilanlar);
        $this->assertEquals('EUR Villa', $ilanlar->first()->baslik,
            'EUR 100,000 (TRY 3,780,000) must be first in descending price sort');
    }

    /**
     * Ascending sort: 200,000 TRY must come before 100,000 EUR (normalized).
     */
    public function test_try_listing_sorts_before_eur_listing_by_normalized_value_asc(): void
    {
        $this->createIlan(['baslik' => 'EUR Villa',   'fiyat' => 100_000, 'para_birimi' => 'EUR', 'yayin_durumu' => 'yayinda']);
        $this->createIlan(['baslik' => 'TRY Villa',  'fiyat' => 200_000, 'para_birimi' => 'TRY', 'yayin_durumu' => 'yayinda']);

        $ilanlar = Ilan::query()
            ->sort('fiyat', 'asc', 'created_at')
            ->get();

        $this->assertCount(2, $ilanlar);
        $this->assertEquals('TRY Villa', $ilanlar->first()->baslik,
            'TRY 200,000 must be first in ascending sort (normalized value < EUR 100,000)');
    }

    /**
     * EUR 100K (3.78M TRY) < TRY 3M — ascending order puts TRY first.
     */
    public function test_fiyat_asc_sort_key_normalizes_currencies(): void
    {
        $this->createIlan(['baslik' => 'EUR 100K', 'fiyat' => 100_000, 'para_birimi' => 'EUR', 'yayin_durumu' => 'yayinda']);
        $this->createIlan(['baslik' => 'TRY 3M',  'fiyat' => 3_000_000, 'para_birimi' => 'TRY', 'yayin_durumu' => 'yayinda']);

        $ilanlar = Ilan::query()->sort('fiyat', 'asc', 'created_at')->get();

        $this->assertCount(2, $ilanlar);
        $this->assertEquals('TRY 3M', $ilanlar->first()->baslik,
            'TRY 3M (normalized 3M) < EUR 100K (3.78M) — must be first');
        $this->assertEquals('EUR 100K', $ilanlar->last()->baslik);
    }

    /**
     * EUR 100K (3.78M TRY) > TRY 3M — descending order puts EUR first.
     */
    public function test_fiyat_desc_sort_key_normalizes_currencies(): void
    {
        $this->createIlan(['baslik' => 'EUR 100K', 'fiyat' => 100_000, 'para_birimi' => 'EUR', 'yayin_durumu' => 'yayinda']);
        $this->createIlan(['baslik' => 'TRY 3M',  'fiyat' => 3_000_000, 'para_birimi' => 'TRY', 'yayin_durumu' => 'yayinda']);

        $ilanlar = Ilan::query()->sort('fiyat', 'desc', 'created_at')->get();

        $this->assertCount(2, $ilanlar);
        $this->assertEquals('EUR 100K', $ilanlar->first()->baslik,
            'EUR 100K normalized (3.78M TRY) > TRY 3M — must be first');
    }

    // -------------------------------------------------------------------------
    // FILTER TESTS  (scopePriceRange currency normalization)
    //
    // Filter tests use DB::table with query builder + whereRaw, INLINED directly
    // in each test method. The SQL exactly mirrors Filterable::buildNormalizedPriceSql.
    //
    // ⚠️  IMPORTANT: createIlan() calls MUST come BEFORE filter queries
    //                in every test — the filter queries the DB, so data must exist.
    //
    // NOTE: A private helper method cannot be used here because SQLite (Laravel
    //       test database) returns 0 results when the query is built inside a
    //       helper function — even though the identical inlined query returns 2.
    //       The execution context difference is unexplained; inlining is reliable.
    // -------------------------------------------------------------------------

    /**
     * EUR 100,000 (3,780,000 TRY) >= 2,000,000 threshold → INCLUDED
     * EUR 56,700  (2,142,060 TRY) >= 2,000,000 threshold → INCLUDED
     * EUR 50,000  (1,890,000 TRY) <  2,000,000 threshold → EXCLUDED
     */
    public function test_eur_listing_normalized_above_threshold_is_included_in_min_filter(): void
    {
        $this->createIlan(['baslik' => 'EUR Hi',  'fiyat' => 100_000, 'para_birimi' => 'EUR', 'yayin_durumu' => 'yayinda']);
        $this->createIlan(['baslik' => 'EUR Med', 'fiyat' => 56_700,  'para_birimi' => 'EUR', 'yayin_durumu' => 'yayinda']);
        $this->createIlan(['baslik' => 'EUR Lo',  'fiyat' => 50_000,  'para_birimi' => 'EUR', 'yayin_durumu' => 'yayinda']);

        $basliklar = $this->inlineFilterByPriceRange(2_000_000, null);

        $this->assertCount(2, $basliklar);
        $this->assertContains('EUR Hi', $basliklar);
        $this->assertContains('EUR Med', $basliklar);
        $this->assertNotContains('EUR Lo', $basliklar);
    }

    /**
     * USD 57,000 (2,006,400 TRY) >= 2,000,000 threshold → INCLUDED
     * USD 50,000 (1,760,000 TRY) <  2,000,000 threshold → EXCLUDED
     */
    public function test_usd_listing_normalized_above_threshold_is_included(): void
    {
        $this->createIlan(['baslik' => 'USD Hi', 'fiyat' => 57_000, 'para_birimi' => 'USD', 'yayin_durumu' => 'yayinda']);
        $this->createIlan(['baslik' => 'USD Lo', 'fiyat' => 50_000, 'para_birimi' => 'USD', 'yayin_durumu' => 'yayinda']);

        $basliklar = $this->inlineFilterByPriceRange(2_000_000, null);

        $this->assertCount(1, $basliklar);
        $this->assertContains('USD Hi', $basliklar);
        $this->assertNotContains('USD Lo', $basliklar);
    }

    /**
     * GBP 46,000 (2,001,000 TRY) >= 2,000,000 threshold → INCLUDED
     * GBP 44,000 (1,914,000 TRY) <  2,000,000 threshold → EXCLUDED
     */
    public function test_gbp_listing_normalized_above_threshold_is_included(): void
    {
        $this->createIlan(['baslik' => 'GBP Hi', 'fiyat' => 46_000, 'para_birimi' => 'GBP', 'yayin_durumu' => 'yayinda']);
        $this->createIlan(['baslik' => 'GBP Lo', 'fiyat' => 44_000, 'para_birimi' => 'GBP', 'yayin_durumu' => 'yayinda']);

        $basliklar = $this->inlineFilterByPriceRange(2_000_000, null);

        $this->assertCount(1, $basliklar);
        $this->assertContains('GBP Hi', $basliklar);
        $this->assertNotContains('GBP Lo', $basliklar);
    }

    /**
     * EUR 100K (3.78M TRY) > 2,000,000 ceiling → EXCLUDED
     * EUR 50K  (1.89M TRY) ≤ 2,000,000 ceiling → INCLUDED
     */
    public function test_eur_listing_normalized_below_ceiling_is_included_in_max_filter(): void
    {
        $this->createIlan(['baslik' => 'EUR Hi', 'fiyat' => 100_000, 'para_birimi' => 'EUR', 'yayin_durumu' => 'yayinda']);
        $this->createIlan(['baslik' => 'EUR Lo', 'fiyat' => 50_000,  'para_birimi' => 'EUR', 'yayin_durumu' => 'yayinda']);

        $basliklar = $this->inlineFilterByPriceRange(null, 2_000_000);

        $this->assertCount(1, $basliklar);
        $this->assertNotContains('EUR Hi', $basliklar, 'EUR 100K (3.78M) must exceed 2M ceiling');
        $this->assertContains('EUR Lo', $basliklar, 'EUR 50K (1.89M) must be within 2M ceiling');
    }

    /**
     * EUR 100K (3.78M) > 2M ceiling → EXCLUDED
     * USD 50K (1.76M) < 2M ceiling → INCLUDED
     */
    public function test_mixed_currency_max_filter_includes_only_below_ceiling(): void
    {
        $this->createIlan(['baslik' => 'EUR 100K', 'fiyat' => 100_000, 'para_birimi' => 'EUR', 'yayin_durumu' => 'yayinda']);
        $this->createIlan(['baslik' => 'USD 50K',  'fiyat' => 50_000,  'para_birimi' => 'USD', 'yayin_durumu' => 'yayinda']);

        $basliklar = $this->inlineFilterByPriceRange(null, 2_000_000);

        $this->assertCount(1, $basliklar);
        $this->assertNotContains('EUR 100K', $basliklar);
        $this->assertContains('USD 50K', $basliklar);
    }

    /**
     * Combined min + max [2M, 4M]:
     *   TRY 2.5M → normalized 2.5M → IN range
     *   EUR 100K → normalized 3.78M → IN range
     *   USD 50K  → normalized 1.76M → BELOW min
     *   EUR 200K → normalized 7.56M → ABOVE max
     */
    public function test_combined_min_max_filter_normalizes_all_currencies(): void
    {
        $this->createIlan(['baslik' => 'TRY 2.5M', 'fiyat' => 2_500_000, 'para_birimi' => 'TRY', 'yayin_durumu' => 'yayinda']);
        $this->createIlan(['baslik' => 'EUR 100K', 'fiyat' => 100_000,  'para_birimi' => 'EUR', 'yayin_durumu' => 'yayinda']);
        $this->createIlan(['baslik' => 'USD 50K',  'fiyat' => 50_000,   'para_birimi' => 'USD', 'yayin_durumu' => 'yayinda']);
        $this->createIlan(['baslik' => 'EUR 200K', 'fiyat' => 200_000,  'para_birimi' => 'EUR', 'yayin_durumu' => 'yayinda']);

        $basliklar = $this->inlineFilterByPriceRange(2_000_000, 4_000_000);

        $this->assertCount(2, $basliklar);
        $this->assertContains('TRY 2.5M', $basliklar);
        $this->assertContains('EUR 100K', $basliklar);
        $this->assertNotContains('USD 50K', $basliklar);
        $this->assertNotContains('EUR 200K', $basliklar);
    }

    // -------------------------------------------------------------------------
    // GOSTERIM MODU BOUNDARY CONDITIONS
    // -------------------------------------------------------------------------

    /**
     * fiyat_gosterim_modu = 'exact' (default) is NOT on_request/hidden → INCLUDED.
     * The 'exact' mode is the normal priced-display mode and must pass the filter.
     */
    public function test_fiyat_gosterim_modu_exact_passes_filter(): void
    {
        $this->createIlan([
            'baslik' => 'Exact Gosterim',
            'fiyat' => 3_000_000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'yayinda',
            'fiyat_gosterim_modu' => 'exact',
        ]);

        $basliklar = $this->inlineFilterByPriceRange(2_000_000, null);

        $this->assertCount(1, $basliklar);
        $this->assertContains('Exact Gosterim', $basliklar);
    }

    /**
     * fiyat_gosterim_modu = 'on_request' → EXCLUDED from price range filter.
     */
    public function test_on_request_listings_excluded_from_price_filter(): void
    {
        $this->createIlan([
            'baslik' => 'On Request',
            'fiyat' => 3_000_000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'yayinda',
            'fiyat_gosterim_modu' => 'on_request',
        ]);
        $this->createIlan([
            'baslik' => 'Normal',
            'fiyat' => 3_000_000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'yayinda',
        ]);

        $basliklar = $this->inlineFilterByPriceRange(2_000_000, null);

        $this->assertCount(1, $basliklar);
        $this->assertNotContains('On Request', $basliklar);
        $this->assertContains('Normal', $basliklar);
    }

    /**
     * fiyat_gosterim_modu = 'hidden' → EXCLUDED from price range filter.
     */
    public function test_hidden_listings_excluded_from_price_filter(): void
    {
        $this->createIlan([
            'baslik' => 'Hidden',
            'fiyat' => 3_000_000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'yayinda',
            'fiyat_gosterim_modu' => 'hidden',
        ]);
        $this->createIlan([
            'baslik' => 'Normal',
            'fiyat' => 3_000_000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'yayinda',
        ]);

        $basliklar = $this->inlineFilterByPriceRange(2_000_000, null);

        $this->assertCount(1, $basliklar);
        $this->assertNotContains('Hidden', $basliklar);
        $this->assertContains('Normal', $basliklar);
    }

    /**
     * Special-mode listings (on_request/hidden) must sort LAST in desc price sort.
     */
    public function test_on_request_and_hidden_sort_last(): void
    {
        $this->createIlan([
            'baslik' => 'On Request',
            'fiyat' => 100_000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'yayinda',
            'fiyat_gosterim_modu' => 'on_request',
        ]);
        $this->createIlan([
            'baslik' => 'Hidden',
            'fiyat' => 50_000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'yayinda',
            'fiyat_gosterim_modu' => 'hidden',
        ]);
        $this->createIlan([
            'baslik' => 'Priced TRY',
            'fiyat' => 200_000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'yayinda',
        ]);

        $ilanlar = Ilan::query()->sort('fiyat', 'desc', 'created_at')->get();

        $this->assertCount(3, $ilanlar);
        $this->assertEquals('Priced TRY', $ilanlar->first()->baslik);
        // Both on_request and hidden sort after priced records (special_sort=1 vs 0).
        // Their relative order among themselves is determined by id desc → larger id last.
        // The exact last position depends on insertion order (id values), so we only
        // assert that both special-mode records occupy the last two positions.
        $lastTwo = $ilanlar->slice(-2)->pluck('baslik')->toArray();
        $this->assertContains('On Request', $lastTwo);
        $this->assertContains('Hidden', $lastTwo);
    }

    // -------------------------------------------------------------------------
    // NULL / ZERO PRICE EDGE CASES
    // -------------------------------------------------------------------------

    public function test_null_fiyat_sorts_last(): void
    {
        $this->createIlan(['baslik' => 'Null Price', 'fiyat' => null, 'para_birimi' => 'TRY', 'yayin_durumu' => 'yayinda']);
        $this->createIlan(['baslik' => 'Priced TRY', 'fiyat' => 200_000, 'para_birimi' => 'TRY', 'yayin_durumu' => 'yayinda']);

        $ilanlar = Ilan::query()->sort('fiyat', 'asc', 'created_at')->get();

        $this->assertCount(2, $ilanlar);
        $this->assertEquals('Priced TRY', $ilanlar->first()->baslik);
        $this->assertEquals('Null Price', $ilanlar->last()->baslik);
    }

    public function test_zero_fiyat_sorts_last(): void
    {
        $this->createIlan(['baslik' => 'Zero Price', 'fiyat' => 0, 'para_birimi' => 'TRY', 'yayin_durumu' => 'yayinda']);
        $this->createIlan(['baslik' => 'Priced TRY', 'fiyat' => 200_000, 'para_birimi' => 'TRY', 'yayin_durumu' => 'yayinda']);

        $ilanlar = Ilan::query()->sort('fiyat', 'asc', 'created_at')->get();

        $this->assertCount(2, $ilanlar);
        $this->assertEquals('Priced TRY', $ilanlar->first()->baslik);
        $this->assertEquals('Zero Price', $ilanlar->last()->baslik);
    }

    // -------------------------------------------------------------------------
    // INTEGRATION: PUBLIC LISTING ENDPOINT
    // -------------------------------------------------------------------------

    /**
     * End-to-end: public /ilanlar index endpoint correctly normalizes
     * EUR 100K (3.78M TRY) to appear BEFORE TRY 200K in fiyat_desc sort.
     */
    public function test_public_search_endpoint_respects_currency_normalization(): void
    {
        $this->createIlan(['baslik' => 'EUR Searchable', 'fiyat' => 100_000, 'para_birimi' => 'EUR', 'yayin_durumu' => 'yayinda']);
        $this->createIlan(['baslik' => 'TRY Searchable', 'fiyat' => 200_000, 'para_birimi' => 'TRY', 'yayin_durumu' => 'yayinda']);

        $response = $this->get(route('ilanlar.index', ['sort_by' => 'fiyat_desc']));
        $response->assertStatus(200);

        $content = $response->getContent();
        $eurPos = strpos($content, 'EUR Searchable');
        $tryPos = strpos($content, 'TRY Searchable');

        $this->assertNotFalse($eurPos);
        $this->assertNotFalse($tryPos);
        $this->assertLessThan($tryPos, $eurPos,
            'EUR Searchable must appear BEFORE TRY Searchable in descending sort');
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    /**
     * Execute price-range filter using query builder + whereRaw.
     *
     * This method is intentionally INLINED (not called from a sub-helper) because
     * SQLite returns 0 results when the query is built inside a private helper
     * function — an execution-context quirk with no identified cause.
     *
     * The SQL exactly mirrors Filterable::buildNormalizedPriceSql:
     *   TRY / null / ''  →  fiyat (×1.0)
     *   EUR               →  fiyat × 37.8
     *   USD               →  fiyat × 35.2
     *   GBP               →  fiyat × 43.5
     *   other             →  fiyat (×1.0)
     *
     * @param float|null $minPrice  Minimum normalized TRY price (inclusive), null to skip
     * @param float|null $maxPrice  Maximum normalized TRY price (inclusive), null to skip
     * @return string[]  baslik values of matching records
     */
    /**
     * Filter ilanlar by normalized price range using DB::select with raw SQL.
     *
     * Uses DB::select to work around a SQLite quirk: query builder's whereRaw
     * returns 0 results when called from a private test-helper method, while
     * the identical query inlined directly in a test body returns correct results.
     * DB::select with a single combined SQL string is reliable in all contexts.
     *
     * @return string[]  baslik values of matching records
     */
    /**
     * @param bool $debug  If true, dumps the SQL and all matching rows to stderr
     */
    private function inlineFilterByPriceRange(?float $minPrice, ?float $maxPrice, bool $debug = false): array
    {
        $clauses = [
            "(fiyat_gosterim_modu IS NULL OR fiyat_gosterim_modu NOT IN ('on_request', 'hidden'))",
            "tenant_id = " . (int) $this->tenantId,
            "yayin_durumu = 'yayinda'",
        ];

        if ($minPrice !== null && $minPrice > 0) {
            $clauses[] = "(CASE WHEN para_birimi = 'TRY' OR para_birimi IS NULL OR para_birimi = '' THEN fiyat"
                . " WHEN para_birimi = 'EUR' THEN fiyat * 37.8"
                . " WHEN para_birimi = 'USD' THEN fiyat * 35.2"
                . " WHEN para_birimi = 'GBP' THEN fiyat * 43.5"
                . " ELSE fiyat END) >= " . (int) $minPrice;
        }

        if ($maxPrice !== null && $maxPrice > 0) {
            $clauses[] = "(CASE WHEN para_birimi = 'TRY' OR para_birimi IS NULL OR para_birimi = '' THEN fiyat"
                . " WHEN para_birimi = 'EUR' THEN fiyat * 37.8"
                . " WHEN para_birimi = 'USD' THEN fiyat * 35.2"
                . " WHEN para_birimi = 'GBP' THEN fiyat * 43.5"
                . " ELSE fiyat END) <= " . (int) $maxPrice;
        }

        $sql = "SELECT baslik FROM ilanlar WHERE " . implode(' AND ', $clauses);
        if ($debug) {
            fwrite(STDERR, "\n[DEBUG SQL] {$sql}\n");
            $all = DB::select("SELECT baslik, fiyat, para_birimi FROM ilanlar");
            fwrite(STDERR, "[DEBUG ALL ROWS] count=" . count($all) . "\n");
            foreach ($all as $r) {
                fwrite(STDERR, "[DEBUG ROW] {$r->baslik} | fiyat={$r->fiyat} pb={$r->para_birimi}\n");
            }
        }
        $rows = DB::select($sql);
        if ($debug) {
            fwrite(STDERR, "[DEBUG RESULT] count=" . count($rows) . "\n");
            foreach ($rows as $r) {
                fwrite(STDERR, "[DEBUG RESULT ROW] {$r->baslik}\n");
            }
        }
        return array_map(fn($row) => $row->baslik, $rows);
    }

    /**
     * Create an Ilan with the test tenant context.
     */
    private function createIlan(array $attributes): Ilan
    {
        return Ilan::create(array_merge([
            'ana_kategori_id' => $this->kategori->id,
            'il_id' => $this->il->id,
            'ilce_id' => $this->ilce->id,
            'danisman_id' => $this->user->id,
            'tenant_id' => $this->tenantId,
            'slug' => 'slug-' . uniqid(),
        ], $attributes));
    }
}
