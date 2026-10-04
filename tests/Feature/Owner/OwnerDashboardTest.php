<?php

namespace Tests\Feature\Owner;

use App\Enums\IlanDurumu;
use App\Models\Il;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\SaaS\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * OwnerDashboardTest
 *
 * Mülk Sahibi (Owner) Dashboard feature testleri.
 *
 * Doğrulanan kurallar:
 *  - Toplam ve aktif ilan sayıları doğruluğu (whereYayinda scope)
 *  - Taslak ve pasif ilanların aktif sayıya dahil edilmemesi
 *  - Kullanıcı / Owner izolasyonu (başka mülk sahibinin ilanları sayılmaz)
 *  - Misafir (guest) yönlendirme güvenliği
 *
 * @group owner
 * @group dashboard
 * @group sab
 */
class OwnerDashboardTest extends TestCase
{
    private User $owner;
    private User $otherOwner;
    private IlanKategori $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('owner', 'web');

        Tenant::firstOrCreate(['id' => 1], ['name' => 'Tenant 1', 'domain' => 'tenant-1.local']);

        $this->owner = User::factory()->owner()->create([
            'tenant_id' => 1,
            'email'     => 'owner@example.com',
        ]);

        $this->otherOwner = User::factory()->owner()->create([
            'tenant_id' => 1,
            'email'     => 'other-owner@example.com',
        ]);

        $this->kategori = IlanKategori::factory()->create(['parent_id' => null]);
        Il::firstOrCreate(['id' => 1], ['il_adi' => 'Muğla', 'plaka_kodu' => '48']);
    }

    /** @test */
    public function guest_is_redirected_to_owner_login(): void
    {
        $response = $this->get(route('owner.dashboard'));

        $response->assertRedirect(route('owner.login'));
    }

    /** @test */
    public function user_without_owner_role_cannot_access_owner_dashboard(): void
    {
        $regularUser = User::factory()->create([
            'tenant_id' => 1,
        ]);

        $response = $this->actingAs($regularUser)->get(route('owner.dashboard'));

        $response->assertStatus(403);
    }

    /** @test */
    public function owner_sees_dashboard_with_correct_total_and_active_counts(): void
    {
        // 2 adet yayında (aktif) ilan
        Ilan::factory()->count(2)->create([
            'user_id'         => $this->owner->id,
            'yayin_durumu'    => IlanDurumu::YAYINDA->value,
            'ana_kategori_id' => $this->kategori->id,
            'il_id'           => 1,
        ]);

        // 1 adet taslak ilan
        Ilan::factory()->create([
            'user_id'         => $this->owner->id,
            'yayin_durumu'    => IlanDurumu::TASLAK->value,
            'ana_kategori_id' => $this->kategori->id,
            'il_id'           => 1,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.dashboard'));

        $response->assertOk();
        $response->assertViewIs('owner.dashboard');
        $response->assertViewHas('ilanSayisi', 3);
        $response->assertViewHas('aktifIlanSayisi', 2);
    }

    /** @test */
    public function published_listing_increments_active_count(): void
    {
        // Başlangıçta 0 aktif ilan
        $responseBefore = $this->actingAs($this->owner)->get(route('owner.dashboard'));
        $responseBefore->assertOk();
        $responseBefore->assertViewHas('aktifIlanSayisi', 0);
        $responseBefore->assertViewHas('ilanSayisi', 0);

        // Yayında ilan oluştur
        Ilan::factory()->create([
            'user_id'         => $this->owner->id,
            'yayin_durumu'    => 'yayinda',
            'ana_kategori_id' => $this->kategori->id,
            'il_id'           => 1,
        ]);

        $responseAfter = $this->actingAs($this->owner)->get(route('owner.dashboard'));
        $responseAfter->assertOk();
        $responseAfter->assertViewHas('aktifIlanSayisi', 1);
        $responseAfter->assertViewHas('ilanSayisi', 1);
    }

    /** @test */
    public function draft_and_passive_listings_do_not_increment_active_count(): void
    {
        // 1 taslak, 1 pasif ilan oluştur
        Ilan::factory()->create([
            'user_id'         => $this->owner->id,
            'yayin_durumu'    => IlanDurumu::TASLAK->value,
            'ana_kategori_id' => $this->kategori->id,
            'il_id'           => 1,
        ]);

        Ilan::factory()->create([
            'user_id'         => $this->owner->id,
            'yayin_durumu'    => IlanDurumu::PASIF->value,
            'ana_kategori_id' => $this->kategori->id,
            'il_id'           => 1,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.dashboard'));

        $response->assertOk();
        $response->assertViewHas('ilanSayisi', 2);
        $response->assertViewHas('aktifIlanSayisi', 0);
    }

    /** @test */
    public function another_owners_published_listing_does_not_increment_this_owners_counts(): void
    {
        // Diğer owner'a ait 2 yayında ilan
        Ilan::factory()->count(2)->create([
            'user_id'         => $this->otherOwner->id,
            'yayin_durumu'    => IlanDurumu::YAYINDA->value,
            'ana_kategori_id' => $this->kategori->id,
            'il_id'           => 1,
        ]);

        // Bu owner'a ait 1 yayında ilan
        Ilan::factory()->create([
            'user_id'         => $this->owner->id,
            'yayin_durumu'    => IlanDurumu::YAYINDA->value,
            'ana_kategori_id' => $this->kategori->id,
            'il_id'           => 1,
        ]);

        // Bu owner kendi sayılarını görmeli (toplam 1, aktif 1)
        $responseOwner = $this->actingAs($this->owner)->get(route('owner.dashboard'));
        $responseOwner->assertOk();
        $responseOwner->assertViewHas('ilanSayisi', 1);
        $responseOwner->assertViewHas('aktifIlanSayisi', 1);

        // Diğer owner kendi sayılarını görmeli (toplam 2, aktif 2)
        $responseOther = $this->actingAs($this->otherOwner)->get(route('owner.dashboard'));
        $responseOther->assertOk();
        $responseOther->assertViewHas('ilanSayisi', 2);
        $responseOther->assertViewHas('aktifIlanSayisi', 2);
    }
}
