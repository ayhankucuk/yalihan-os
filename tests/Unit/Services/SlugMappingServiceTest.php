<?php

namespace Tests\Unit\Services;

use App\Models\IlanKategori;
use App\Models\YayinTipi;
use App\Models\YayinTipiSablonu;
use App\Services\Ups\SlugMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

/**
 * Phase 2 — Slug Mapping Regression Tests (T13, T14)
 *
 * T13: SlugMappingVerified — listing_type_id → template_slug mapping correct
 * T14: V1TableLookup — yayin_tipleri table → correct slug lookup
 *
 * @see storage/tmp/canonical-mapping-inheritance-implementation-plan.md §5.1
 */
class SlugMappingServiceTest extends TestCase
{
    use RefreshDatabase;

    private SlugMappingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SlugMappingService::class);
    }

    // ─── T14: V1 Table Lookup ───────────────────────────────────────────

    /**
     * T14a: listingTypeIdToV1Slug returns correct slug for each known listing_type_id.
     */
    public function test_t14a_listing_type_id_to_v1_slug_returns_correct_slugs(): void
    {
        $this->seedYayinTipleri();

        $expected = [
            1 => 'satilik',
            2 => 'kiralik',
            3 => 'kat-karsiligi',
            4 => 'devren',
            5 => 'gunluk-kiralik',
            6 => 'haftalik-kiralik',
            7 => 'aylik-kiralik',
            8 => 'sezonluk-kiralik',
        ];

        foreach ($expected as $ltId => $slug) {
            $this->assertSame(
                $slug,
                $this->service->listingTypeIdToV1Slug($ltId),
                "listing_type_id={$ltId} should map to slug='{$slug}'"
            );
        }
    }

    /**
     * T14b: listingTypeIdToV1Slug throws InvalidArgumentException for unknown id.
     */
    public function test_t14b_unknown_listing_type_id_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown listing_type_id: 999');
        $this->service->listingTypeIdToV1Slug(999);
    }

    /**
     * T14c: listingTypeIdToV1Slug throws RuntimeException when yayin_tipleri record missing.
     */
    public function test_t14c_missing_yayin_tipi_record_throws_runtime_exception(): void
    {
        // Don't seed yayin_tipleri — record won't exist
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('yayin_tipleri record not found');
        $this->service->listingTypeIdToV1Slug(1);
    }

    // ─── T13: Slug Mapping Verified ──────────────────────────────────────

    /**
     * T13a: v1SlugToCanonical returns correct canonical typeSlug for each V1 slug.
     */
    public function test_t13a_v1_slug_to_canonical_returns_correct_type_slugs(): void
    {
        $expected = [
            'satilik'          => 'satilik',
            'kiralik'          => 'kiralik',
            'gunluk-kiralik'   => 'gunluk',
            'haftalik-kiralik' => 'haftalik',
            'aylik-kiralik'    => 'aylik',
            'sezonluk-kiralik' => 'sezonluk',
            'devren'           => 'devren',
            'kat-karsiligi'    => 'kat-karsiligi',
        ];

        foreach ($expected as $v1Slug => $canonical) {
            $this->assertSame(
                $canonical,
                $this->service->v1SlugToCanonical($v1Slug),
                "V1 slug='{$v1Slug}' should canonicalize to '{$canonical}'"
            );
        }
    }

    /**
     * T13b: v1SlugToCanonical throws InvalidArgumentException for unknown slug.
     */
    public function test_t13b_unknown_v1_slug_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown V1 yayin_tipleri slug: 'unknown-slug'");
        $this->service->v1SlugToCanonical('unknown-slug');
    }

    /**
     * T13c: shortenSlug correctly strips -kiralik and -kiralama suffixes.
     */
    public function test_t13c_shorten_slug_strips_suffixes(): void
    {
        $this->assertSame('satilik', $this->service->shortenSlug('satilik'));
        $this->assertSame('kiralik', $this->service->shortenSlug('kiralik'));
        $this->assertSame('gunluk', $this->service->shortenSlug('gunluk-kiralik'));
        $this->assertSame('sezonluk', $this->service->shortenSlug('sezonluk-kiralik'));
        $this->assertSame('gunluk', $this->service->shortenSlug('gunluk-kiralama'));
    }

    /**
     * T13d: resolveTemplate returns correct YayinTipiSablonu for known kategori + listing_type.
     */
    public function test_t13d_resolve_template_returns_correct_template(): void
    {
        $this->seedYayinTipleri();

        $kategori = IlanKategori::factory()->create([
            'name' => 'Villa',
            'slug' => 'villa',
            'seviye' => 1,
        ]);

        $template = YayinTipiSablonu::factory()->create([
            'kategori_id' => $kategori->id,
            'ad' => 'Villa Satılık',
            'slug' => 'villa-satilik',
        ]);

        $resolved = $this->service->resolveTemplate($kategori->id, 1);

        $this->assertInstanceOf(YayinTipiSablonu::class, $resolved);
        $this->assertSame($template->id, $resolved->id);
        $this->assertSame('villa-satilik', $resolved->slug);
    }

    /**
     * T13e: resolveTemplate throws RuntimeException when template not found.
     */
    public function test_t13e_resolve_template_throws_when_template_missing(): void
    {
        $this->seedYayinTipleri();

        $kategori = IlanKategori::factory()->create([
            'name' => 'Villa',
            'slug' => 'villa',
            'seviye' => 1,
        ]);

        // Don't create the template — should throw
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("YayinTipiSablonu not found for slug='villa-satilik'");
        $this->service->resolveTemplate($kategori->id, 1);
    }

    /**
     * T13f: resolveTemplateByKategoriSlug works with kategori slug directly.
     */
    public function test_t13f_resolve_template_by_kategori_slug(): void
    {
        $this->seedYayinTipleri();

        $kategori = IlanKategori::factory()->create([
            'name' => 'Villa',
            'slug' => 'villa',
            'seviye' => 1,
        ]);

        $template = YayinTipiSablonu::factory()->create([
            'kategori_id' => $kategori->id,
            'ad' => 'Villa Günlük',
            'slug' => 'villa-gunluk',
        ]);

        $resolved = $this->service->resolveTemplateByKategoriSlug('villa', 5);

        $this->assertSame($template->id, $resolved->id);
        $this->assertSame('villa-gunluk', $resolved->slug);
    }

    /**
     * T13g: Full pipeline — gunluk-kiralik (lt=5) → gunluk → villa-gunluk template.
     */
    public function test_t13g_full_pipeline_gunluk_kiralik_to_canonical(): void
    {
        $this->seedYayinTipleri();

        $kategori = IlanKategori::factory()->create([
            'name' => 'Villa',
            'slug' => 'villa',
            'seviye' => 1,
        ]);

        $template = YayinTipiSablonu::factory()->create([
            'kategori_id' => $kategori->id,
            'ad' => 'Villa Günlük Kiralık',
            'slug' => 'villa-gunluk',
        ]);

        // listing_type_id=5 → yayin_tipleri.slug='gunluk-kiralik' → canonical='gunluk'
        // → template_slug='villa-gunluk'
        $resolved = $this->service->resolveTemplate($kategori->id, 5);
        $this->assertSame('villa-gunluk', $resolved->slug);
    }

    /**
     * T13h: getMappingReport returns full diagnostic info.
     */
    public function test_t13h_get_mapping_report_returns_diagnostics(): void
    {
        $this->seedYayinTipleri();

        $kategori = IlanKategori::factory()->create([
            'name' => 'Villa',
            'slug' => 'villa',
            'seviye' => 1,
        ]);

        $template = YayinTipiSablonu::factory()->create([
            'kategori_id' => $kategori->id,
            'slug' => 'villa-satilik',
        ]);

        $report = $this->service->getMappingReport($kategori->id, 1);

        $this->assertSame(1, $report['listing_type_id']);
        $this->assertSame($kategori->id, $report['sub_category_id']);
        $this->assertSame('satilik', $report['v1_slug']);
        $this->assertSame('satilik', $report['canonical_type_slug']);
        $this->assertSame('villa', $report['kategori_slug']);
        $this->assertSame('villa-satilik', $report['template_slug']);
        $this->assertSame($template->id, $report['template_id']);
        $this->assertTrue($report['resolved']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    /**
     * Seed the 8 V1 yayin_tipleri records matching the production data.
     */
    private function seedYayinTipleri(): void
    {
        $records = [
            ['id' => 1, 'name' => 'Satılık', 'slug' => 'satilik', 'aktiflik_durumu' => 1],
            ['id' => 2, 'name' => 'Kiralık', 'slug' => 'kiralik', 'aktiflik_durumu' => 1],
            ['id' => 3, 'name' => 'Kat Karşılığı', 'slug' => 'kat-karsiligi', 'aktiflik_durumu' => 1],
            ['id' => 4, 'name' => 'Devren', 'slug' => 'devren', 'aktiflik_durumu' => 1],
            ['id' => 5, 'name' => 'Günlük Kiralık', 'slug' => 'gunluk-kiralik', 'aktiflik_durumu' => 1],
            ['id' => 6, 'name' => 'Haftalık Kiralık', 'slug' => 'haftalik-kiralik', 'aktiflik_durumu' => 1],
            ['id' => 7, 'name' => 'Aylık Kiralık', 'slug' => 'aylik-kiralik', 'aktiflik_durumu' => 1],
            ['id' => 8, 'name' => 'Sezonluk Kiralık', 'slug' => 'sezonluk-kiralik', 'aktiflik_durumu' => 1],
        ];

        foreach ($records as $record) {
            YayinTipi::create($record);
        }
    }
}
