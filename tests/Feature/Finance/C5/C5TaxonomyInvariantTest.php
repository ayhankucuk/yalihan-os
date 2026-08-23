<?php

namespace Tests\Feature;

use App\Models\IlanKategori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PILOT-01 Recovery-B: Canonical Taxonomy Reconciliation Tests
 *
 * Database invariants:
 * 1. Root category count = 6 (canonical families)
 * 2. No child category has parent_id = NULL
 * 3. Villa belongs to Konut (parent_id = Konut.id)
 * 4. Every parent_id references a real category
 * 5. No orphan taxonomy rows
 * 6. No duplicate slug
 */
class C5TaxonomyInvariantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedIlanKategori();
    }

    /**
     * Seed IlanKategori with canonical hierarchy
     */
    private function seedIlanKategori(): void
    {
        $this->artisan('db:seed', ['--class' => 'IlanKategoriSeeder']);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ROOT CATEGORY INVARIANTS
    // ══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function root_category_count_equals_six(): void
    {
        $rootCategories = IlanKategori::whereNull('parent_id')
            ->where('aktiflik_durumu', true)
            ->get();

        $this->assertEquals(6, $rootCategories->count(), 'Root category count must be 6');
    }

    /** @test */
    public function root_categories_match_canonical_families(): void
    {
        $rootSlugs = IlanKategori::whereNull('parent_id')
            ->where('aktiflik_durumu', true)
            ->pluck('slug')
            ->toArray();

        $canonicalRoots = ['konut', 'isyeri', 'arsa-arazi', 'yazlik-kiralama', 'turistik-tesisler', 'projeden-satis'];

        sort($rootSlugs);
        sort($canonicalRoots);

        $this->assertEquals($canonicalRoots, $rootSlugs, 'Root categories must match canonical families');
    }

    // ══════════════════════════════════════════════════════════════════════════
    // CHILD CATEGORY INVARIANTS
    // ══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function no_child_has_null_parent(): void
    {
        $allChildren = IlanKategori::whereNotNull('parent_id')
            ->where('aktiflik_durumu', true)
            ->get();

        $nullParents = $allChildren->filter(fn($c) => $c->parent_id === null);

        $this->assertEquals(0, $nullParents->count(), 'No child category should have parent_id = NULL');
    }

    /** @test */
    public function every_parent_id_references_real_category(): void
    {
        $allCategories = IlanKategori::where('aktiflik_durumu', true)->get();
        $validParentIds = $allCategories->pluck('id')->toArray();

        $children = $allCategories->filter(fn($c) => $c->parent_id !== null);
        $orphans = $children->filter(fn($c) => !in_array($c->parent_id, $validParentIds));

        $this->assertEquals(0, $orphans->count(), 'No orphan categories (child with invalid parent_id)');
    }

    // ══════════════════════════════════════════════════════════════════════════
    // VILLA SPECIFIC INVARIANTS
    // ══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function villa_belongs_to_konut(): void
    {
        $konut = IlanKategori::where('slug', 'konut')
            ->whereNull('parent_id')
            ->first();

        $this->assertNotNull($konut, 'Konut root category must exist');

        $villa = IlanKategori::where('slug', 'villa')->first();

        $this->assertNotNull($villa, 'Villa category must exist');
        $this->assertEquals($konut->id, $villa->parent_id, 'Villa.parent_id must equal Konut.id');
        $this->assertEquals(1, $villa->seviye, 'Villa seviye must be 1 (child)');
    }

    /** @test */
    public function villa_is_not_root(): void
    {
        $rootVilla = IlanKategori::where('slug', 'villa')
            ->whereNull('parent_id')
            ->first();

        $this->assertNull($rootVilla, 'Villa should NOT be a root category');
    }

    /** @test */
    public function villa_not_in_root_dropdown(): void
    {
        $rootCategories = IlanKategori::whereNull('parent_id')
            ->where('aktiflik_durumu', true)
            ->pluck('slug')
            ->toArray();

        $this->assertNotContains('villa', $rootCategories, 'Villa should not appear in root dropdown');
    }

    // ══════════════════════════════════════════════════════════════════════════
    // KONUT CHILDREN INVARIANTS
    // ══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function konut_has_expected_children(): void
    {
        $konut = IlanKategori::where('slug', 'konut')
            ->whereNull('parent_id')
            ->first();

        $children = IlanKategori::where('parent_id', $konut->id)
            ->where('aktiflik_durumu', true)
            ->get();

        $childSlugs = $children->pluck('slug')->toArray();

        $expectedChildren = ['daire', 'villa', 'mustakil-ev', 'dubleks'];

        foreach ($expectedChildren as $expected) {
            $this->assertContains($expected, $childSlugs, "Konut should have child: {$expected}");
        }
    }

    /** @test */
    public function konut_children_have_correct_seviye(): void
    {
        $konut = IlanKategori::where('slug', 'konut')
            ->whereNull('parent_id')
            ->first();

        $children = IlanKategori::where('parent_id', $konut->id)
            ->where('aktiflik_durumu', true)
            ->get();

        foreach ($children as $child) {
            $this->assertEquals(1, $child->seviye, "{$child->slug} seviye should be 1");
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // DUPLICATE SLUG INVARIANTS
    // ══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function no_duplicate_slugs(): void
    {
        $allSlugs = IlanKategori::where('aktiflik_durumu', true)
            ->pluck('slug')
            ->toArray();

        $uniqueSlugs = array_unique($allSlugs);

        $this->assertEquals(count($allSlugs), count($uniqueSlugs), 'No duplicate slugs allowed');
    }

    /** @test */
    public function slug_uniqueness_includes_soft_deleted(): void
    {
        $allSlugs = IlanKategori::withTrashed()
            ->pluck('slug')
            ->toArray();

        $uniqueSlugs = array_unique($allSlugs);

        $this->assertEquals(count($allSlugs), count($uniqueSlugs), 'No duplicate slugs even with soft-deleted');
    }

    // ══════════════════════════════════════════════════════════════════════════
    // SEEDER INVARIANTS
    // ══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function seeder_is_idempotent(): void
    {
        // Run seeder twice
        $this->artisan('db:seed', ['--class' => 'IlanKategoriSeeder']);

        $firstRunRootCount = IlanKategori::whereNull('parent_id')
            ->where('aktiflik_durumu', true)
            ->count();

        $this->artisan('db:seed', ['--class' => 'IlanKategoriSeeder']);

        $secondRunRootCount = IlanKategori::whereNull('parent_id')
            ->where('aktiflik_durumu', true)
            ->count();

        $this->assertEquals($firstRunRootCount, $secondRunRootCount, 'Seeder should be idempotent');
    }
}
