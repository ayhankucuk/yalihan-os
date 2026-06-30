<?php

namespace Tests\Feature\PropertyEngine;

use App\Enums\AktiflikDurumu;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\FeatureCategory;
use App\Models\IlanKategori;
use App\Models\User;
use App\Models\YayinTipiSablonu;
use App\Services\Wizard\FeatureTemplateResolver;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression tests for PR-1: Property Engine Runtime Stabilization.
 *
 * Covers:
 *  - Bug #1: Feature::aktif() scope missing → 500 on template pages
 *  - Bug #2: Wizard resolver returns 0 features when sub_category_id provided
 *  - Bug #3: featureCategories() view missing → redirect fix
 */
class RuntimeStabilizationTest extends TestCase
{
    private FeatureTemplateResolver $resolver;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(FeatureTemplateResolver::class);

        $this->admin = User::factory()->create();
        $roleClass = \App\Models\Role::class;
        if (! $roleClass::where('name', 'admin')->where('guard_name', 'web')->exists()) {
            $roleClass::create(['name' => 'admin', 'guard_name' => 'web']);
        }
        DB::table('model_has_roles')->insertOrIgnore([
            'role_id' => $roleClass::where('name', 'admin')->first()->id,
            'model_type' => User::class,
            'model_id' => $this->admin->id,
        ]);
    }

    // ── Bug #1: Feature::aktif() scope ──

    /** @test */
    public function feature_aktif_scope_returns_only_active_features(): void
    {
        $category = FeatureCategory::factory()->create();

        $active = Feature::factory()->create([
            'name' => 'Active Feature',
            'slug' => 'active-feature-' . uniqid(),
            'feature_category_id' => $category->id,
            'aktiflik_durumu' => AktiflikDurumu::AKTIF,
        ]);

        $passive = Feature::factory()->create([
            'name' => 'Passive Feature',
            'slug' => 'passive-feature-' . uniqid(),
            'feature_category_id' => $category->id,
            'aktiflik_durumu' => AktiflikDurumu::PASIF,
        ]);

        $results = Feature::aktif()->pluck('id');

        $this->assertTrue($results->contains($active->id), 'Active feature should be in aktif() scope');
        $this->assertFalse($results->contains($passive->id), 'Passive feature should NOT be in aktif() scope');
    }

    /** @test */
    public function feature_aktif_scope_filters_by_enum_value(): void
    {
        $category = FeatureCategory::factory()->create();

        $feature = Feature::factory()->create([
            'name' => 'Enum Test',
            'slug' => 'enum-test-' . uniqid(),
            'feature_category_id' => $category->id,
            'aktiflik_durumu' => AktiflikDurumu::AKTIF,
        ]);

        // aktif scope should pass integer 1 (AKTIF) to where clause
        $count = Feature::aktif()->where('id', $feature->id)->count();
        $this->assertEquals(1, $count);

        // Deactivate
        $feature->update(['aktiflik_durumu' => AktiflikDurumu::PASIF]);
        $count = Feature::aktif()->where('id', $feature->id)->count();
        $this->assertEquals(0, $count);
    }

    // ── Bug #2: Wizard resolver with subcategory ──

    /** @test */
    public function resolver_returns_features_with_subcategory_provided(): void
    {
        // Setup: main category, sub category, listing type
        $mainCat = IlanKategori::forceCreate([
            'name' => 'Resolver Test Ana',
            'slug' => 'resolver-test-ana-' . uniqid(),
            'seviye' => 0,
            'parent_id' => null,
            'aktiflik_durumu' => true,
        ]);

        $subCat = IlanKategori::forceCreate([
            'name' => 'Resolver Test Alt',
            'slug' => 'resolver-test-alt-' . uniqid(),
            'seviye' => 1,
            'parent_id' => $mainCat->id,
            'aktiflik_durumu' => true,
        ]);

        $listingType = YayinTipiSablonu::firstOrCreate(
            ['slug' => 'satilik'],
            ['ad' => 'Satılık', 'aktiflik_durumu' => true, 'display_order' => 1]
        );

        $featCat = FeatureCategory::factory()->create();

        $feature = Feature::factory()->create([
            'name' => 'SubCat Resolver Test',
            'slug' => 'subcat-resolver-' . uniqid(),
            'feature_category_id' => $featCat->id,
            'aktiflik_durumu' => AktiflikDurumu::AKTIF,
        ]);

        // Global listing-type assignment (NULL main_category_id, NULL sub_category_id)
        FeatureAssignment::create([
            'feature_id' => $feature->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id' => $listingType->id,
            'listing_type_id' => $listingType->id,
            'scope_type' => 'listing_type',
            'main_category_id' => null,
            'sub_category_id' => null,
            'is_required' => true,
            'is_visible' => true,
            'display_order' => 1,
            'aktiflik_durumu' => true,
        ]);

        // Resolve with subcategory — this was the bug: returned 0 features
        $result = $this->resolver->resolveFeatures(
            $mainCat->id,
            $subCat->id,
            $listingType->id
        );

        $this->assertGreaterThan(0, $result->count(), 'Resolver must return features when subcategory is provided');
        $this->assertTrue(
            $result->contains('feature_id', $feature->id),
            'Global listing-type assignment should be visible when subcategory is provided'
        );
    }

    /** @test */
    public function resolver_returns_features_without_subcategory(): void
    {
        $mainCat = IlanKategori::forceCreate([
            'name' => 'NoSub Test Ana',
            'slug' => 'nosub-test-ana-' . uniqid(),
            'seviye' => 0,
            'parent_id' => null,
            'aktiflik_durumu' => true,
        ]);

        $listingType = YayinTipiSablonu::firstOrCreate(
            ['slug' => 'satilik'],
            ['ad' => 'Satılık', 'aktiflik_durumu' => true, 'display_order' => 1]
        );

        $featCat = FeatureCategory::factory()->create();

        $feature = Feature::factory()->create([
            'name' => 'NoSub Resolver Test',
            'slug' => 'nosub-resolver-' . uniqid(),
            'feature_category_id' => $featCat->id,
            'aktiflik_durumu' => AktiflikDurumu::AKTIF,
        ]);

        FeatureAssignment::create([
            'feature_id' => $feature->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id' => $listingType->id,
            'listing_type_id' => $listingType->id,
            'scope_type' => 'listing_type',
            'main_category_id' => null,
            'sub_category_id' => null,
            'is_required' => true,
            'is_visible' => true,
            'display_order' => 1,
            'aktiflik_durumu' => true,
        ]);

        // Resolve without subcategory (null)
        $result = $this->resolver->resolveFeatures(
            $mainCat->id,
            null,
            $listingType->id
        );

        $this->assertGreaterThan(0, $result->count(), 'Resolver must return features without subcategory');
        $this->assertTrue(
            $result->contains('feature_id', $feature->id),
            'Global listing-type assignment should be visible without subcategory'
        );
    }

    // ── Bug #3: featureCategories redirect ──

    /** @test */
    public function feature_categories_url_does_not_500(): void
    {
        // Note: /features/categories URL is caught by /{feature} wildcard show route
        // before it reaches the categories group. The fix ensures that if the method
        // IS reached, it redirects instead of rendering a missing view.
        // This test verifies no 500 at the URL level (observable behavior).
        $response = $this->actingAs($this->admin)
            ->get(route('admin.property-hub.features.categories.index'));

        $response->assertRedirect();
    }

    /** @test */
    public function feature_categories_controller_method_redirects(): void
    {
        // Direct controller method test: verify the fix returns a redirect
        $controller = app(\App\Http\Controllers\Admin\PropertyHubController::class);
        $result = $controller->featureCategories();

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $result);
        $this->assertStringContainsString('ozellikler/kategoriler', $result->getTargetUrl());
    }

    /** @test */
    public function yayin_tipi_sablonlari_page_loads_without_error(): void
    {
        // Ensure at least one listing type exists
        YayinTipiSablonu::firstOrCreate(
            ['slug' => 'satilik'],
            ['ad' => 'Satılık', 'aktiflik_durumu' => true, 'display_order' => 1]
        );

        $response = $this->actingAs($this->admin)
            ->get(route('admin.property-hub.templates.index'));

        $response->assertSuccessful();
    }
}
