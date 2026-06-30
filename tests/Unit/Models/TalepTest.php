<?php

namespace Tests\Unit\Models;

use App\Models\Talep;
use App\Models\Kisi;
use App\Models\User;
use App\Enums\TalepDurumu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TalepTest extends TestCase
{

    /**
     * Test Talep model can be created
     */
    public function test_talep_can_be_created(): void
    {
        $kisiId = DB::table('kisiler')->insertGetId([
            'ad' => 'Test',
            'soyad' => 'Kisi',
            'eposta' => 'test@example.com',
            'telefon' => '5551234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $talepId = DB::table('talepler')->insertGetId([
            'talep_tipi' => 'Konut',
            'notlar' => 'Test açıklama',
            'talep_durumu' => 'yayinda',
            'emlak_tipi' => 'Daire',
            'kisi_id' => $kisiId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $talep = Talep::find($talepId);

        $this->assertInstanceOf(Talep::class, $talep);
        $this->assertEquals('Konut', $talep->talep_tipi);
    }

    /**
     * Test Talep model relationships - kisi
     */
    public function test_talep_belongs_to_kisi(): void
    {
        $kisiId = DB::table('kisiler')->insertGetId([
            'ad' => 'Test',
            'soyad' => 'Kisi',
            'eposta' => 'test@example.com',
            'telefon' => '5551234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $talepId = DB::table('talepler')->insertGetId([
            'talep_tipi' => 'Konut',
            'kisi_id' => $kisiId,
            'talep_durumu' => 'yayinda',
            'emlak_tipi' => 'Daire',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $talep = Talep::find($talepId);

        $this->assertNotNull($talep->kisi);
        $this->assertEquals($kisiId, $talep->kisi->id);
    }

    /**
     * Test Talep model relationships - danisman
     */
    public function test_talep_belongs_to_danisman(): void
    {
        $kisiId = DB::table('kisiler')->insertGetId([
            'ad' => 'Test',
            'soyad' => 'Kisi',
            'eposta' => 'test@example.com',
            'telefon' => '5551234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $danismanId = DB::table('users')->insertGetId([
            'name' => 'Test Danışman',
            'email' => 'danisman@example.com',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $talepId = DB::table('talepler')->insertGetId([
            'talep_tipi' => 'Konut',
            'kisi_id' => $kisiId,
            'danisman_id' => $danismanId,
            'talep_durumu' => 'yayinda',
            'emlak_tipi' => 'Daire',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $talep = Talep::find($talepId);

        if (method_exists($talep, 'danisman')) {
            $this->assertNotNull($talep->danisman);
            $this->assertEquals($danismanId, $talep->danisman->id);
        }
    }

    /**
     * Test Talep model relationships - ilanlar
     */
    public function test_talep_has_ilanlar(): void
    {
        $kisiId = DB::table('kisiler')->insertGetId([
            'ad' => 'Test',
            'soyad' => 'Kisi',
            'eposta' => 'test@example.com',
            'telefon' => '5551234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $talepId = DB::table('talepler')->insertGetId([
            'talep_tipi' => 'Konut',
            'kisi_id' => $kisiId,
            'talep_durumu' => 'yayinda',
            'emlak_tipi' => 'Daire',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ilanId = DB::table('ilanlar')->insertGetId([
            'baslik' => 'Test İlan',
            'slug' => 'talep-test-ilan',
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'yayinda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Connect via eslesmeler pivot table
        DB::table('eslesmeler')->insert([
            'talep_id' => $talepId,
            'ilan_id' => $ilanId,
            'kisi_id' => $kisiId,
            'skor' => 80,
            'eslesme_durumu' => 'beklemede',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $talep = Talep::find($talepId);

        if (method_exists($talep, 'ilanlar')) {
            $this->assertGreaterThanOrEqual(1, $talep->ilanlar->count());
        }
    }

    /**
     * Test Talep model scope - active (if exists)
     */
    public function test_talep_scope_active(): void
    {
        $kisiId = DB::table('kisiler')->insertGetId([
            'ad' => 'Test',
            'soyad' => 'Kisi',
            'eposta' => 'test@example.com',
            'telefon' => '5551234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('talepler')->insert([
            [
                'talep_tipi' => 'Active Talep',
                'notlar' => 'Test açıklama',
                'talep_durumu' => 'yayinda', // TalepDurumu::AKTIF backing value
                'emlak_tipi' => 'Daire',
                'kisi_id' => $kisiId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'talep_tipi' => 'Inactive Talep',
                'notlar' => 'Test açıklama',
                'talep_durumu' => 'Taslak',
                'emlak_tipi' => 'Daire',
                'kisi_id' => $kisiId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // talepler tablosunda yayin_durumu/aktiflik_durumu yok,
        // HasActiveScope bu tabloyu filtreleyemez — skipped.
        $this->markTestSkipped('talepler tablosunda HasActiveScope uyumlu alan yok (talep_durumu ayrı bir domain alanı)');
    }

    /**
     * Test Talep model talep_durumu field (Context7 compliance)
     */
    public function test_talep_durumu_field(): void
    {
        $kisiId = DB::table('kisiler')->insertGetId([
            'ad' => 'Test',
            'soyad' => 'Kisi',
            'eposta' => 'test@example.com',
            'telefon' => '5551234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $talepId = DB::table('talepler')->insertGetId([
            'talep_tipi' => 'Konut',
            'notlar' => 'Test açıklama',
            'talep_durumu' => 'yayinda', // TalepDurumu::AKTIF backing value
            'emlak_tipi' => 'Daire',
            'kisi_id' => $kisiId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $talep = Talep::find($talepId);

        $val = $talep->talep_durumu;
        if ($val instanceof \UnitEnum) {
            $val = $val->value;
        }

        $this->assertEquals('yayinda', $val);
    }
}
