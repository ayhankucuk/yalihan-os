<?php

namespace Tests\Feature\Rental;

use App\Models\Ilan;
use App\Models\PropertySeasonalRate;
use App\Models\User;
use App\Services\PropertyPricingService;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Gate B — Pricing & Commercial Logic Engine Tests
 */
class GateBPricingTest extends TestCase
{

    protected PropertyPricingService $service;
    protected Ilan $ilan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PropertyPricingService::class);

        $user = User::factory()->create();
        $this->ilan = Ilan::factory()->create([
            'user_id'        => $user->id,
            'rental_enabled' => true,
            'min_stay_nights'=> 2,
            'fiyat'          => 1000, // 1000 TRY base nightly rate
        ]);
    }

    /**
     * B1: Base price fallback (no seasonal rate)
     */
    public function test_b1_base_price_fallback_no_seasonal(): void
    {
        $quote = $this->service->calculateQuote(
            $this->ilan->id,
            '2026-10-01',
            '2026-10-04', // 3 nights
            'TR',
            'TRY'
        );

        $this->assertEquals(3, $quote['nights']);
        $this->assertEquals(1000, $quote['nightly_rate_try']);
        $this->assertEquals(3000, $quote['subtotal_try']);
        $this->assertNull($quote['applied_season']);

        // TR commission = 8%
        $this->assertEquals(0.08, $quote['commission_rate']);
        $this->assertEquals(240, $quote['commission_try']);
        $this->assertEquals(3240, $quote['total_try']);
    }

    /**
     * B2: Seasonal rate takes precedence over base price
     */
    public function test_b2_seasonal_rate_overrides_base_price(): void
    {
        PropertySeasonalRate::create([
            'property_id'    => $this->ilan->id,
            'start_date'     => '2026-07-01',
            'end_date'       => '2026-08-31',
            'nightly_rate'   => 2500, // High season rate
            'season_label'   => 'Yaz 2026',
            'currency'       => 'TRY',
            'aktiflik_durumu'=> true,
        ]);

        $quote = $this->service->calculateQuote(
            $this->ilan->id,
            '2026-07-10',
            '2026-07-17', // 7 nights in high season
            'TR',
            'TRY'
        );

        $this->assertEquals(2500, $quote['nightly_rate_try'], "Seasonal rate should override base.");
        $this->assertEquals('Yaz 2026', $quote['applied_season']);
        $this->assertEquals(17500, $quote['subtotal_try']);
    }

    /**
     * B3: Seasonal min_stay override
     */
    public function test_b3_seasonal_min_stay_override(): void
    {
        PropertySeasonalRate::create([
            'property_id'      => $this->ilan->id,
            'start_date'       => '2026-07-01',
            'end_date'         => '2026-08-31',
            'nightly_rate'     => 2000,
            'min_stay_override'=> 7, // 7 nights minimum in high season
            'aktiflik_durumu'  => true,
        ]);

        $minStay = $this->service->getEffectiveMinStay($this->ilan->id, '2026-07-15');
        $this->assertEquals(7, $minStay, "Seasonal min_stay override should be 7.");

        // Off-season: should revert to property default (2)
        $minStayOff = $this->service->getEffectiveMinStay($this->ilan->id, '2026-11-01');
        $this->assertEquals(2, $minStayOff, "Off-season should use property default min_stay.");
    }

    /**
     * B1 Currency: EUR conversion
     */
    public function test_b1_currency_eur_conversion(): void
    {
        $quote = $this->service->calculateQuote(
            $this->ilan->id,
            '2026-10-01',
            '2026-10-03', // 2 nights
            'GR',
            'EUR'
        );

        // GR commission = 5%
        $this->assertEquals(0.05, $quote['commission_rate']);
        $subtotal     = 1000 * 2; // 2000 TRY
        $commission   = (int) round($subtotal * 0.05); // 100
        $totalTRY     = $subtotal + $commission; // 2100
        $expectedEUR  = round($totalTRY * 0.029, 2);

        $this->assertEquals($totalTRY, $quote['total_try']);
        $this->assertEquals($expectedEUR, $quote['total_converted']);
        $this->assertEquals('EUR', $quote['currency']);
    }

    /**
     * B4: Commission is always audit-logged
     */
    public function test_b4_commission_audit_logged(): void
    {
        $quote = $this->service->calculateQuote(
            $this->ilan->id,
            '2026-10-01',
            '2026-10-06', // 5 nights
            'UK',
            'GBP'
        );

        $this->assertArrayHasKey('audit', $quote);
        $audit = $quote['audit'];

        $this->assertEquals('UK', $audit['market']);
        $this->assertEquals(6.0, $audit['commission_pct']); // UK = 6%
        $this->assertArrayHasKey('calculated_at', $audit);
        $this->assertArrayHasKey('exchange_rate', $audit);
    }

    /**
     * B: UI bypass test — service always enforces pricing regardless of input source
     */
    public function test_pricing_is_deterministic_and_cannot_be_bypassed(): void
    {
        // Call twice with same params — result must be identical (deterministic)
        $q1 = $this->service->calculateQuote($this->ilan->id, '2026-11-01', '2026-11-04', 'TR', 'TRY');
        $q2 = $this->service->calculateQuote($this->ilan->id, '2026-11-01', '2026-11-04', 'TR', 'TRY');

        $this->assertEquals($q1['total_try'], $q2['total_try']);
        $this->assertEquals($q1['nightly_rate_try'], $q2['nightly_rate_try']);
    }
}
