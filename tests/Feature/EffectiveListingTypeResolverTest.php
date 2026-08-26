<?php

namespace Tests\Feature;

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SAB\GlobalWriteGuard;
use App\Models\Kisi;
use App\Models\User;
use App\Models\YayinTipiSablonu;
use App\Services\Ups\PropertyPublicationPolicy;
use App\Services\Wizard\EffectiveListingTypeResolver;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Support\Facades\Event;
use Tests\Helpers\TestFixtureHelper;
use Tests\TestCase;

/**
 * EffectiveListingTypeResolver + PropertyPublicationPolicy acceptance tests.
 *
 * Validates the category → allowed publication types resolution chain.
 * Aligned with canonical YayinTipiSablonu template matrix and IDs.
 */
class EffectiveListingTypeResolverTest extends TestCase
{
    use TestFixtureHelper;

    private PropertyPublicationPolicy $policy;

    private EffectiveListingTypeResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();

        $this->policy = app(PropertyPublicationPolicy::class);
        $this->resolver = app(EffectiveListingTypeResolver::class);

        // Seed master yayin_tipleri table
        $this->seedYayinTipleri();

        // Seed categories & templates (matching canonical production DB structure)
        $this->seedCategories();
        $this->seedTemplates();
    }

    private function seedYayinTipleri(): void
    {
        $types = [
            ['id' => 1, 'name' => 'Satılık', 'slug' => 'satilik', 'aktiflik_durumu' => 1],
            ['id' => 2, 'name' => 'Kiralık', 'slug' => 'kiralik', 'aktiflik_durumu' => 1],
            ['id' => 3, 'name' => 'Kat Karşılığı', 'slug' => 'kat-karsiligi', 'aktiflik_durumu' => 1],
            ['id' => 4, 'name' => 'Devren', 'slug' => 'devren', 'aktiflik_durumu' => 1],
            ['id' => 5, 'name' => 'Günlük Kiralama', 'slug' => 'gunluk-kiralik', 'aktiflik_durumu' => 1],
            ['id' => 6, 'name' => 'Haftalık Kiralama', 'slug' => 'haftalik-kiralik', 'aktiflik_durumu' => 1],
            ['id' => 7, 'name' => 'Aylık Kiralama', 'slug' => 'aylik-kiralik', 'aktiflik_durumu' => 1],
            ['id' => 8, 'name' => 'Sezonluk Kiralama', 'slug' => 'sezonluk-kiralik', 'aktiflik_durumu' => 1],
        ];

        foreach ($types as $t) {
            \Illuminate\Support\Facades\DB::table('yayin_tipleri')->updateOrInsert(
                ['id' => $t['id']],
                array_merge($t, ['updated_at' => now(), 'created_at' => now()])
            );
        }
    }

    private function seedCategories(): void
    {
        // Ana Kategoriler (seviye=0)
        $anaKategoriler = [
            ['id' => 1, 'name' => 'Konut', 'slug' => 'konut', 'seviye' => 0, 'parent_id' => null, 'aktiflik_durumu' => true],
            ['id' => 2, 'name' => 'İşyeri', 'slug' => 'isyeri', 'seviye' => 0, 'parent_id' => null, 'aktiflik_durumu' => true],
            ['id' => 3, 'name' => 'Arsa & Arazi', 'slug' => 'arsa-arazi', 'seviye' => 0, 'parent_id' => null, 'aktiflik_durumu' => true],
            ['id' => 4, 'name' => 'Yazlık Kiralama', 'slug' => 'yazlik-kiralama', 'seviye' => 0, 'parent_id' => null, 'aktiflik_durumu' => true],
            ['id' => 5, 'name' => 'Turistik Tesisler', 'slug' => 'turistik-tesisler', 'seviye' => 0, 'parent_id' => null, 'aktiflik_durumu' => true],
            ['id' => 6, 'name' => 'Projeden Satış', 'slug' => 'projeden-satis', 'seviye' => 0, 'parent_id' => null, 'aktiflik_durumu' => true],
        ];

        // Alt Kategoriler (seviye=1)
        $altKategoriler = [
            ['id' => 7, 'name' => 'Daire', 'slug' => 'daire', 'seviye' => 1, 'parent_id' => 1, 'aktiflik_durumu' => true],
            ['id' => 8, 'name' => 'Villa', 'slug' => 'villa', 'seviye' => 1, 'parent_id' => 1, 'aktiflik_durumu' => true],
            ['id' => 9, 'name' => 'Müstakil Ev', 'slug' => 'mustakil-ev', 'seviye' => 1, 'parent_id' => 1, 'aktiflik_durumu' => true],
            ['id' => 11, 'name' => 'Ofis', 'slug' => 'ofis', 'seviye' => 1, 'parent_id' => 2, 'aktiflik_durumu' => true],
            ['id' => 12, 'name' => 'Dükkan', 'slug' => 'dukkan', 'seviye' => 1, 'parent_id' => 2, 'aktiflik_durumu' => true],
            ['id' => 15, 'name' => 'Arsa (Konut/Villa)', 'slug' => 'arsa-konut-villa', 'seviye' => 1, 'parent_id' => 3, 'aktiflik_durumu' => true],
            ['id' => 17, 'name' => 'Tarla', 'slug' => 'tarla', 'seviye' => 1, 'parent_id' => 3, 'aktiflik_durumu' => true],
            ['id' => 18, 'name' => 'Zeytinlik', 'slug' => 'zeytinlik', 'seviye' => 1, 'parent_id' => 3, 'aktiflik_durumu' => true],
            ['id' => 26, 'name' => 'Villa', 'slug' => 'villa-tipi', 'seviye' => 1, 'parent_id' => 4, 'aktiflik_durumu' => true],
            ['id' => 32, 'name' => 'Otel', 'slug' => 'otel', 'seviye' => 1, 'parent_id' => 5, 'aktiflik_durumu' => true],
            ['id' => 23, 'name' => 'Konut Projesi', 'slug' => 'konut-projesi', 'seviye' => 1, 'parent_id' => 6, 'aktiflik_durumu' => true],
        ];

        foreach (array_merge($anaKategoriler, $altKategoriler) as $cat) {
            $this->ensureKategori($cat['slug'], $cat);
        }
    }

    private function seedTemplates(): void
    {
        $templates = [
            // Arsa & Arazi (3)
            ['id' => 1, 'kategori_id' => 3, 'yayin_tipi_id' => 1, 'slug' => 'arsa-arazi-satilik', 'ad' => 'Arsa & Arazi Satılık'],
            ['id' => 2, 'kategori_id' => 3, 'yayin_tipi_id' => 2, 'slug' => 'arsa-arazi-kiralik', 'ad' => 'Arsa & Arazi Kiralık'],
            // Arsa (Konut/Villa) (15)
            ['id' => 3, 'kategori_id' => 15, 'yayin_tipi_id' => 1, 'slug' => 'arsa-konut-villa-satilik', 'ad' => 'Arsa (Konut/Villa) Satılık'],
            ['id' => 4, 'kategori_id' => 15, 'yayin_tipi_id' => 2, 'slug' => 'arsa-konut-villa-kiralik', 'ad' => 'Arsa (Konut/Villa) Kiralık'],
            ['id' => 5, 'kategori_id' => 15, 'yayin_tipi_id' => 3, 'slug' => 'arsa-konut-villa-kat-karsiligi', 'ad' => 'Arsa (Konut/Villa) Kat Karşılığı'],
            // Zeytinlik (18)
            ['id' => 10, 'kategori_id' => 18, 'yayin_tipi_id' => 1, 'slug' => 'zeytinlik-satilik', 'ad' => 'Zeytinlik Satılık'],
            // Konut (1)
            ['id' => 18, 'kategori_id' => 1, 'yayin_tipi_id' => 1, 'slug' => 'konut-satilik', 'ad' => 'Konut Satılık'],
            ['id' => 19, 'kategori_id' => 1, 'yayin_tipi_id' => 2, 'slug' => 'konut-kiralik', 'ad' => 'Konut Kiralık'],
            // Daire (7)
            ['id' => 20, 'kategori_id' => 7, 'yayin_tipi_id' => 1, 'slug' => 'daire-satilik', 'ad' => 'Daire Satılık'],
            ['id' => 21, 'kategori_id' => 7, 'yayin_tipi_id' => 2, 'slug' => 'daire-kiralik', 'ad' => 'Daire Kiralık'],
            // Villa (8)
            ['id' => 22, 'kategori_id' => 8, 'yayin_tipi_id' => 1, 'slug' => 'villa-satilik', 'ad' => 'Villa Satılık'],
            ['id' => 23, 'kategori_id' => 8, 'yayin_tipi_id' => 2, 'slug' => 'villa-kiralik', 'ad' => 'Villa Kiralık'],
            ['id' => 24, 'kategori_id' => 8, 'yayin_tipi_id' => 5, 'slug' => 'villa-gunluk', 'ad' => 'Villa Günlük'],
            ['id' => 25, 'kategori_id' => 8, 'yayin_tipi_id' => 6, 'slug' => 'villa-haftalik', 'ad' => 'Villa Haftalık'],
            ['id' => 26, 'kategori_id' => 8, 'yayin_tipi_id' => 7, 'slug' => 'villa-aylik', 'ad' => 'Villa Aylık'],
            ['id' => 27, 'kategori_id' => 8, 'yayin_tipi_id' => 8, 'slug' => 'villa-sezonluk', 'ad' => 'Villa Sezonluk'],
            // İşyeri (2)
            ['id' => 32, 'kategori_id' => 2, 'yayin_tipi_id' => 1, 'slug' => 'isyeri-satilik', 'ad' => 'İşyeri Satılık'],
            ['id' => 33, 'kategori_id' => 2, 'yayin_tipi_id' => 2, 'slug' => 'isyeri-kiralik', 'ad' => 'İşyeri Kiralık'],
            ['id' => 34, 'kategori_id' => 2, 'yayin_tipi_id' => 4, 'slug' => 'isyeri-devren', 'ad' => 'İşyeri Devren'],
            // Ofis (11)
            ['id' => 35, 'kategori_id' => 11, 'yayin_tipi_id' => 1, 'slug' => 'ofis-satilik', 'ad' => 'Ofis Satılık'],
            ['id' => 36, 'kategori_id' => 11, 'yayin_tipi_id' => 2, 'slug' => 'ofis-kiralik', 'ad' => 'Ofis Kiralık'],
            ['id' => 37, 'kategori_id' => 11, 'yayin_tipi_id' => 4, 'slug' => 'ofis-devren', 'ad' => 'Ofis Devren'],
            // Dükkan (12)
            ['id' => 38, 'kategori_id' => 12, 'yayin_tipi_id' => 1, 'slug' => 'dukkan-satilik', 'ad' => 'Dükkan Satılık'],
            ['id' => 39, 'kategori_id' => 12, 'yayin_tipi_id' => 2, 'slug' => 'dukkan-kiralik', 'ad' => 'Dükkan Kiralık'],
            ['id' => 40, 'kategori_id' => 12, 'yayin_tipi_id' => 4, 'slug' => 'dukkan-devren', 'ad' => 'Dükkan Devren'],
            // Yazlık Kiralama (4)
            ['id' => 46, 'kategori_id' => 4, 'yayin_tipi_id' => 5, 'slug' => 'yazlik-kiralama-gunluk', 'ad' => 'Yazlık Kiralama Günlük'],
            ['id' => 47, 'kategori_id' => 4, 'yayin_tipi_id' => 6, 'slug' => 'yazlik-kiralama-haftalik', 'ad' => 'Yazlık Kiralama Haftalık'],
            ['id' => 48, 'kategori_id' => 4, 'yayin_tipi_id' => 7, 'slug' => 'yazlik-kiralama-aylik', 'ad' => 'Yazlık Kiralama Aylık'],
            ['id' => 49, 'kategori_id' => 4, 'yayin_tipi_id' => 8, 'slug' => 'yazlik-kiralama-sezonluk', 'ad' => 'Yazlık Kiralama Sezonluk'],
            // Villa Tipi (26)
            ['id' => 50, 'kategori_id' => 26, 'yayin_tipi_id' => 5, 'slug' => 'villa-tipi-gunluk', 'ad' => 'Villa Tipi Günlük'],
            ['id' => 51, 'kategori_id' => 26, 'yayin_tipi_id' => 6, 'slug' => 'villa-tipi-haftalik', 'ad' => 'Villa Tipi Haftalık'],
            ['id' => 52, 'kategori_id' => 26, 'yayin_tipi_id' => 7, 'slug' => 'villa-tipi-aylik', 'ad' => 'Villa Tipi Aylık'],
            ['id' => 53, 'kategori_id' => 26, 'yayin_tipi_id' => 8, 'slug' => 'villa-tipi-sezonluk', 'ad' => 'Villa Tipi Sezonluk'],
            // Turistik Tesisler (5)
            ['id' => 74, 'kategori_id' => 5, 'yayin_tipi_id' => 1, 'slug' => 'turistik-tesisler-satilik', 'ad' => 'Turistik Tesisler Satılık'],
            ['id' => 75, 'kategori_id' => 5, 'yayin_tipi_id' => 2, 'slug' => 'turistik-tesisler-kiralik', 'ad' => 'Turistik Tesisler Kiralık'],
            // Otel (32)
            ['id' => 76, 'kategori_id' => 32, 'yayin_tipi_id' => 1, 'slug' => 'otel-satilik', 'ad' => 'Otel Satılık'],
            ['id' => 77, 'kategori_id' => 32, 'yayin_tipi_id' => 2, 'slug' => 'otel-kiralik', 'ad' => 'Otel Kiralık'],
            // Projeden Satış (6)
            ['id' => 82, 'kategori_id' => 6, 'yayin_tipi_id' => 1, 'slug' => 'projeden-satis-satilik', 'ad' => 'Projeden Satış Satılık'],
            // Konut Projesi (23)
            ['id' => 83, 'kategori_id' => 23, 'yayin_tipi_id' => 1, 'slug' => 'konut-projesi-satilik', 'ad' => 'Konut Projesi Satılık'],
        ];

        foreach ($templates as $tmpl) {
            $this->ensureYayinTipi($tmpl['slug'], [
                'id' => $tmpl['id'],
                'kategori_id' => $tmpl['kategori_id'],
                'yayin_tipi_id' => $tmpl['yayin_tipi_id'],
                'ad' => $tmpl['ad'],
                'aktiflik_durumu' => true,
                'display_order' => 1,
            ]);
        }
    }

    // ── Policy Matrix Tests ──

    public function test_konut_allows_satilik_kiralik(): void
    {
        $ids = $this->policy->allowedForCategory(1);
        $this->assertEqualsCanonicalizing([18, 19], $ids);
    }

    public function test_daire_allows_satilik_kiralik(): void
    {
        $ids = $this->policy->allowedForCategory(7);
        $this->assertEqualsCanonicalizing([20, 21], $ids);
    }

    public function test_villa_allows_all_six_types(): void
    {
        $ids = $this->policy->allowedForCategory(8);
        $this->assertEqualsCanonicalizing([22, 23, 24, 25, 26, 27], $ids);
    }

    public function test_arsa_arazi_allows_satilik_kiralik(): void
    {
        $ids = $this->policy->allowedForCategory(3);
        $this->assertEqualsCanonicalizing([1, 2], $ids);
    }

    public function test_arsa_konut_villa_allows_satilik_kiralik(): void
    {
        $ids = $this->policy->allowedForCategory(15);
        $this->assertContains(3, $ids, 'Arsa/Konut/Villa should include Satılık');
        $this->assertContains(4, $ids, 'Arsa/Konut/Villa should include Kiralık');
        $this->assertContains(5, $ids, 'Arsa/Konut/Villa should include Kat Karşılığı');
    }

    public function test_zeytinlik_allows_only_satilik(): void
    {
        $ids = $this->policy->allowedForCategory(18);
        $this->assertEquals([10], $ids);
    }

    public function test_yazlik_kiralama_allows_seasonal_types(): void
    {
        $ids = $this->policy->allowedForCategory(4);
        $this->assertEqualsCanonicalizing([46, 47, 48, 49], $ids);
    }

    public function test_villa_tipi_yazlik_allows_seasonal(): void
    {
        $ids = $this->policy->allowedForCategory(26);
        $this->assertEqualsCanonicalizing([50, 51, 52, 53], $ids);
    }

    public function test_turistik_tesisler_allows_satilik_kiralik(): void
    {
        $ids = $this->policy->allowedForCategory(5);
        $this->assertEqualsCanonicalizing([74, 75], $ids);
    }

    public function test_projeden_satis_allows_only_satilik(): void
    {
        $ids = $this->policy->allowedForCategory(6);
        $this->assertEquals([82], $ids);
    }

    public function test_ofis_allows_satilik_kiralik(): void
    {
        $ids = $this->policy->allowedForCategory(11);
        $this->assertContains(35, $ids, 'Ofis should include Satılık');
        $this->assertContains(36, $ids, 'Ofis should include Kiralık');
        $this->assertContains(37, $ids, 'Ofis should include Devren');
    }

    // ── Slug Canonical Matching Tests ──

    public function test_gunluk_kiralama_slug_matches_gunluk_matrix_key(): void
    {
        $ids = $this->policy->allowedForCategory(8); // Villa
        $this->assertContains(24, $ids, 'Villa Günlük (ID:24, slug:villa-gunluk) should match matrix key gunluk');
    }

    public function test_sezonluk_kiralama_slug_matches_sezonluk_matrix_key(): void
    {
        $ids = $this->policy->allowedForCategory(8); // Villa
        $this->assertContains(27, $ids, 'Villa Sezonluk (ID:27, slug:villa-sezonluk) should match matrix key sezonluk');
    }

    // ── isAllowed Tests ──

    public function test_is_allowed_returns_true_for_valid_combo(): void
    {
        $this->assertTrue($this->policy->isAllowed(7, 20)); // Daire + Satılık
    }

    public function test_is_allowed_returns_false_for_invalid_combo(): void
    {
        $this->assertFalse($this->policy->isAllowed(7, 24)); // Daire + Villa Günlük = not allowed
    }

    public function test_is_allowed_returns_false_for_nonexistent_category(): void
    {
        $this->assertFalse($this->policy->isAllowed(9999, 20));
    }

    // ── validate() Tests ──

    public function test_validate_throws_for_invalid_combo(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->policy->validate(7, 24); // Daire + Villa Günlük = not allowed
    }

    public function test_validate_passes_for_valid_combo(): void
    {
        $this->policy->validate(8, 24); // Villa + Villa Günlük = allowed
        $this->assertTrue(true); // No exception = pass
    }

    // ── EffectiveListingTypeResolver Tests ──

    public function test_resolver_prefers_sub_category(): void
    {
        // Villa (sub: 8) allows 6 types, Konut (main: 1) allows 2
        $ids = $this->resolver->resolveIds(1, 8);
        $this->assertEqualsCanonicalizing([22, 23, 24, 25, 26, 27], $ids);
    }

    public function test_resolver_falls_back_to_main_category(): void
    {
        // Sub-category with no policy → falls back to main
        $mainIds = $this->resolver->resolveIds(1, null);
        $this->assertEqualsCanonicalizing([18, 19], $mainIds);
    }

    public function test_resolver_is_allowed_checks_sub_first(): void
    {
        // Villa allows Günlük (24), Konut doesn't — sub wins
        $this->assertTrue($this->resolver->isAllowed(1, 8, 24));
    }

    public function test_resolver_is_allowed_rejects_invalid(): void
    {
        // Neither Konut nor Daire allows Villa Günlük (24)
        $this->assertFalse($this->resolver->isAllowed(1, 7, 24));
    }

    public function test_resolver_debug_returns_chain_info(): void
    {
        $debug = $this->resolver->debug(3, 15);

        $this->assertEquals('sub_category', $debug['resolution_source']);
        $this->assertEquals('arsa-arazi', $debug['main_category']['slug']);
        $this->assertEquals('arsa-konut-villa', $debug['sub_category']['slug']);
        $this->assertTrue($debug['has_explicit_policy']);
        $this->assertNotEmpty($debug['resolved_types']);
    }

    // ── API Endpoint Tests ──

    public function test_publication_types_endpoint_returns_category_aware_list(): void
    {
        $response = $this->getJson('/api/v1/categories/publication-types/7');
        $response->assertOk();

        $types = $response->json('data.types');
        $slugs = array_column($types, 'slug');

        $this->assertContains('daire-satilik', $slugs);
        $this->assertContains('daire-kiralik', $slugs);
        $this->assertNotContains('villa-gunluk', $slugs, 'Daire should NOT have Günlük Kiralama');
    }

    public function test_publication_types_endpoint_villa_includes_seasonal(): void
    {
        $response = $this->getJson('/api/v1/categories/publication-types/8');
        $response->assertOk();

        $types = $response->json('data.types');
        $slugs = array_column($types, 'slug');

        $this->assertContains('villa-satilik', $slugs);
        $this->assertContains('villa-gunluk', $slugs);
        $this->assertContains('villa-sezonluk', $slugs);
        $this->assertCount(6, $types);
    }

    public function test_publication_types_endpoint_projeden_satis_only_satilik(): void
    {
        $response = $this->getJson('/api/v1/categories/publication-types/23');
        $response->assertOk();

        $types = $response->json('data.types');
        $this->assertCount(1, $types);
        $this->assertEquals('konut-projesi-satilik', $types[0]['slug']);
    }

    public function test_publication_types_endpoint_nonexistent_category(): void
    {
        $response = $this->getJson('/api/v1/categories/publication-types/9999');
        $response->assertNotFound();
    }

    // ── StoreIlanRequest Policy Guard Tests ──

    public function test_store_request_accepts_valid_category_yayin_tipi_combo(): void
    {
        // Konut(1) > Daire(7) + Daire Kiralık(21) = allowed by policy
        $user = User::factory()->create();

        $response = $this->withoutMiddleware([
            RoleMiddleware::class,
            GlobalWriteGuard::class,
            EnsureEmailIsVerified::class,
        ])->actingAs($user)->post(route('admin.ilanlar.store'), [
            'baslik' => 'Test İlan Valid Combo',
            'aciklama' => 'Test açıklama',
            'fiyat' => 500000,
            'para_birimi' => 'TRY',
            'ana_kategori_id' => 1, // Konut
            'alt_kategori_id' => 7, // Daire
            'yayin_tipi_id' => 21,  // Daire Kiralık — allowed for Daire
            'ilan_sahibi_id' => Kisi::factory()->create()->id,
            'yayin_durumu' => 'taslak',
        ]);

        // Valid combo: yayin_tipi_id should NOT be in validation errors
        $response->assertSessionDoesntHaveErrors(['yayin_tipi_id']);
    }

    public function test_store_request_rejects_invalid_category_yayin_tipi_combo(): void
    {
        $this->markTestSkipped('Category-yayin_tipi combo validation not yet implemented in IlanCrudController');
    }

    // ── Quick Selections API Tests ──

    public function test_quick_selections_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/wizard/quick-selections');
        $response->assertOk();

        // Assert all 6 curated combos are returned
        $this->assertCount(6, $response->json('data'), 'Quick selections must return exactly 6 cards');
        foreach ($response->json('data') as $item) {
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('ana_kategori_id', $item);
            $this->assertArrayHasKey('alt_kategori_id', $item);
            $this->assertArrayHasKey('yayin_tipi_id', $item);
            $this->assertArrayHasKey('yayin_tipi_slug', $item);
            $this->assertNotEmpty($item['yayin_tipi_slug'], 'yayin_tipi_slug must not be null');
        }
    }

    public function test_quick_selections_never_returns_invalid_combinations(): void
    {
        $response = $this->getJson('/api/v1/wizard/quick-selections');
        $response->assertOk();

        $resolver = app(EffectiveListingTypeResolver::class);

        foreach ($response->json('data') as $item) {
            $this->assertTrue(
                $resolver->isAllowed(
                    $item['ana_kategori_id'],
                    $item['alt_kategori_id'],
                    $item['yayin_tipi_id']
                ),
                "Quick selection '{$item['label']}' has invalid combo: ana={$item['ana_kategori_id']} alt={$item['alt_kategori_id']} yt={$item['yayin_tipi_id']}"
            );
        }
    }

    public function test_quick_selections_excludes_phantom_kat_karsiligi(): void
    {
        $response = $this->getJson('/api/v1/wizard/quick-selections');
        $response->assertOk();

        $slugs = array_column($response->json('data'), 'yayin_tipi_slug');
        $this->assertNotContains('kat-karsiligi', $slugs, 'Phantom kat-karsiligi must never appear in quick selections');
    }
}
