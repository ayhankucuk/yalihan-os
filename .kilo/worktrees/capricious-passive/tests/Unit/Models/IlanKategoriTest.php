<?php

namespace Tests\Unit\Models;

use App\Models\IlanKategori;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IlanKategoriTest extends TestCase
{

    /**
     * Test IlanKategori model can be created
     */
    public function test_ilan_kategori_can_be_created(): void
    {
        $kategoriId = DB::table('ilan_kategorileri')->insertGetId([
            'name' => 'Test Kategori',
            'slug' => 'test-kategori',
            'aktiflik_durumu' => 1,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kategori = IlanKategori::find($kategoriId);

        $this->assertInstanceOf(IlanKategori::class, $kategori);
        $this->assertEquals('Test Kategori', $kategori->name);
        $this->assertEquals('test-kategori', $kategori->slug);
        $this->assertEquals(1, $kategori->aktiflik_durumu?->value ?? $kategori->aktiflik_durumu);
        $this->assertEquals(0, $kategori->display_order);
    }

    /**
     * Test IlanKategori model relationships - parent
     */
    public function test_ilan_kategori_belongs_to_parent(): void
    {
        // Create parent category
        $parentId = DB::table('ilan_kategorileri')->insertGetId([
            'name' => 'Ana Kategori',
            'slug' => 'ana-kategori',
            'aktiflik_durumu' => 1,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create child category
        $childId = DB::table('ilan_kategorileri')->insertGetId([
            'name' => 'Alt Kategori',
            'slug' => 'alt-kategori',
            'aktiflik_durumu' => 1,
            'parent_id' => $parentId,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $child = IlanKategori::find($childId);

        $this->assertInstanceOf(IlanKategori::class, $child->parent);
        $this->assertEquals($parentId, $child->parent->id);
    }

    /**
     * Test IlanKategori model relationships - children
     */
    public function test_ilan_kategori_has_children(): void
    {
        // Create parent category
        $parentId = DB::table('ilan_kategorileri')->insertGetId([
            'name' => 'Ana Kategori',
            'slug' => 'ana-kategori',
            'aktiflik_durumu' => 1,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create child categories
        DB::table('ilan_kategorileri')->insert([
            [
                'name' => 'Alt Kategori 1',
                'slug' => 'alt-kategori-1',
                'aktiflik_durumu' => 1,
                'parent_id' => $parentId,
                'display_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Alt Kategori 2',
                'slug' => 'alt-kategori-2',
                'aktiflik_durumu' => 1,
                'parent_id' => $parentId,
                'display_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $parent = IlanKategori::find($parentId);

        $this->assertGreaterThanOrEqual(2, $parent->children->count());
        $this->assertTrue($parent->children->every(fn ($child) => $child->parent_id === $parentId));
    }

    /**
     * Test IlanKategori model relationships - ilanlar
     */
    public function test_ilan_kategori_has_ilanlar(): void
    {
        // Create category
        $kategoriId = DB::table('ilan_kategorileri')->insertGetId([
            'name' => 'Test Kategori',
            'slug' => 'test-kategori',
            'aktiflik_durumu' => 1,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create listings
        DB::table('ilanlar')->insert([
            [
                'baslik' => 'İlan 1',
                'slug' => 'ilan-1',
                'fiyat' => 100000,
                'para_birimi' => 'TL',
                'yayin_durumu' => 'yayinda',
                'kategori_id' => $kategoriId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'baslik' => 'İlan 2',
                'slug' => 'ilan-2',
                'fiyat' => 200000,
                'para_birimi' => 'TL',
                'yayin_durumu' => 'yayinda',
                'kategori_id' => $kategoriId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $kategori = IlanKategori::find($kategoriId);

        $this->assertGreaterThanOrEqual(2, $kategori->ilanlar->count());
    }

    /**
     * Test IlanKategori model scope - active
     */
    public function test_ilan_kategori_scope_active(): void
    {
        // Create test data
        DB::table('ilan_kategorileri')->insert([
            ['name' => 'Aktif Kategori', 'slug' => 'aktif-kategori', 'aktiflik_durumu' => 1, 'display_order' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pasif Kategori', 'slug' => 'pasif-kategori', 'aktiflik_durumu' => 0, 'display_order' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $activeKategoriler = IlanKategori::active()->get();

        $this->assertGreaterThanOrEqual(1, $activeKategoriler->count());
        $this->assertTrue($activeKategoriler->every(fn ($kategori) => (($kategori->aktiflik_durumu?->value ?? $kategori->aktiflik_durumu) == 1)));
    }

    /**
     * Test IlanKategori model scope - ordered (display_order)
     */
    public function test_ilan_kategori_scope_ordered(): void
    {
        // Create test data
        DB::table('ilan_kategorileri')->insert([
            ['name' => 'Kategori 3', 'slug' => 'kategori-3', 'aktiflik_durumu' => 1, 'display_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kategori 1', 'slug' => 'kategori-1', 'aktiflik_durumu' => 1, 'display_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kategori 2', 'slug' => 'kategori-2', 'aktiflik_durumu' => 1, 'display_order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $orderedKategoriler = IlanKategori::ordered()->get();

        $this->assertGreaterThanOrEqual(3, $orderedKategoriler->count());
        // Check if ordered by display_order
        $displayOrders = $orderedKategoriler->pluck('display_order')->toArray();
        $sortedDisplayOrders = $displayOrders;
        sort($sortedDisplayOrders);
        $this->assertEquals($sortedDisplayOrders, $displayOrders);
    }

    /**
     * Test IlanKategori model display_order field (Context7 compliance)
     */
    public function test_ilan_kategori_display_order_field(): void
    {
        // Create test data
        $kategoriId = DB::table('ilan_kategorileri')->insertGetId([
            'name' => 'Test Kategori',
            'slug' => 'test-kategori',
            'aktiflik_durumu' => 1,
            'display_order' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kategori = IlanKategori::find($kategoriId);

        $this->assertEquals(5, $kategori->display_order);
        $this->assertArrayHasKey('display_order', $kategori->getAttributes());
    }

    /**
     * Test IlanKategori model SoftDeletes trait
     */
    public function test_ilan_kategori_soft_deletes(): void
    {
        $kategoriId = DB::table('ilan_kategorileri')->insertGetId([
            'name' => 'Test Kategori',
            'slug' => 'test-kategori',
            'aktiflik_durumu' => 1,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kategori = IlanKategori::find($kategoriId);
        $kategori->delete();

        $this->assertSoftDeleted('ilan_kategorileri', ['id' => $kategoriId]);
        $this->assertNull(IlanKategori::find($kategoriId));
        $this->assertNotNull(IlanKategori::withTrashed()->find($kategoriId));
    }
}
