<?php

namespace Tests\Feature\Wizard;

use Tests\TestCase;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\User;

/**
 * 🧪 Wizard Step 1: Critical Tests (P0)
 *
 * Sorunlar:
 * 1. "Alt kategori bulunamadı" - kategori seçince boş döner
 * 2. "Yayın tipi yok" - alt kategori seçince boş döner
 * 3. SQLSTATE slug hatası
 *
 * SSOT Endpoints (Context7 Compliant):
 * - GET /api/v1/categories/sub/{parentId}
 * - GET /api/v1/categories/publication-types/{categoryId}
 */
class WizardStep1TemplateDataTest extends TestCase
{

    private User $admin;
    private IlanKategori $konutKategori;
    private IlanKategori $arsaKategori;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'test@yalihanai.com',
            'role_id' => 1,
            'aktiflik_durumu' => true,
        ]);

        // Ana kategoriler (seviye=0, parent_id=null)
        $this->konutKategori = IlanKategori::create([
            'name' => 'Konut',
            'slug' => 'konut',
            'parent_id' => null,
            'seviye' => 0,
            'aktiflik_durumu' => true,
            'display_order' => 1,
        ]);

        $this->arsaKategori = IlanKategori::create([
            'name' => 'Arsa & Arazi',
            'slug' => 'arsa', // ✅ Corrected slug for Policy Matrix match
            'parent_id' => null,
            'seviye' => 0,
            'aktiflik_durumu' => true,
            'display_order' => 2,
        ]);

        // Alt kategoriler (seviye=1, parent_id=ana_kategori_id)
        IlanKategori::create([
            'name' => 'Daire',
            'slug' => 'daire',
            'parent_id' => $this->konutKategori->id,
            'seviye' => 1,
            'aktiflik_durumu' => true,
            'display_order' => 1,
        ]);

        IlanKategori::create([
            'name' => 'Villa',
            'slug' => 'villa',
            'parent_id' => $this->konutKategori->id,
            'seviye' => 1,
            'aktiflik_durumu' => true,
            'display_order' => 2,
        ]);

        IlanKategori::create([
            'name' => 'İmar Arsası',
            'slug' => 'imar-arsasi',
            'parent_id' => $this->arsaKategori->id,
            'seviye' => 1,
            'aktiflik_durumu' => true,
            'display_order' => 1,
        ]);

        // Yayın tipleri (YayinTipiSablonu - Global Master Templates)
        YayinTipiSablonu::firstOrCreate(
            ['slug' => 'satilik'],
            ['ad' => 'Satılık', 'aktiflik_durumu' => true, 'display_order' => 1]
        );

        YayinTipiSablonu::firstOrCreate(
            ['slug' => 'kiralik'],
            ['ad' => 'Kiralık', 'aktiflik_durumu' => true, 'display_order' => 2]
        );

        $this->actingAs($this->admin);
    }

    /** @test */
    public function it_loads_subcategories_for_konut_category()
    {
        $this->withoutExceptionHandling();
        $response = $this->getJson("/api/v1/categories/sub/{$this->konutKategori->id}");

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $subcategories = $response->json('data.subcategories');
        $this->assertIsArray($subcategories);
        $this->assertGreaterThanOrEqual(2, count($subcategories), 'Expected 2+ subcategories');

        $slugs = collect($subcategories)->pluck('slug')->toArray();
        $this->assertContains('daire', $slugs);
        $this->assertContains('villa', $slugs);
    }

    /** @test */
    public function it_loads_subcategories_for_arsa_category()
    {
        $response = $this->getJson("/api/v1/categories/sub/{$this->arsaKategori->id}");

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $subcategories = $response->json('data.subcategories');
        $this->assertIsArray($subcategories);
        $this->assertGreaterThanOrEqual(1, count($subcategories), 'Expected 1+ subcategory');

        $slugs = collect($subcategories)->pluck('slug')->toArray();
        $this->assertContains('imar-arsasi', $slugs);
    }

    /** @test */
    public function it_loads_publication_types_for_konut_category()
    {
        $this->withoutExceptionHandling();
        $response = $this->getJson("/api/v1/categories/publication-types/{$this->konutKategori->id}");

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $types = $response->json('data.types');
        $this->assertIsArray($types);
        $this->assertGreaterThanOrEqual(2, count($types), 'Expected 2+ publication types');

        // API returns 'name' field, not 'yayin_tipi'
        $names = collect($types)->pluck('name')->toArray();
        $this->assertContains('Satılık', $names);
        $this->assertContains('Kiralık', $names);
    }

    /** @test */
    public function it_loads_publication_types_for_arsa_category()
    {
        $response = $this->getJson("/api/v1/categories/publication-types/{$this->arsaKategori->id}");

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $types = $response->json('data.types');
        $this->assertIsArray($types);
        $this->assertGreaterThanOrEqual(1, count($types), 'Expected 1+ publication type');

        // API returns 'name' field, not 'yayin_tipi'
        $names = collect($types)->pluck('name')->toArray();
        $this->assertContains('Satılık', $names);
    }
}
