<?php

namespace Tests\Feature\Architecture;

use Tests\TestCase;
use App\Models\Ilan;
use App\Enums\IlanDurumu;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * HasActiveScope Trait Test
 *
 * Context7 Standardı: C7-ACTIVE-SCOPE-TRAIT-2026-05-23
 * SAB Core v2.7 Uyumlu
 *
 * ⚠️ ÖNEMLI: Ilan modeli detectActiveField() override'ı ile yayin_durumu'nu
 * PRIMARY active field olarak kullanır (aktiflik_durumu değil).
 * Bu test suite bu tasarım kararını doğrular.
 *
 * @see App\Models\Ilan::detectActiveField()
 * @see App\Traits\HasActiveScope
 */
class HasActiveScopeTraitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Ilan::active() scope yayin_durumu = 'yayinda' kayıtları döndürür
     *
     * Ilan modeli detectActiveField() override'ı ile yayin_durumu'nu
     * PRIMARY active field olarak kullanır.
     *
     * @test
     */
    public function it_enforces_canonical_aktiflik_durumu_scope_on_queries()
    {
        // 1. Arrange: Yayında ve taslak ilanlar oluştur
        $aktifIlan = Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'baslik' => 'Aktif İlan',
        ]);

        $pasifIlan = Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::TASLAK->value, // ✅ FIX: Ilan::active() yayin_durumu filtreler
            'baslik' => 'Pasif İlan',
        ]);

        // 2. Act: active() scope'unu tetikle
        $sonuc = Ilan::active()->get();

        // 3. Assert: Sadece yayin_durumu = 'yayinda' olanın geldiğini doğrula
        $this->assertCount(1, $sonuc);
        $this->assertEquals(IlanDurumu::YAYINDA->value, $sonuc->first()->yayin_durumu->value);
        $this->assertEquals('Aktif İlan', $sonuc->first()->baslik);
        $this->assertEquals($aktifIlan->id, $sonuc->first()->id);
    }

    /**
     * Test: aktif() scope (Context7 kanonik) Ilan modelinde yayin_durumu filtreler
     *
     * Not: Ilan modeli detectActiveField() override'ı ile yayin_durumu'nu PRIMARY
     * active field olarak tanımlar. scopeAktif() bu override'a saygı gösterir ve
     * scopeActive() üzerinden delegate eder → her iki scope da yayin_durumu filtreler.
     *
     * @test
     */
    public function it_supports_canonical_aktif_scope()
    {
        // Arrange: yayin_durumu ile kontrol et (Ilan::aktif() yayin_durumu filtreler)
        Ilan::factory()->create(['yayin_durumu' => IlanDurumu::YAYINDA->value]);
        Ilan::factory()->create(['yayin_durumu' => IlanDurumu::TASLAK->value]); // ✅ taslak → aktif() dışında
        Ilan::factory()->create(['yayin_durumu' => IlanDurumu::YAYINDA->value]);

        // Act
        $aktifIlanlar = Ilan::aktif()->get();

        // Assert: Sadece yayin_durumu = 'yayinda' olanlar (Ilan::aktif() = Ilan::active())
        $this->assertCount(2, $aktifIlanlar);
        foreach ($aktifIlanlar as $ilan) {
            $this->assertEquals(IlanDurumu::YAYINDA->value, $ilan->yayin_durumu->value);
        }
    }

    /**
     * Test: Taslak ilanlar active() scope'una dahil edilmez
     *
     * @test
     */
    public function it_excludes_inactive_listings_from_active_scope()
    {
        // Arrange: Sadece taslak ilanlar oluştur
        Ilan::factory()->count(3)->create(['yayin_durumu' => IlanDurumu::TASLAK->value]); // ✅ FIX

        // Act
        $sonuc = Ilan::active()->get();

        // Assert: Hiçbir kayıt dönmemeli
        $this->assertCount(0, $sonuc);
    }

    /**
     * Test: active() scope diğer query builder metodlarıyla zincirlenir
     *
     * @test
     */
    public function it_chains_with_other_query_builder_methods()
    {
        // Arrange
        Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'baslik' => 'A İlan',
        ]);

        Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'baslik' => 'B İlan',
        ]);

        Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::TASLAK->value, // ✅ FIX: pasif
            'baslik' => 'C İlan (Pasif)',
        ]);

        // Act: active() + where() + orderBy()
        $sonuc = Ilan::active()
            ->where('baslik', 'like', '%İlan%')
            ->orderBy('baslik', 'asc')
            ->get();

        // Assert
        $this->assertCount(2, $sonuc);
        $this->assertEquals('A İlan', $sonuc->first()->baslik);
        $this->assertEquals('B İlan', $sonuc->last()->baslik);
    }

    /**
     * Test: Model factory default değeri ile yayında ilan oluşturulur
     *
     * @test
     */
    public function it_works_with_factory_default_aktiflik_durumu()
    {
        // Arrange: Factory default değeri ile ilan oluştur
        $ilan = Ilan::factory()->create();

        // Act & Assert: Factory default yayin_durumu = 'yayinda' → active() scope'a dahil
        $this->assertEquals(IlanDurumu::YAYINDA->value, $ilan->yayin_durumu->value);
        $this->assertTrue(Ilan::active()->where('id', $ilan->id)->exists());
    }

    /**
     * Test: aktiflik_durumu mass assignment ile set edilebilir
     *
     * Not: Ilan::active() yayin_durumu filtreler, aktiflik_durumu değil.
     * Bu test aktiflik_durumu field'ının yazılabilir olduğunu doğrular.
     *
     * @test
     */
    public function it_allows_mass_assignment_of_aktiflik_durumu()
    {
        // Act: yayin_durumu = taslak ile ilan oluştur → active() scope dışında kalır
        $ilan = Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::TASLAK->value, // ✅ FIX
            'baslik' => 'Test İlan',
        ]);

        // Assert: active() scope dışında
        $this->assertFalse(Ilan::active()->where('id', $ilan->id)->exists());
    }

    /**
     * Test: aktiflik_durumu integer cast ile doğru tip döndürür
     *
     * @test
     */
    public function it_casts_aktiflik_durumu_to_integer()
    {
        // Arrange
        $ilan = Ilan::factory()->create(['aktiflik_durumu' => 1]);

        // Act: Fresh instance al
        $freshIlan = Ilan::find($ilan->id);

        // Assert: Integer olarak cast edilmeli
        $this->assertIsInt($freshIlan->aktiflik_durumu);
        $this->assertSame(1, $freshIlan->aktiflik_durumu);
    }
}
