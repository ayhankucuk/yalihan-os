<?php

namespace Tests\Feature;

use App\Enums\AktiflikDurumu;
use App\Models\IlanKategori;
use App\Models\YayinTipi;
use App\Models\YayinTipiSablonu;
use App\Services\Ups\PropertyPublicationPolicy;
use App\Support\YayinTipiRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PILOT-01-003 Recovery: Villa Yayin Tipi Provisioning
 *
 * SAAB PILOT-01 Recovery Baseline: 877f45d
 *
 * Kök neden:
 *   YayinTipiSeeder sadece 4 base YayinTipi (Satılık, Kiralık, Kat Karşılığı, Devren)
 *   oluşturuyordu. Villa matrix'inde 6 yayın tipi var (Günlük, Haftalık, Aylık,
 *   Sezonluk dahil) ama bunlar YayinTipi olarak mevcut değildi.
 *
 * Düzeltme:
 *   1. YayinTipiSeeder → seasonal YayinTipi kayıtları eklendi (IDs 5-8)
 *   2. YayinTipiSeeder → Villa YayinTipiSablonu kayıtları eklendi (IDs 19-24)
 *   3. YayinTipi model → is_active → aktiflik_durumu düzeltildi (Context7 compliance)
 *   4. YayinTipiRules → villa-gunluk, villa-haftalik, villa-aylik, villa-sezonluk eklendi
 *
 * Regression guard: Arsa YayinTipiSablonu (IDs 13-14) korunmalı.
 */
class PILOT01003VillaPublicationTypeTest extends TestCase
{
    use RefreshDatabase;

    // Manuel minimal veri kurulumu (seeder'a güvenmiyoruz)
    protected function setUp(): void
    {
        parent::setUp();

        // Villa kategorisi
        IlanKategori::create(['name' => 'Villa', 'slug' => 'villa', 'seviye' => 2, 'aktiflik_durumu' => true]);
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 1: Villa kategorisi 6 yayın tipi dönmeli (PILOT-01-003 core)
    // ─────────────────────────────────────────────────────────────────
    public function test_villa_category_returns_all_six_publication_types(): void
    {
        // YayinTipiKayıtları oluştur
        $this->seedYayinTipleri();
        $this->seedVillaTemplates();

        $villa = IlanKategori::where('slug', 'villa')->firstOrFail();
        $templateCount = YayinTipiSablonu::where('kategori_id', $villa->id)->count();
        $this->assertEquals(6, $templateCount, "Villa YayinTipiSablonu sayısı 6 olmalı, {$templateCount} var. Villa ID={$villa->id}");

        $policy = app(PropertyPublicationPolicy::class);
        $types = $policy->getAllowedTypes($villa->id);

        $this->assertGreaterThanOrEqual(
            6,
            $types->count(),
            'Villa kategorisi en az 6 yayın tipi dönmeli.'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 2: Seasonal YayinTipi kayıtları mevcut
    // ─────────────────────────────────────────────────────────────────
    public function test_seasonal_yayintipi_records_in_db(): void
    {
        $this->seedYayinTipleri();

        $this->assertDatabaseHas('yayin_tipleri', ['slug' => 'gunluk-kiralik']);
        $this->assertDatabaseHas('yayin_tipleri', ['slug' => 'haftalik-kiralik']);
        $this->assertDatabaseHas('yayin_tipleri', ['slug' => 'aylik-kiralik']);
        $this->assertDatabaseHas('yayin_tipleri', ['slug' => 'sezonluk-kiralik']);
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 3: YayinTipiRules canonicalization — Villa seasonal slugs
    // ─────────────────────────────────────────────────────────────────
    public function test_yayintipirules_canonicalizes_villa_seasonal_slugs(): void
    {
        $cases = [
            'villa-gunluk'  => 'gunluk',
            'villa-haftalik' => 'haftalik',
            'villa-aylik'  => 'aylik',
            'villa-sezonluk' => 'sezonluk',
            'villa-satilik' => 'satilik',
            'villa-kiralik' => 'kiralik',
        ];

        foreach ($cases as $slug => $expected) {
            $canonical = YayinTipiRules::canonicalizeSlug($slug);
            $this->assertEquals($expected, $canonical, "canonicalizeSlug('{$slug}')");
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 4: YayinTipiRules bilinmeyen slug reddeder
    // ─────────────────────────────────────────────────────────────────
    public function test_yayintipirules_rejects_unknown_slugs(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        YayinTipiRules::canonicalizeSlug('villa-garbage');
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 5: YayinTipi aktiflik_durumu cast dogru
    // ─────────────────────────────────────────────────────────────────
    public function test_yayintipi_aktiflik_durumu_cast(): void
    {
        $this->seedYayinTipleri();

        $tip = YayinTipi::where('slug', 'gunluk-kiralik')->firstOrFail();
        // aktiflik_durumu Enum cast'li — enum değeri kontrol et
        $this->assertEquals(AktiflikDurumu::AKTIF, $tip->aktiflik_durumu);
    }

    // ─────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────
    private function seedYayinTipleri(): void
    {
        // Base YayinTipleri (updateOrCreate - unique constraint güvenli)
        $base = [
            ['id' => 1, 'name' => 'Satılık',       'slug' => 'satilik',        'aktiflik_durumu' => 1],
            ['id' => 2, 'name' => 'Kiralık',       'slug' => 'kiralik',       'aktiflik_durumu' => 1],
            ['id' => 3, 'name' => 'Kat Karşılığı', 'slug' => 'kat-karsiligi', 'aktiflik_durumu' => 1],
            ['id' => 4, 'name' => 'Devren',        'slug' => 'devren',        'aktiflik_durumu' => 1],
            ['id' => 5, 'name' => 'Günlük Kiralık',  'slug' => 'gunluk-kiralik',  'aktiflik_durumu' => 1],
            ['id' => 6, 'name' => 'Haftalık Kiralık', 'slug' => 'haftalik-kiralik', 'aktiflik_durumu' => 1],
            ['id' => 7, 'name' => 'Aylık Kiralık',   'slug' => 'aylik-kiralik',   'aktiflik_durumu' => 1],
            ['id' => 8, 'name' => 'Sezonluk Kiralık', 'slug' => 'sezonluk-kiralik', 'aktiflik_durumu' => 1],
        ];

        foreach ($base as $t) {
            YayinTipi::updateOrCreate(['slug' => $t['slug']], $t);
        }
    }

    private function seedVillaTemplates(): void
    {
        $villa = IlanKategori::where('slug', 'villa')->firstOrFail();
        // ID'ler AUTO_INCREMENT ile otomatik atanır. Policy slug bazlı eşleşiyor (canonicalizeSlug).
        $base = [
            // ID AUTO_INCREMENT: Yukarıdaki [25-30] yorumu referans içindir, gerçek ID'ler DB'den gelir.
            ['kategori_id' => $villa->id, 'yayin_tipi_id' => 1, 'ad' => 'Villa Satılık Şablonu', 'slug' => 'villa-satilik',  'aktiflik_durumu' => 1],
            ['kategori_id' => $villa->id, 'yayin_tipi_id' => 2, 'ad' => 'Villa Kiralık Şablonu', 'slug' => 'villa-kiralik', 'aktiflik_durumu' => 1],
            ['kategori_id' => $villa->id, 'yayin_tipi_id' => 5, 'ad' => 'Villa Günlük Kiralık Şablonu', 'slug' => 'villa-gunluk',  'aktiflik_durumu' => 1],
            ['kategori_id' => $villa->id, 'yayin_tipi_id' => 6, 'ad' => 'Villa Haftalık Şablonu', 'slug' => 'villa-haftalik', 'aktiflik_durumu' => 1],
            ['kategori_id' => $villa->id, 'yayin_tipi_id' => 7, 'ad' => 'Villa Aylık Şablonu', 'slug' => 'villa-aylik', 'aktiflik_durumu' => 1],
            ['kategori_id' => $villa->id, 'yayin_tipi_id' => 8, 'ad' => 'Villa Sezonluk Şablonu', 'slug' => 'villa-sezonluk', 'aktiflik_durumu' => 1],
        ];

        foreach ($base as $t) {
            YayinTipiSablonu::updateOrCreate(['kategori_id' => $t['kategori_id'], 'yayin_tipi_id' => $t['yayin_tipi_id']], $t);
        }
    }
}
