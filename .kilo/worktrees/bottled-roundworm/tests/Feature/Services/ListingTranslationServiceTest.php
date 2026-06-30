<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\Ilan;
use App\Models\Language;
use App\Services\AITranslation\ListingTranslationService;
use Illuminate\Support\Facades\Cache;

class ListingTranslationServiceTest extends TestCase
{
    private ListingTranslationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(ListingTranslationService::class);
        
        // Seed standard active languages as per Pack-P2 SSOT
        Language::create(['code' => 'tr', 'name' => 'Türkçe', 'aktiflik_durumu' => true, 'display_order' => 1]);
        Language::create(['code' => 'en', 'name' => 'English', 'aktiflik_durumu' => true, 'display_order' => 2]);
        Language::create(['code' => 'ru', 'name' => 'Russian', 'aktiflik_durumu' => true, 'display_order' => 3]);
        
        // Disabled languages that should NEVER be targeted
        Language::create(['code' => 'ar', 'name' => 'Arabic', 'aktiflik_durumu' => false, 'display_order' => 4]);
        Language::create(['code' => 'de', 'name' => 'German', 'aktiflik_durumu' => false, 'display_order' => 5]);
        Language::create(['code' => 'fr', 'name' => 'French', 'aktiflik_durumu' => false, 'display_order' => 6]);
        
        Cache::clear();
    }

    /** @test */
    public function it_resolves_targets_for_turkish_source()
    {
        $ilan = new Ilan(['source_locale' => 'tr']);
        
        $targets = $this->service->resolveTargetLocales($ilan);
        
        $this->assertEquals(['en', 'ru'], $targets);
        $this->assertNotContains('tr', $targets);
        $this->assertNotContains('ar', $targets);
        $this->assertNotContains('de', $targets);
        $this->assertNotContains('fr', $targets);
    }

    /** @test */
    public function it_resolves_targets_for_english_source()
    {
        $ilan = new Ilan(['source_locale' => 'en']);
        
        $targets = $this->service->resolveTargetLocales($ilan);
        
        $this->assertEquals(['tr', 'ru'], $targets);
        $this->assertNotContains('en', $targets);
    }

    /** @test */
    public function it_resolves_targets_for_russian_source()
    {
        $ilan = new Ilan(['source_locale' => 'ru']);
        
        $targets = $this->service->resolveTargetLocales($ilan);
        
        $this->assertEquals(['tr', 'en'], $targets);
        $this->assertNotContains('ru', $targets);
    }

    /** @test */
    public function it_never_includes_inactive_languages()
    {
        // Even if we force source to 'ar', it shouldn't target disabled ones
        $ilan = new Ilan(['source_locale' => 'ar']);
        
        $targets = $this->service->resolveTargetLocales($ilan);
        
        $this->assertEquals(['tr', 'en', 'ru'], $targets);
        
        $this->assertNotContains('ar', $targets);
        $this->assertNotContains('de', $targets);
        $this->assertNotContains('fr', $targets);
    }
}
