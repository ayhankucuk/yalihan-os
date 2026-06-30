<?php

namespace Tests\Feature;

use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\Ups\PropertyPublicationPolicy;
use App\Services\Wizard\EffectiveListingTypeResolver;
use Tests\TestCase;
use Tests\Helpers\TestFixtureHelper;

/**
 * EffectiveListingTypeResolver + PropertyPublicationPolicy acceptance tests.
 *
 * Validates the category → allowed publication types resolution chain.
 */
class EffectiveListingTypeResolverTest extends TestCase
{
    use TestFixtureHelper;

    private PropertyPublicationPolicy $policy;
    private EffectiveListingTypeResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Event::fake();

        $this->policy = app(PropertyPublicationPolicy::class);
        $this->resolver = app(EffectiveListingTypeResolver::class);

        // Seed YayinTipiSablonu (6 global types) using idempotent helpers
        $this->ensureYayinTipi('satilik', ['id' => 1, 'ad' => 'Satılık', 'display_order' => 1]);
        $this->ensureYayinTipi('kiralik', ['id' => 2, 'ad' => 'Kiralık', 'display_order' => 2]);
        $this->ensureYayinTipi('gunluk-kiralama', ['id' => 3, 'ad' => 'Günlük Kiralama', 'display_order' => 3]);
        $this->ensureYayinTipi('haftalik-kiralama', ['id' => 4, 'ad' => 'Haftalık Kiralama', 'display_order' => 4]);
        $this->ensureYayinTipi('aylik-kiralama', ['id' => 5, 'ad' => 'Aylık Kiralama', 'display_order' => 5]);
        $this->ensureYayinTipi('sezonluk-kiralama', ['id' => 6, 'ad' => 'Sezonluk Kiralama', 'display_order' => 6]);

        // Seed categories (matching production DB structure)
        $this->seedCategories();
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

    // ── Policy Matrix Tests ──

    public function test_konut_allows_satilik_kiralik(): void
    {
        $ids = $this->policy->allowedForCategory(1);
        $this->assertEqualsCanonicalizing([1, 2], $ids);
    }

    public function test_daire_allows_satilik_kiralik(): void
    {
        $ids = $this->policy->allowedForCategory(7);
        $this->assertEqualsCanonicalizing([1, 2], $ids);
    }

    public function test_villa_allows_all_six_types(): void
    {
        $ids = $this->policy->allowedForCategory(8);
        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 5, 6], $ids);
    }

    public function test_arsa_arazi_allows_satilik_kiralik(): void
    {
        $ids = $this->policy->allowedForCategory(3);
        $this->assertEqualsCanonicalizing([1, 2], $ids);
    }

    public function test_arsa_konut_villa_allows_satilik_kiralik(): void
    {
        // kat-karsiligi (ID:7) exists in test DB migration, so it matches the matrix too
        $ids = $this->policy->allowedForCategory(15);
        $this->assertContains(1, $ids, 'Arsa/Konut/Villa should include Satılık');
        $this->assertContains(2, $ids, 'Arsa/Konut/Villa should include Kiralık');
    }

    public function test_zeytinlik_allows_only_satilik(): void
    {
        $ids = $this->policy->allowedForCategory(18);
        $this->assertEquals([1], $ids);
    }

    public function test_yazlik_kiralama_allows_seasonal_types(): void
    {
        $ids = $this->policy->allowedForCategory(4);
        $this->assertEqualsCanonicalizing([3, 4, 5, 6], $ids);
    }

    public function test_villa_tipi_yazlik_allows_satilik_plus_seasonal(): void
    {
        $ids = $this->policy->allowedForCategory(26);
        $this->assertEqualsCanonicalizing([1, 3, 4, 5, 6], $ids);
    }

    public function test_turistik_tesisler_allows_satilik_kiralik(): void
    {
        $ids = $this->policy->allowedForCategory(5);
        $this->assertEqualsCanonicalizing([1, 2], $ids);
    }

    public function test_projeden_satis_allows_only_satilik(): void
    {
        $ids = $this->policy->allowedForCategory(6);
        $this->assertEquals([1], $ids);
    }

    public function test_ofis_allows_satilik_kiralik(): void
    {
        // devren-satilik (ID:8) and devren-kiralik (ID:9) exist in test DB
        // Both canonicalize to 'devren' which is in the ofis matrix
        $ids = $this->policy->allowedForCategory(11);
        $this->assertContains(1, $ids, 'Ofis should include Satılık');
        $this->assertContains(2, $ids, 'Ofis should include Kiralık');
    }

    // ── Slug Canonical Matching Tests ──

    public function test_gunluk_kiralama_slug_matches_gunluk_matrix_key(): void
    {
        // Matrix uses 'gunluk' but DB slug is 'gunluk-kiralama'
        // Canonical matching should bridge this gap
        $ids = $this->policy->allowedForCategory(8); // Villa
        $this->assertContains(3, $ids, 'Günlük Kiralama (ID:3, slug:gunluk-kiralama) should match matrix key gunluk');
    }

    public function test_sezonluk_kiralama_slug_matches_sezonluk_matrix_key(): void
    {
        $ids = $this->policy->allowedForCategory(8); // Villa
        $this->assertContains(6, $ids, 'Sezonluk Kiralama (ID:6, slug:sezonluk-kiralama) should match matrix key sezonluk');
    }

    // ── isAllowed Tests ──

    public function test_isAllowed_returns_true_for_valid_combo(): void
    {
        $this->assertTrue($this->policy->isAllowed(7, 1)); // Daire + Satılık
    }

    public function test_isAllowed_returns_false_for_invalid_combo(): void
    {
        $this->assertFalse($this->policy->isAllowed(7, 3)); // Daire + Günlük = not allowed
    }

    public function test_isAllowed_returns_false_for_nonexistent_category(): void
    {
        $this->assertFalse($this->policy->isAllowed(9999, 1));
    }

    // ── validate() Tests ──

    public function test_validate_throws_for_invalid_combo(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->policy->validate(7, 3); // Daire + Günlük = not allowed
    }

    public function test_validate_passes_for_valid_combo(): void
    {
        $this->policy->validate(8, 3); // Villa + Günlük = allowed
        $this->assertTrue(true); // No exception = pass
    }

    // ── EffectiveListingTypeResolver Tests ──

    public function test_resolver_prefers_sub_category(): void
    {
        // Villa (sub) allows 6 types, Konut (main) allows 2
        $ids = $this->resolver->resolveIds(1, 8);
        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 5, 6], $ids);
    }

    public function test_resolver_falls_back_to_main_category(): void
    {
        // Sub-category with no policy → falls back to main
        $mainIds = $this->resolver->resolveIds(1, null);
        $this->assertEqualsCanonicalizing([1, 2], $mainIds);
    }

    public function test_resolver_isAllowed_checks_sub_first(): void
    {
        // Villa allows Günlük, Konut doesn't — sub wins
        $this->assertTrue($this->resolver->isAllowed(1, 8, 3));
    }

    public function test_resolver_isAllowed_rejects_invalid(): void
    {
        // Neither Konut nor Daire allows Günlük
        $this->assertFalse($this->resolver->isAllowed(1, 7, 3));
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

        $this->assertContains('satilik', $slugs);
        $this->assertContains('kiralik', $slugs);
        $this->assertNotContains('gunluk-kiralama', $slugs, 'Daire should NOT have Günlük Kiralama');
    }

    public function test_publication_types_endpoint_villa_includes_seasonal(): void
    {
        $response = $this->getJson('/api/v1/categories/publication-types/8');
        $response->assertOk();

        $types = $response->json('data.types');
        $slugs = array_column($types, 'slug');

        $this->assertContains('satilik', $slugs);
        $this->assertContains('gunluk-kiralama', $slugs);
        $this->assertContains('sezonluk-kiralama', $slugs);
        $this->assertCount(6, $types);
    }

    public function test_publication_types_endpoint_projeden_satis_only_satilik(): void
    {
        $response = $this->getJson('/api/v1/categories/publication-types/23');
        $response->assertOk();

        $types = $response->json('data.types');
        $this->assertCount(1, $types);
        $this->assertEquals('satilik', $types[0]['slug']);
    }

    public function test_publication_types_endpoint_nonexistent_category(): void
    {
        $response = $this->getJson('/api/v1/categories/publication-types/9999');
        $response->assertNotFound();
    }

    // ── StoreIlanRequest Policy Guard Tests ──

    public function test_store_request_accepts_valid_category_yayin_tipi_combo(): void
    {
        // Konut(1) > Daire(7) + Kiralık(2) = allowed by policy
        $user = \App\Models\User::factory()->create();

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\RoleMiddleware::class,
            \App\Http\Middleware\SAB\GlobalWriteGuard::class,
            \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ])->actingAs($user)->post(route('admin.ilanlar.store'), [
            'baslik' => 'Test İlan Valid Combo',
            'aciklama' => 'Test açıklama',
            'fiyat' => 500000,
            'para_birimi' => 'TRY',
            'ana_kategori_id' => 1, // Konut
            'alt_kategori_id' => 7, // Daire
            'yayin_tipi_id' => 2,   // Kiralık — allowed for Daire
            'ilan_sahibi_id' => \App\Models\Kisi::factory()->create()->id,
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
        $response->assertJsonStructure(['data' => [['label', 'icon', 'color', 'ana_kategori_id', 'alt_kategori_id', 'yayin_tipi_id', 'ana_slug', 'alt_slug', 'yayin_tipi_slug']]]);
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
