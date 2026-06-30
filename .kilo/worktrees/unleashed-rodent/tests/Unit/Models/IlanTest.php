<?php

namespace Tests\Unit\Models;

use App\Enums\IlanDurumu;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Kisi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IlanTest extends TestCase
{

    /**
     * Test Ilan model can be created
     */
    public function test_ilan_can_be_created(): void
    {
        // Create test data using DB::table for simplicity
        $ilanId = DB::table('ilanlar')->insertGetId([
            'baslik' => 'Test İlan',
            'slug' => 'test-ilan',
            'fiyat' => 100000,
            'para_birimi' => 'TL',
            'yayin_durumu' => 'yayinda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ilan = Ilan::find($ilanId);

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
        // Create test data using DB::table
        $danismanId = DB::table('users')->insertGetId([
            'name' => 'Test Danışman',
            'email' => 'danisman@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ilanId = DB::table('ilanlar')->insertGetId([
            'baslik' => 'Test İlan',
            'slug' => 'test-ilan-danisman',
            'fiyat' => 100000,
            'para_birimi' => 'TL',
            'yayin_durumu' => 'yayinda',
            'danisman_id' => $danismanId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ilan = Ilan::find($ilanId);

        $this->assertInstanceOf(User::class, $ilan->danisman);
        $this->assertEquals($danismanId, $ilan->danisman->id);
    }

    /**
     * Test Ilan model relationships - kategori
     */
    public function test_ilan_belongs_to_kategori(): void
    {
        // Create test data using DB::table
        $kategoriId = DB::table('ilan_kategorileri')->insertGetId([
            'name' => 'Test Kategori',
            'slug' => 'test-kategori',
            'seviye' => 0,
            'aktiflik_durumu' => true,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ilanId = DB::table('ilanlar')->insertGetId([
            'baslik' => 'Test İlan',
            'slug' => 'test-ilan-kategori',
            'fiyat' => 100000,
            'para_birimi' => 'TL',
            'yayin_durumu' => 'yayinda',
            'alt_kategori_id' => $kategoriId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ilan = Ilan::find($ilanId);

        $this->assertInstanceOf(IlanKategori::class, $ilan->altKategori);
        $this->assertEquals($kategoriId, $ilan->altKategori->id);
    }

    /**
     * Test Ilan model scope - active
     */
    public function test_ilan_scope_active(): void
    {
        // Create test data
        DB::table('ilanlar')->insert([
            ['baslik' => 'Aktif İlan', 'slug' => 'aktif-ilan', 'fiyat' => 100000, 'para_birimi' => 'TL', 'yayin_durumu' => 'yayinda', 'created_at' => now(), 'updated_at' => now()],
            ['baslik' => 'Pasif İlan', 'slug' => 'pasif-ilan', 'fiyat' => 200000, 'para_birimi' => 'TL', 'yayin_durumu' => 'pasif', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $activeIlans = Ilan::active()->get();

        $this->assertGreaterThanOrEqual(1, $activeIlans->count());
        $this->assertTrue($activeIlans->every(fn ($ilan) => $ilan->yayin_durumu === IlanDurumu::YAYINDA));
    }

    /**
     * Test Ilan model scope - pending
     */
    public function test_ilan_scope_pending(): void
    {
        // Create test data
        DB::table('ilanlar')->insert([
            ['baslik' => 'Beklemede İlan', 'slug' => 'beklemede-ilan', 'fiyat' => 100000, 'para_birimi' => 'TL', 'yayin_durumu' => 'beklemede', 'created_at' => now(), 'updated_at' => now()],
            ['baslik' => 'Aktif İlan', 'slug' => 'aktif-ilan-2', 'fiyat' => 200000, 'para_birimi' => 'TL', 'yayin_durumu' => 'yayinda', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $pendingIlans = Ilan::pending()->get();

        $this->assertGreaterThanOrEqual(1, $pendingIlans->count());
        $this->assertTrue($pendingIlans->every(fn ($ilan) => $ilan->yayin_durumu === IlanDurumu::BEKLEMEDE));
    }

    /**
     * Test Ilan model Filterable trait - priceRange
     */
    public function test_ilan_price_range_filter(): void
    {
        // Create test data
        DB::table('ilanlar')->insert([
            ['baslik' => 'İlan 1', 'slug' => 'ilan-1', 'fiyat' => 100000, 'para_birimi' => 'TL', 'yayin_durumu' => 'yayinda', 'created_at' => now(), 'updated_at' => now()],
            ['baslik' => 'İlan 2', 'slug' => 'ilan-2', 'fiyat' => 200000, 'para_birimi' => 'TL', 'yayin_durumu' => 'yayinda', 'created_at' => now(), 'updated_at' => now()],
            ['baslik' => 'İlan 3', 'slug' => 'ilan-3', 'fiyat' => 300000, 'para_birimi' => 'TL', 'yayin_durumu' => 'yayinda', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $results = Ilan::query()
            ->priceRange(150000, 250000, 'fiyat')
            ->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
        $this->assertTrue($results->every(fn ($ilan) => $ilan->fiyat >= 150000 && $ilan->fiyat <= 250000));
    }

    /**
     * Test Ilan model Filterable trait - search
     */
    public function test_ilan_search_filter(): void
    {
        // Create test data
        DB::table('ilanlar')->insert([
            ['baslik' => 'Lüks Villa', 'slug' => 'luks-villa', 'fiyat' => 100000, 'para_birimi' => 'TL', 'yayin_durumu' => 'yayinda', 'created_at' => now(), 'updated_at' => now()],
            ['baslik' => 'Modern Daire', 'slug' => 'modern-daire', 'fiyat' => 200000, 'para_birimi' => 'TL', 'yayin_durumu' => 'yayinda', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $results = Ilan::query()
            ->search('Villa')
            ->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
        $this->assertTrue($results->contains(fn ($ilan) => str_contains($ilan->baslik, 'Villa')));
    }

    /**
     * Test Ilan model Filterable trait - byAktiflikDurumu
     */
    public function test_ilan_status_filter(): void
    {
        // Create test data
        DB::table('ilanlar')->insert([
            ['baslik' => 'Aktif İlan', 'slug' => 'aktif-ilan-3', 'fiyat' => 100000, 'para_birimi' => 'TL', 'yayin_durumu' => 'yayinda', 'created_at' => now(), 'updated_at' => now()],
            ['baslik' => 'Pasif İlan', 'slug' => 'pasif-ilan-2', 'fiyat' => 200000, 'para_birimi' => 'TL', 'yayin_durumu' => 'pasif', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $results = Ilan::query()
            ->byYayinDurumu('yayinda')
            ->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
        $this->assertTrue($results->every(fn ($ilan) => $ilan->yayin_durumu === IlanDurumu::YAYINDA));
    }

    /**
     * Test Ilan model SoftDeletes trait
     */
    public function test_ilan_soft_deletes(): void
    {
        $ilanId = DB::table('ilanlar')->insertGetId([
            'baslik' => 'Test İlan',
            'slug' => 'test-ilan-soft-delete',
            'fiyat' => 100000,
            'para_birimi' => 'TL',
            'yayin_durumu' => 'yayinda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ilan = Ilan::find($ilanId);
        $ilan->delete();

        $this->assertSoftDeleted('ilanlar', ['id' => $ilanId]);
        $this->assertNull(Ilan::find($ilanId));
        $this->assertNotNull(Ilan::withTrashed()->find($ilanId));
    }
}
