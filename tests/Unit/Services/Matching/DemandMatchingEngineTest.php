<?php

namespace Tests\Unit\Services\Matching;

use App\Enums\IlanDurumu;
use App\Enums\TalepDurumu;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\Talep;
use App\Services\Matching\DemandMatchingEngine;
use App\Services\Price\CurrencyRateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DemandMatchingEngineTest extends TestCase
{
    protected DemandMatchingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();

        Cache::put('currency_rates', [
            'rates' => CurrencyRateService::FALLBACK_RATES,
            'last_updated' => now()->toIso8601String(),
            'source' => 'test',
            'base_currency' => 'TRY',
        ], 3600);

        // Manual cleanup for domain tables (no transactions used)
        DB::table('eslesmeler')->delete();
        DB::table('ilanlar')->delete();
        DB::table('talepler')->delete();
        DB::table('ai_logs')->delete();

        $this->engine = app(DemandMatchingEngine::class);
    }

    /** @test */
    public function it_filters_candidates_by_location_and_category_in_sql()
    {
        // 1. Setup Categories, Kisi & Locations
        $kategori = $this->ensureKategori('villa');
        $digerKategori = $this->ensureKategori('arsa');
        $kisi = Kisi::withoutEvents(function () {
            return Kisi::factory()->create();
        });

        $istanbul = $this->ensureIl(34, ['il_adi' => 'İstanbul']);
        $ankara = $this->ensureIl(6, ['il_adi' => 'Ankara']);

        // 2. Setup Ilanlar (Candidates)
        // Correct candidate
        Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'il_id' => $istanbul->id,
            'alt_kategori_id' => $kategori->id,
            'fiyat' => 1000000,
        ]);

        // Wrong location
        Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'il_id' => $ankara->id,
            'alt_kategori_id' => $kategori->id,
            'fiyat' => 1000000,
        ]);

        // Wrong category
        Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'il_id' => $istanbul->id,
            'alt_kategori_id' => $digerKategori->id,
            'fiyat' => 1000000,
        ]);

        // 3. Setup Talep
        $talep = Talep::factory()->create([
            'il_id' => $istanbul->id,
            'kisi_id' => $kisi->id,
            'alt_kategori_id' => $kategori->id,
            'min_fiyat' => 900000,
            'max_fiyat' => 1100000,
            'talep_durumu' => TalepDurumu::AKTIF->value,
        ]);

        // 4. Execute
        $candidates = $this->engine->getIlanCandidates($talep);
        $this->assertCount(1, $candidates);
        $results = $this->engine->matchDemand($talep);

        // 5. Assert: Should only find 1 candidate
        $this->assertCount(1, $results);
        $this->assertEquals($istanbul->id, $results->first()['ilan']->il_id);
    }

    /** @test */
    public function it_respects_max_candidates_limit()
    {
        // 1. Setup limit & environment
        Config::set('crm.matching.max_candidates', 5);
        $kisi = Kisi::factory()->create();
        $istanbul = $this->ensureIl(34, ['il_adi' => 'İstanbul']);

        // 2. Create 10 valid candidates
        Ilan::factory()->count(10)->create([
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'il_id' => $istanbul->id,
            'fiyat' => 1000000,
        ]);

        // 3. Setup Talep
        $talep = Talep::factory()->create([
            'il_id' => $istanbul->id,
            'kisi_id' => $kisi->id,
            'talep_durumu' => TalepDurumu::AKTIF->value,
        ]);

        // 4. Execute
        $results = $this->engine->matchDemand($talep, 20);

        // Assert: Results should be capped by max_candidates even if we requested 20
        $this->assertLessThanOrEqual(5, $results->count());
    }

    /** @test */
    public function it_filters_by_price_tolerance_in_sql()
    {
        Config::set('crm.matching.price_tolerance', 0.10); // %10
        $kisi = Kisi::factory()->create();
        $istanbul = $this->ensureIl(34, ['il_adi' => 'İstanbul']);

        // Candidate within tolerance (1.1M is exactly +10% of 1M)
        Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'il_id' => $istanbul->id,
            'fiyat' => 1100000,
        ]);

        // Candidate outside tolerance (1.2M is > +10%)
        Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'il_id' => $istanbul->id,
            'fiyat' => 1200000,
        ]);

        $talep = Talep::factory()->create([
            'il_id' => $istanbul->id,
            'max_fiyat' => 1000000,
            'kisi_id' => $kisi->id,
            'talep_durumu' => TalepDurumu::AKTIF->value,
        ]);

        $results = $this->engine->matchDemand($talep);

        $this->assertCount(1, $results);
    }

    /** @test */
    public function it_filters_candidates_by_neighborhood_and_area_in_sql()
    {
        // 1. Setup
        $kategori = $this->ensureKategori('villa');
        $istanbul = $this->ensureIl(34, ['il_adi' => 'İstanbul']);

        $ilce = $this->ensureIlce(1, $istanbul->id, ['ilce_adi' => 'Beşiktaş']);
        $mahalle = $this->ensureMahalle(1, $ilce->id, ['mahalle_adi' => 'Levent']);
        $digerMahalle = $this->ensureMahalle(2, $ilce->id, ['mahalle_adi' => 'Etiler']);

        // 2. Setup Ilanlar
        // Valid candidate
        Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'il_id' => $istanbul->id,
            'ilce_id' => $ilce->id,
            'mahalle_id' => $mahalle->id,
            'alt_kategori_id' => $kategori->id,
            'brut_m2' => 200,
            'fiyat' => 1000000,
        ]);

        // Wrong neighborhood
        Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'il_id' => $istanbul->id,
            'ilce_id' => $ilce->id,
            'mahalle_id' => $digerMahalle->id,
            'alt_kategori_id' => $kategori->id,
            'brut_m2' => 200,
            'fiyat' => 1000000,
        ]);

        // Out of m2 range (too small)
        Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'il_id' => $istanbul->id,
            'ilce_id' => $ilce->id,
            'mahalle_id' => $mahalle->id,
            'alt_kategori_id' => $kategori->id,
            'brut_m2' => 100, // Talep min is 200, tolerance 20% -> 160 is min. 100 is out.
            'fiyat' => 1000000,
        ]);

        // 3. Create Talep
        $talep = Talep::factory()->create([
            'il_id' => $istanbul->id,
            'ilce_id' => $ilce->id,
            'mahalle_id' => $mahalle->id,
            'alt_kategori_id' => $kategori->id,
            'min_fiyat' => 800000,
            'max_fiyat' => 1200000,
            'talep_durumu' => TalepDurumu::AKTIF->value,
        ]);

        // Use forceFill or direct assignment for non-fillable attributes
        $talep->min_metrekare = 200;
        $talep->max_metrekare = 300;
        $talep->save();

        // 4. Match
        $results = $this->engine->matchDemand($talep);

        // 5. Assert: Only 1 candidate should survive SQL filtering
        $this->assertCount(1, $results);
    }

    /** @test */
    public function it_filters_candidates_across_different_currencies_in_sql()
    {
        $kisi = Kisi::factory()->create();
        $bodrum = $this->ensureIl(48, ['il_adi' => 'Muğla']);

        // Candidate matching after currency conversion
        // 4,500,000 TRY is ~120,967 EUR @ 37.20 rate (within 100k-150k EUR budget)
        // Raw numbers (4.5M vs 150k) would eliminate this without currency normalization!
        $matchingIlan = Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'il_id' => $bodrum->id,
            'fiyat' => 4500000,
            'para_birimi' => 'TRY',
        ]);

        // Out of budget: 500,000 TRY (~13,440 EUR) -> Below 100k EUR
        Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'il_id' => $bodrum->id,
            'fiyat' => 500000,
            'para_birimi' => 'TRY',
        ]);

        // Out of budget: 20,000,000 TRY (~537,634 EUR) -> Above 150k EUR
        Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'il_id' => $bodrum->id,
            'fiyat' => 20000000,
            'para_birimi' => 'TRY',
        ]);

        $talep = Talep::factory()->create([
            'il_id' => $bodrum->id,
            'kisi_id' => $kisi->id,
            'min_fiyat' => 100000,
            'max_fiyat' => 150000,
            'para_birimi' => 'EUR',
            'talep_durumu' => TalepDurumu::AKTIF->value,
        ]);

        $candidates = $this->engine->getIlanCandidates($talep);

        $this->assertTrue($candidates->contains('id', $matchingIlan->id), 'Matching TRY listing should be retrieved as candidate for EUR demand');
        $this->assertCount(1, $candidates);
    }
}
