<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\Language;
use App\Services\LocaleControlService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LocaleControlServiceTest extends TestCase
{
    private LocaleControlService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(LocaleControlService::class);
        Cache::clear();
    }

    /** @test */
    public function it_returns_only_active_languages()
    {
        // Seed active languages
        Language::create(['code' => 'tr', 'name' => 'Türkçe', 'aktiflik_durumu' => true, 'display_order' => 1]);
        Language::create(['code' => 'en', 'name' => 'English', 'aktiflik_durumu' => true, 'display_order' => 2]);
        Language::create(['code' => 'ru', 'name' => 'Russian', 'aktiflik_durumu' => true, 'display_order' => 3]);
        
        // Seed disabled languages
        Language::create(['code' => 'ar', 'name' => 'Arabic', 'aktiflik_durumu' => false, 'display_order' => 4]);
        Language::create(['code' => 'de', 'name' => 'German', 'aktiflik_durumu' => false, 'display_order' => 5]);

        $activeLanguages = $this->service->getActiveLanguages();

        $this->assertCount(3, $activeLanguages);
        $this->assertEquals(['tr', 'en', 'ru'], $activeLanguages->pluck('code')->toArray());
        $this->assertNotContains('ar', $activeLanguages->pluck('code')->toArray());
    }

    /** @test */
    public function it_provides_boot_safety_fallback_when_table_is_missing()
    {
        // Simulate a scenario where the languages table does not exist
        Schema::dropIfExists('languages');
        
        $activeLanguages = $this->service->getActiveLanguages();

        // The fallback logic should return a collection containing only 'tr'
        $this->assertCount(1, $activeLanguages);
        
        $fallbackLang = $activeLanguages->first();
        $this->assertEquals('tr', $fallbackLang->code);
        $this->assertEquals('Türkçe', $fallbackLang->name);
        $this->assertTrue($fallbackLang->aktiflik_durumu);
        
        // Restore state without migrate:fresh (if possible) or skip DDL tests in SQLite
    }

    /** @test */
    public function default_locale_fallback_works()
    {
        Schema::dropIfExists('languages');
        
        $defaultLocale = $this->service->getDefaultLocale();
        
        $this->assertEquals(config('app.fallback_locale', 'tr'), $defaultLocale);
        
        // Restore table
    }

    /** @test */
    public function seeder_is_idempotent_and_deterministic()
    {
        // Run seeder twice
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\LocaleCurrencySeeder']);
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\LocaleCurrencySeeder']);

        $activeLanguages = app(LocaleControlService::class)->getActiveLanguages();

        $this->assertCount(3, $activeLanguages);
        $this->assertEquals(['tr', 'en', 'ru'], $activeLanguages->pluck('code')->toArray());
        $this->assertEquals('tr', app(LocaleControlService::class)->getDefaultLocale());
    }

    /** @test */
    public function stale_cache_is_invalidated_after_seeding()
    {
        // Force a stale cache state
        Cache::put('active_languages', collect([
            (object) ['code' => 'zz', 'name' => 'Fake', 'aktiflik_durumu' => true, 'varsayilan_durumu' => true, 'is_rtl' => false, 'display_order' => 1]
        ]), 3600);
        Cache::put('default_locale', 'zz', 3600);

        // Verify stale cache is active
        $this->assertEquals(['zz'], app(LocaleControlService::class)->getActiveLanguages()->pluck('code')->toArray());
        $this->assertEquals('zz', app(LocaleControlService::class)->getDefaultLocale());

        // Run seeder
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\LocaleCurrencySeeder']);

        // Verify cache was invalidated and new DB state is returned
        $activeLanguages = app(LocaleControlService::class)->getActiveLanguages();
        $this->assertEquals(['tr', 'en', 'ru'], $activeLanguages->pluck('code')->toArray());
        $this->assertEquals('tr', app(LocaleControlService::class)->getDefaultLocale());
    }
}
