<?php

namespace Tests\Unit\CommandCenter;

use App\Services\SaaS\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 1j Currency Boundary Regression Tests
 *
 * Verifies that IlanSearchService::search() with paraBirimi filter
 * does NOT produce cross-currency price comparisons.
 *
 * Canonical contract: when currency is supplied, results MUST be
 * restricted to that currency only.
 */
class IlanSearchServiceCurrencyBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectDefaultTenantContext();
    }

    /**
     * Helper: create a published listing directly via DB (bypassing model events).
     * Sets tenant_id from the current tenant context so tenant-isolated search() finds it.
     */
    private function createListing(array $overrides): int
    {
        $tenant = app(TenantContextService::class)->getTenant();

        $defaults = [
            'baslik' => 'Test Ilan',
            'slug' => 'test-ilan-' . uniqid(),
            'fiyat' => 1000000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
            'tenant_id' => $tenant->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('ilanlar')->insert(array_merge($defaults, $overrides));

        return (int) DB::table('ilanlar')->max('id');
    }

    // -------------------------------------------------------------------------
    // TEST CONTRACT: EUR max budget 1,000,000
    // -------------------------------------------------------------------------

    public function test_eur_900k_included_when_max_1m_currency_eur(): void
    {
        $ilanId = $this->createListing([
            'baslik' => 'EUR 900K Villa',
            'fiyat' => 900000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        $service = app(\App\Services\Ilan\IlanSearchService::class);
        $result = $service->search([
            'yayin_durumu' => 'yayinda',
            'maxFiyat' => 1_000_000,
            'paraBirimi' => 'EUR',
        ]);

        $ids = collect($result['data'])->pluck('id')->toArray();
        $this->assertContains($ilanId, $ids, 'EUR 900K listing must be included in EUR 1M budget');
    }

    public function test_eur_1m_included_at_boundary(): void
    {
        $ilanId = $this->createListing([
            'baslik' => 'EUR 1M Villa',
            'fiyat' => 1_000_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        $service = app(\App\Services\Ilan\IlanSearchService::class);
        $result = $service->search([
            'yayin_durumu' => 'yayinda',
            'maxFiyat' => 1_000_000,
            'paraBirimi' => 'EUR',
        ]);

        $ids = collect($result['data'])->pluck('id')->toArray();
        $this->assertContains($ilanId, $ids, 'EUR 1M listing must be included at boundary');
    }

    public function test_eur_1m_plus_1_excluded(): void
    {
        $ilanId = $this->createListing([
            'baslik' => 'EUR 1M+1 Villa',
            'fiyat' => 1_000_001,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        $service = app(\App\Services\Ilan\IlanSearchService::class);
        $result = $service->search([
            'yayin_durumu' => 'yayinda',
            'maxFiyat' => 1_000_000,
            'paraBirimi' => 'EUR',
        ]);

        $ids = collect($result['data'])->pluck('id')->toArray();
        $this->assertNotContains($ilanId, $ids, 'EUR 1,000,001 listing must be excluded from EUR 1M budget');
    }

    // -------------------------------------------------------------------------
    // CROSS-CURRENCY EXCLUSION: TRY and USD MUST NOT appear in EUR results
    // -------------------------------------------------------------------------

    public function test_try_800k_excluded_from_eur_budget(): void
    {
        $ilanId = $this->createListing([
            'baslik' => 'TRY 800K Villa',
            'fiyat' => 800_000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'yayinda',
        ]);

        $service = app(\App\Services\Ilan\IlanSearchService::class);
        $result = $service->search([
            'yayin_durumu' => 'yayinda',
            'maxFiyat' => 1_000_000,
            'paraBirimi' => 'EUR',
        ]);

        $ids = collect($result['data'])->pluck('id')->toArray();
        $this->assertNotContains($ilanId, $ids, 'TRY 800K listing must NOT appear in EUR budget results');
    }

    public function test_usd_700k_excluded_from_eur_budget(): void
    {
        $ilanId = $this->createListing([
            'baslik' => 'USD 700K Villa',
            'fiyat' => 700_000,
            'para_birimi' => 'USD',
            'yayin_durumu' => 'yayinda',
        ]);

        $service = app(\App\Services\Ilan\IlanSearchService::class);
        $result = $service->search([
            'yayin_durumu' => 'yayinda',
            'maxFiyat' => 1_000_000,
            'paraBirimi' => 'EUR',
        ]);

        $ids = collect($result['data'])->pluck('id')->toArray();
        $this->assertNotContains($ilanId, $ids, 'USD 700K listing must NOT appear in EUR budget results');
    }

    // -------------------------------------------------------------------------
    // PUBLICATION STATE INVARIANT
    // -------------------------------------------------------------------------

    public function test_unpublished_eur_listing_excluded_from_budget(): void
    {
        $ilanId = $this->createListing([
            'baslik' => 'Unpublished EUR Villa',
            'fiyat' => 500_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'taslak', // not yayinda
        ]);

        $service = app(\App\Services\Ilan\IlanSearchService::class);
        $result = $service->search([
            'yayin_durumu' => 'yayinda',
            'maxFiyat' => 1_000_000,
            'paraBirimi' => 'EUR',
        ]);

        $ids = collect($result['data'])->pluck('id')->toArray();
        $this->assertNotContains($ilanId, $ids, 'Unpublished EUR listing must be excluded');
    }

    // -------------------------------------------------------------------------
    // BACKWARD COMPATIBILITY: caller WITHOUT currency must NOT break
    // -------------------------------------------------------------------------

    public function test_search_without_currency_still_returns_all_currencies(): void
    {
        $eurId = $this->createListing([
            'baslik' => 'EUR 900K',
            'fiyat' => 900_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);
        $tryId = $this->createListing([
            'baslik' => 'TRY 30M',
            'fiyat' => 30_000_000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'yayinda',
        ]);

        $service = app(\App\Services\Ilan\IlanSearchService::class);
        $result = $service->search([
            'yayin_durumu' => 'yayinda',
            'maxFiyat' => 50_000_000,
            // NO paraBirimi — existing callers must still work
        ]);

        $ids = collect($result['data'])->pluck('id')->toArray();
        $this->assertContains($eurId, $ids, 'EUR listing must appear when no currency specified');
        $this->assertContains($tryId, $ids, 'TRY listing must appear when no currency specified');
    }

    // -------------------------------------------------------------------------
    // CURRENCY CASE INSENSITIVITY
    // -------------------------------------------------------------------------

    public function test_currency_parameter_is_normalized_to_uppercase(): void
    {
        $ilanId = $this->createListing([
            'baslik' => 'EUR 500K',
            'fiyat' => 500_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        $service = app(\App\Services\Ilan\IlanSearchService::class);

        // lowercase 'eur' must still match EUR rows
        $result = $service->search([
            'yayin_durumu' => 'yayinda',
            'maxFiyat' => 1_000_000,
            'paraBirimi' => 'eur',
        ]);

        $ids = collect($result['data'])->pluck('id')->toArray();
        $this->assertContains($ilanId, $ids, 'Lowercase currency param must be normalized correctly');
    }

    // -------------------------------------------------------------------------
    // INVALID / UNSUPPORTED CURRENCY MUST NOT FILTER
    // -------------------------------------------------------------------------

    public function test_unsupported_currency_string_is_ignored_safely(): void
    {
        $eurId = $this->createListing([
            'baslik' => 'EUR 500K',
            'fiyat' => 500_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);
        $tryId = $this->createListing([
            'baslik' => 'TRY 30M',
            'fiyat' => 30_000_000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'yayinda',
        ]);

        $service = app(\App\Services\Ilan\IlanSearchService::class);
        $result = $service->search([
            'yayin_durumu' => 'yayinda',
            'maxFiyat' => 100_000_000,
            'paraBirimi' => 'INVALID_XYZ', // unsupported — must NOT filter
        ]);

        $ids = collect($result['data'])->pluck('id')->toArray();
        $this->assertContains($eurId, $ids, 'EUR listing must appear when invalid currency is ignored');
        $this->assertContains($tryId, $ids, 'TRY listing must appear when invalid currency is ignored');
    }
}
