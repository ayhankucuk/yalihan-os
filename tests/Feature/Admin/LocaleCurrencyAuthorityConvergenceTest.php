<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\Language;
use App\Models\User;
use App\Services\CurrencyControlService;
use App\Services\LocaleControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleCurrencyAuthorityConvergenceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create([
            'email' => 'locale-admin-'.uniqid().'@yalihan.local',
        ]);

        app(LocaleControlService::class)->clearCache();
        app(CurrencyControlService::class)->clearCache();

        // Seed deterministic test languages
        Language::updateOrCreate(['code' => 'tr'], [
            'name' => 'Türkçe',
            'aktiflik_durumu' => true,
            'varsayilan_durumu' => true,
            'is_rtl' => false,
            'display_order' => 1,
        ]);
        Language::updateOrCreate(['code' => 'en'], [
            'name' => 'English',
            'aktiflik_durumu' => true,
            'varsayilan_durumu' => false,
            'is_rtl' => false,
            'display_order' => 2,
        ]);

        // Seed deterministic test currencies
        Currency::updateOrCreate(['code' => 'TRY'], [
            'symbol' => '₺',
            'aktiflik_durumu' => true,
            'varsayilan_durumu' => true,
            'display_order' => 1,
        ]);
        Currency::updateOrCreate(['code' => 'EUR'], [
            'symbol' => '€',
            'aktiflik_durumu' => true,
            'varsayilan_durumu' => false,
            'display_order' => 2,
        ]);
    }

    /**
     * Contract 1 & 2: General settings form no longer exposes editable default_language or default_currency inputs.
     */
    public function test_general_settings_does_not_expose_editable_default_language_or_currency(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.ayarlar.index'));

        $response->assertOk();
        $response->assertDontSee('name="default_language"', false);
        $response->assertDontSee('name="default_currency"', false);
        $response->assertSee('Varsayılan Para Birimi');
        $response->assertSee('Varsayılan Dil');
        $response->assertSee('VARSAYILAN');
    }

    /**
     * Contract 3 & 5: Dedicated Language tab setDefault action updates the canonical authority.
     */
    public function test_dedicated_language_management_updates_canonical_authority(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.ayarlar.languages.set-default'), [
                'code' => 'en',
            ]);

        $response->assertRedirect();

        $localeService = app(LocaleControlService::class);
        $this->assertEquals('en', $localeService->getDefaultLocale());

        $this->assertTrue((bool) Language::where('code', 'en')->value('varsayilan_durumu'));
        $this->assertFalse((bool) Language::where('code', 'tr')->value('varsayilan_durumu'));
    }

    /**
     * Contract 4 & 6: Dedicated Currency tab setDefault action updates the canonical authority.
     */
    public function test_dedicated_currency_management_updates_canonical_authority(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.ayarlar.currencies.set-default'), [
                'code' => 'EUR',
            ]);

        $response->assertRedirect();

        $currencyService = app(CurrencyControlService::class);
        $this->assertEquals('EUR', $currencyService->getDefaultCurrency());

        $this->assertTrue((bool) Currency::where('code', 'EUR')->value('varsayilan_durumu'));
        $this->assertFalse((bool) Currency::where('code', 'TRY')->value('varsayilan_durumu'));
    }

    /**
     * Contract 7: Generic settings bulk-update cannot hijack or override the canonical locale/currency authorities.
     */
    public function test_generic_settings_bulk_update_does_not_alter_effective_runtime_authorities(): void
    {
        // Dedicated initial state: tr & TRY
        $localeService = app(LocaleControlService::class);
        $currencyService = app(CurrencyControlService::class);

        $this->assertEquals('tr', $localeService->getDefaultLocale());
        $this->assertEquals('TRY', $currencyService->getDefaultCurrency());

        // Attempt to submit generic settings with fake/rogue keys
        $response = $this->actingAs($this->admin)
            ->post(route('admin.ayarlar.bulk-update'), [
                'site_title' => 'Yalıhan OS Test',
                'default_language' => 'en',
                'default_currency' => 'EUR',
            ]);

        $response->assertRedirect(route('admin.ayarlar.index'));

        // Runtime authority MUST remain intact (still tr & TRY) because languages/currencies tables are the SSOT
        $this->assertEquals('tr', $localeService->getDefaultLocale());
        $this->assertEquals('TRY', $currencyService->getDefaultCurrency());
        $this->assertTrue((bool) Language::where('code', 'tr')->value('varsayilan_durumu'));
        $this->assertTrue((bool) Currency::where('code', 'TRY')->value('varsayilan_durumu'));
    }
}
