<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * @group skip-until-migration-complete
 */
class LocaleAndCurrencyResolutionTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        // Clear caches
        app(\App\Services\LocaleControlService::class)->clearCache();
        app(\App\Services\CurrencyControlService::class)->clearCache();

        // Setup initial data
        Language::create(['code' => 'tr', 'name' => 'Türkçe', 'is_active' => true, 'is_default' => true]);
        Language::create(['code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => false]);
        Language::create(['code' => 'ar', 'name' => 'Arabic', 'is_active' => false, 'is_default' => false]);

        Currency::create(['code' => 'TRY', 'symbol' => '₺', 'is_active' => true, 'is_default' => true]);
        Currency::create(['code' => 'USD', 'symbol' => '$', 'is_active' => true, 'is_default' => false]);
        Currency::create(['code' => 'GBP', 'symbol' => '£', 'is_active' => false, 'is_default' => false]);
    }

    /** @test */
    public function it_resolves_default_locale_and_currency()
    {
        $response = $this->get('/test-locale-currency');

        $this->assertEquals('tr', app()->getLocale());
        $response->assertSee('LOCALE: tr');
        $response->assertSee('CURRENCY: TRY');
    }

    /** @test */
    public function it_resolves_locale_from_session()
    {
        $response = $this->withSession(['locale' => 'en'])->get('/test-locale-currency');

        $response->assertSee('LOCALE: en');
    }

    /** @test */
    public function it_ignores_inactive_locale_from_session()
    {
        $response = $this->withSession(['locale' => 'ar'])->get('/test-locale-currency');

        $this->assertEquals('tr', app()->getLocale());
        $response->assertSee('LOCALE: tr');
    }

    /** @test */
    public function it_resolves_currency_from_session()
    {
        $response = $this->withSession(['currency' => 'USD'])->get('/test-locale-currency');

        $response->assertSee('CURRENCY: USD');
    }

    /** @test */
    public function it_ignores_inactive_currency_from_session()
    {
        $response = $this->withSession(['currency' => 'GBP'])->get('/test-locale-currency');

        $response->assertSee('CURRENCY: TRY');
    }
}
