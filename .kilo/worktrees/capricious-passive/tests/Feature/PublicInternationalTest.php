<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Language;

/**
 * @group skip-until-migration-complete
 */
class PublicInternationalTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        // Bypass Production Lock for tests
        config(['governance.production_lock' => 'OPEN']);

        // Clear Caches
        \Illuminate\Support\Facades\Cache::forget('active_languages');
        \Illuminate\Support\Facades\Cache::forget('active_currencies');
        \Illuminate\Support\Facades\Cache::forget('country_financial_rule_tr');

        // Setup basic languages
        Language::create(['code' => 'tr', 'name' => 'Turkish', 'is_active' => true]);
        Language::create(['code' => 'en', 'name' => 'English', 'is_active' => true]);
        Language::create(['code' => 'ru', 'name' => 'Russian', 'is_active' => false]);

        // Setup basic currencies
        \App\Models\Currency::create([
            'code' => 'TRY',
            'symbol' => '₺',
            'is_active' => true,
            'is_default' => true,
            'decimal_precision' => 2
        ]);

        // Setup required financial rules
        \App\Models\CountryFinancialRule::create([
            'country_code' => 'TR',
            'country_name' => 'Turkey',
            'rental_commission_rate' => 10,
            'sales_commission_rate' => 2,
            'advisory_fee_rate' => 1,
            'tax_rate' => 18,
            'default_currency' => 'TRY',
            'aktiflik_durumu' => true
        ]);
    }

    /** @test */
    public function it_resolves_locale_from_url_segment()
    {
        $response = $this->get('/en/invest-in-turkey');

        $response->assertStatus(200);
        $this->assertEquals('en', app()->getLocale());
    }

    /** @test */
    public function it_returns_404_for_inactive_locale()
    {
        $response = $this->get('/ru/invest-in-turkey');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_displays_seo_meta_tags()
    {
        $response = $this->get('/en/invest-in-turkey');

        $response->assertSee('Invest in Turkey');
        $response->assertSee('hreflang="en"', false);
        $response->assertSee('hreflang="tr"', false);
    }

    /** @test */
    public function it_displays_calculator_page()
    {
        $response = $this->get('/en/calculator');

        $response->assertStatus(200);
        $response->assertSee('ROI Calculator');
    }
}
