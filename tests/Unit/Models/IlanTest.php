<?php

namespace Tests\Unit\Models;

use App\Enums\IlanDurumu;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\User;
use Tests\TestCase;

class IlanTest extends TestCase
{

    /**
     * Test Ilan model can be created
     */
    public function test_ilan_can_be_created(): void
    {
        $ilan = Ilan::factory()->create([
            'baslik' => 'Test İlan',
            'slug' => 'test-ilan',
            'fiyat' => 100000,
            'para_birimi' => 'TL',
            'yayin_durumu' => 'yayinda',
        ]);

        $this->assertInstanceOf(Ilan::class, $ilan);
        $this->assertEquals('Test İlan', $ilan->baslik);
        $this->assertEquals(100000, $ilan->fiyat);
        $this->assertEquals('TL', $ilan->para_birimi);
        $this->assertEquals(IlanDurumu::YAYINDA, $ilan->yayin_durumu);
    }

    /**
     * Test Ilan model relationships - danisman
     */
    public function test_ilan_belongs_to_danisman(): void
    {
        $danisman = User::factory()->create(['name' => 'Test Danışman']);

        $ilan = Ilan::factory()->create([
            'baslik' => 'Test İlan',
            'slug' => 'test-ilan-danisman',
            'fiyat' => 100000,
            'para_birimi' => 'TL',
            'yayin_durumu' => 'yayinda',
            'danisman_id' => $danisman->id,
        ]);

        $this->assertInstanceOf(User::class, $ilan->danisman);
        $this->assertEquals($danisman->id, $ilan->danisman->id);
    }

    /**
     * Test Ilan model relationships - kategori
     */
    public function test_ilan_belongs_to_kategori(): void
    {
        $kategori = IlanKategori::factory()->create([
            'name' => 'Test Kategori',
            'slug' => 'test-kategori',
        ]);

        $ilan = Ilan::factory()->create([
            'baslik' => 'Test İlan',
            'slug' => 'test-ilan-kategori',
            'fiyat' => 100000,
            'para_birimi' => 'TL',
            'yayin_durumu' => 'yayinda',
            'alt_kategori_id' => $kategori->id,
        ]);

        $this->assertInstanceOf(IlanKategori::class, $ilan->altKategori);
        $this->assertEquals($kategori->id, $ilan->altKategori->id);
    }

    /**
     * Test Ilan model scope - active
     */
    public function test_ilan_scope_active(): void
    {
        Ilan::factory()->create(['baslik' => 'Aktif İlan', 'slug' => 'aktif-ilan', 'fiyat' => 100000, 'para_birimi' => 'TL', 'yayin_durumu' => 'yayinda']);
        Ilan::factory()->create(['baslik' => 'Pasif İlan', 'slug' => 'pasif-ilan', 'fiyat' => 200000, 'para_birimi' => 'TL', 'yayin_durumu' => 'pasif']);

        $activeIlans = Ilan::active()->get();

        $this->assertCount(1, $activeIlans);
        $this->assertSame('aktif-ilan', $activeIlans->sole()->slug);
        $this->assertTrue($activeIlans->every(fn ($ilan) => $ilan->yayin_durumu === IlanDurumu::YAYINDA));
    }

    /**
     * Test Ilan model scope - pending
     */
    public function test_ilan_scope_pending(): void
    {
        Ilan::factory()->create(['baslik' => 'Beklemede İlan', 'slug' => 'beklemede-ilan', 'fiyat' => 100000, 'para_birimi' => 'TL', 'yayin_durumu' => 'beklemede']);
        Ilan::factory()->create(['baslik' => 'Aktif İlan', 'slug' => 'aktif-ilan-2', 'fiyat' => 200000, 'para_birimi' => 'TL', 'yayin_durumu' => 'yayinda']);

        $pendingIlans = Ilan::pending()->get();

        $this->assertCount(1, $pendingIlans);
        $this->assertSame('beklemede-ilan', $pendingIlans->sole()->slug);
        $this->assertTrue($pendingIlans->every(fn ($ilan) => $ilan->yayin_durumu === IlanDurumu::BEKLEMEDE));
    }

    /**
     * Test Ilan model Filterable trait - priceRange
     */
    public function test_ilan_price_range_filter(): void
    {
        Ilan::factory()->create(['baslik' => 'İlan 1', 'slug' => 'ilan-1', 'fiyat' => 100000, 'para_birimi' => 'TL', 'yayin_durumu' => 'yayinda']);
        Ilan::factory()->create(['baslik' => 'İlan 2', 'slug' => 'ilan-2', 'fiyat' => 200000, 'para_birimi' => 'TL', 'yayin_durumu' => 'yayinda']);
        Ilan::factory()->create(['baslik' => 'İlan 3', 'slug' => 'ilan-3', 'fiyat' => 300000, 'para_birimi' => 'TL', 'yayin_durumu' => 'yayinda']);

        $results = Ilan::query()
            ->priceRange(150000, 250000, 'fiyat')
            ->get();

        $this->assertCount(1, $results);
        $this->assertSame('ilan-2', $results->sole()->slug);
        $this->assertTrue($results->every(fn ($ilan) => $ilan->fiyat >= 150000 && $ilan->fiyat <= 250000));
    }

    /**
     * Test Ilan model Filterable trait - search
     */
    public function test_ilan_search_filter(): void
    {
        Ilan::factory()->create(['baslik' => 'Lüks Villa', 'slug' => 'luks-villa', 'fiyat' => 100000, 'para_birimi' => 'TL', 'yayin_durumu' => 'yayinda']);
        Ilan::factory()->create(['baslik' => 'Modern Daire', 'slug' => 'modern-daire', 'fiyat' => 200000, 'para_birimi' => 'TL', 'yayin_durumu' => 'yayinda']);

        $results = Ilan::query()
            ->search('Villa')
            ->get();

        $this->assertCount(1, $results);
        $this->assertSame('luks-villa', $results->sole()->slug);
        $this->assertStringContainsString('Villa', $results->sole()->baslik);
    }

    /**
     * Test Ilan model Filterable trait - byAktiflikDurumu
     */
    public function test_ilan_status_filter(): void
    {
        Ilan::factory()->create(['baslik' => 'Aktif İlan', 'slug' => 'aktif-ilan-3', 'fiyat' => 100000, 'para_birimi' => 'TL', 'yayin_durumu' => 'yayinda']);
        Ilan::factory()->create(['baslik' => 'Pasif İlan', 'slug' => 'pasif-ilan-2', 'fiyat' => 200000, 'para_birimi' => 'TL', 'yayin_durumu' => 'pasif']);

        $results = Ilan::query()
            ->byYayinDurumu('yayinda')
            ->get();

        $this->assertCount(1, $results);
        $this->assertSame('aktif-ilan-3', $results->sole()->slug);
        $this->assertTrue($results->every(fn ($ilan) => $ilan->yayin_durumu === IlanDurumu::YAYINDA));
    }

    /**
     * Test Ilan model SoftDeletes trait
     */
    public function test_ilan_soft_deletes(): void
    {
        $ilan = Ilan::factory()->create([
            'baslik' => 'Test İlan',
            'slug' => 'test-ilan-soft-delete',
            'fiyat' => 100000,
            'para_birimi' => 'TL',
            'yayin_durumu' => 'yayinda',
        ]);

        $ilanId = $ilan->id;
        $ilan->delete();

        $this->assertSoftDeleted('ilanlar', ['id' => $ilanId]);
        $this->assertNull(Ilan::find($ilanId));
        $this->assertNotNull(Ilan::withTrashed()->find($ilanId));
    }
}
