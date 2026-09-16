<?php

namespace Tests\Feature\CRM;

use App\Application\CRM\Services\MatchDemandsForListingUseCase;
use App\Domain\CRM\Services\DemandMatchingService;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Ilce;
use App\Models\Il;
use App\Models\Mahalle;
use App\Models\Role;
use App\Models\Talep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DemandMatchingTenantIsolationTest
 *
 * Dedicated proof that Tenant A's listings NEVER match Tenant B's demands under any condition.
 */
class DemandMatchingTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Il $il;
    private Ilce $ilce;
    private Mahalle $mahalle;
    private IlanKategori $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        $this->il = Il::firstOrCreate(
            ['plaka_kodu' => '48'],
            ['il_adi' => 'Muğla', 'aktiflik_durumu' => 1]
        );

        $this->ilce = Ilce::firstOrCreate(
            ['il_id' => $this->il->id, 'ilce_adi' => 'Bodrum'],
            ['aktiflik_durumu' => 1]
        );

        $this->mahalle = Mahalle::firstOrCreate(
            ['ilce_id' => $this->ilce->id, 'mahalle_adi' => 'Yalıkavak'],
            ['aktiflik_durumu' => 1]
        );

        $this->kategori = IlanKategori::firstOrCreate(
            ['slug' => 'konut'],
            ['name' => 'Konut', 'aktiflik_durumu' => 1]
        );
    }

    public function test_tenant_a_listing_never_matches_tenant_b_demand(): void
    {
        $tenantA = 'tenant-alpha-001';
        $tenantB = 'tenant-beta-002';

        $role = Role::firstOrCreate(['name' => 'danisman'], ['guard_name' => 'web']);

        $userA = new User([
            'name'      => 'Danışman Alpha',
            'email'     => 'alpha@yalihan.test',
            'password'  => bcrypt('secret'),
            'tenant_id' => $tenantA,
        ]);
        $userA->role_id = $role->id;
        $userA->save();

        $userB = new User([
            'name'      => 'Danışman Beta',
            'email'     => 'beta@yalihan.test',
            'password'  => bcrypt('secret'),
            'tenant_id' => $tenantB,
        ]);
        $userB->role_id = $role->id;
        $userB->save();

        // 1. Tenant A's Listing (Bodrum Yalıkavak Villa, 15M TL, Satılık)
        $ilanA = Ilan::create([
            'tenant_id'       => $tenantA,
            'user_id'         => $userA->id,
            'baslik'          => 'Lüks Yalıkavak Villa',
            'slug'            => 'luks-yalikavak-villa-tenant-a',
            'fiyat'           => 15000000,
            'para_birimi'     => 'TRY',
            'tip'             => 'Satılık',
            'yayin_tipi_id'   => 1,
            'kategori_id'     => $this->kategori->id,
            'alt_kategori_id' => $this->kategori->id,
            'il_id'           => $this->il->id,
            'ilce_id'         => $this->ilce->id,
            'mahalle_id'      => $this->mahalle->id,
            'yayin_durumu'    => 'yayinda',
            'aktiflik_durumu' => 1,
        ]);

        $kisiA = \App\Models\Kisi::create([
            'ad'        => 'Müşteri',
            'soyad'     => 'Alpha',
            'kisi_tipi' => 'lead',
        ]);

        $kisiB = \App\Models\Kisi::create([
            'ad'        => 'Müşteri',
            'soyad'     => 'Beta',
            'kisi_tipi' => 'lead',
        ]);

        // 2. Tenant A's Demand (Perfect Match for Listing A)
        $talepA = Talep::create([
            'tenant_id'       => $tenantA,
            'danisman_id'     => $userA->id,
            'kisi_id'         => $kisiA->id,
            'baslik'          => 'Yalıkavak Lüks Villa Talebi (Tenant A)',
            'talep_tipi'      => 'Satılık',
            'talep_durumu'    => 'yayinda',
            'kategori_id'     => $this->kategori->id,
            'alt_kategori_id' => $this->kategori->id,
            'il_id'           => $this->il->id,
            'ilce_id'         => $this->ilce->id,
            'mahalle_id'      => $this->mahalle->id,
            'min_fiyat'       => 12000000,
            'max_fiyat'       => 18000000,
            'oncelik'         => 'Yuksek',
        ]);

        // 3. Tenant B's Demand (Identical criteria, but belonging to Tenant B)
        $talepB = Talep::create([
            'tenant_id'       => $tenantB,
            'danisman_id'     => $userB->id,
            'kisi_id'         => $kisiB->id,
            'baslik'          => 'Yalıkavak Lüks Villa Talebi (Tenant B - Cross Tenant)',
            'talep_tipi'      => 'Satılık',
            'talep_durumu'    => 'yayinda',
            'kategori_id'     => $this->kategori->id,
            'alt_kategori_id' => $this->kategori->id,
            'il_id'           => $this->il->id,
            'ilce_id'         => $this->ilce->id,
            'mahalle_id'      => $this->mahalle->id,
            'min_fiyat'       => 12000000,
            'max_fiyat'       => 18000000,
            'oncelik'         => 'Yuksek',
        ]);

        // 4. Execute Matching Use Case for Tenant A's listing
        $useCase = new MatchDemandsForListingUseCase();
        $matches = $useCase->execute($ilanA, dispatchEvents: false);

        // 5. Assertions: Tenant A matches, Tenant B is NEVER returned
        $this->assertNotEmpty($matches, 'Tenant A should find matching demand');
        $this->assertTrue($matches->contains('talepId', $talepA->id), 'Matches must contain Tenant A demand');
        $this->assertFalse($matches->contains('talepId', $talepB->id), 'Matches must NEVER contain Tenant B demand');
        $this->assertCount(1, $matches, 'Total matches must equal exactly 1 (Tenant A only)');
    }
}
