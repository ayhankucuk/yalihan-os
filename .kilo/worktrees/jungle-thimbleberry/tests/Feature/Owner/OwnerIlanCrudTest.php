<?php

namespace Tests\Feature\Owner;

use App\Enums\IlanDurumu;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OwnerIlanCrudTest
 *
 * SAB v6.1.2 — Owner Portal CRUD Feature Testleri.
 *
 * Doğrulanacak SAB kuralları:
 *  - Kural 1 (Tenant Isolation): Owner başka owner'ın ilanına erişemez.
 *  - Kural 2 (Repository Authority): Write işlemleri repository üzerinden gitmelidir.
 *  - Kural 5 (Policy Enforcement): Yetkisiz erişim 403 döndürmelidir.
 *
 * @group owner
 * @group sab
 */
class OwnerIlanCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $otherOwner;
    private Ilan $ilan;
    private array $validPayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner      = User::factory()->owner()->create();
        $this->otherOwner = User::factory()->owner()->create();

        // Owner'a ait ilan: user_id = owner->id, yayin_durumu = taslak
        $this->ilan = Ilan::factory()->create([
            'user_id'      => $this->owner->id,
            'yayin_durumu' => IlanDurumu::TASLAK->value,
        ]);

        $kategori = IlanKategori::factory()->create(['parent_id' => null]);

        $this->validPayload = [
            'baslik'               => 'Güncellenen Test İlanı',
            'aciklama'             => 'Güncelleme testi açıklaması.',
            'fiyat'                => 1500000,
            'para_birimi'          => 'TRY',
            'fiyat_gosterim_modu'  => 'exact',
            'ana_kategori_id'      => $kategori->id,
            'il_id'                => 1,
        ];
    }

    // ─── index ───────────────────────────────────────────────────────────────

    /** @test */
    public function owner_can_list_own_ilanlar(): void
    {
        $this->actingAs($this->owner)
            ->get(route('owner.ilanlar.index'))
            ->assertOk();
    }

    /** @test */
    public function guest_cannot_access_owner_ilanlar(): void
    {
        $this->get(route('owner.ilanlar.index'))
            ->assertRedirect();
    }

    // ─── show ────────────────────────────────────────────────────────────────

    /** @test */
    public function owner_can_view_own_ilan(): void
    {
        $this->actingAs($this->owner)
            ->get(route('owner.ilanlar.show', $this->ilan->id))
            ->assertOk();
    }

    /**
     * SAB Kural 1 — Tenant Isolation: Cross-tenant okuma yasaktır.
     *
     * @test
     */
    public function owner_cannot_view_other_owners_ilan(): void
    {
        // otherOwner farklı bir kiracıdır — 404 döndürmeli (varlık sızdırılmaz)
        $this->actingAs($this->otherOwner)
            ->get(route('owner.ilanlar.show', $this->ilan->id))
            ->assertNotFound();
    }

    // ─── create / store ──────────────────────────────────────────────────────

    /** @test */
    public function owner_can_access_create_form(): void
    {
        $this->actingAs($this->owner)
            ->get(route('owner.ilanlar.create'))
            ->assertOk()
            ->assertViewIs('owner.ilanlar.create');
    }

    /** @test */
    public function owner_can_store_new_ilan_as_taslak(): void
    {
        $payload = array_merge($this->validPayload, [
            'baslik' => 'Yeni Mülküm',
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('owner.ilanlar.store'), $payload);

        $response->assertRedirect();

        // Yeni ilan mutlaka taslak başlamalı — SAB: owner yayin_durumu belirleyemez
        $this->assertDatabaseHas('ilanlar', [
            'baslik'       => 'Yeni Mülküm',
            'user_id'      => $this->owner->id,
            'yayin_durumu' => IlanDurumu::TASLAK->value,
        ]);
    }

    /** @test */
    public function store_rejects_invalid_payload(): void
    {
        $this->actingAs($this->owner)
            ->post(route('owner.ilanlar.store'), [])
            ->assertSessionHasErrors(['baslik', 'ana_kategori_id', 'il_id']);
    }

    /**
     * SAB Kural 1 — Owner store işlemi kendi user_id'sini set etmeli,
     * başkasının user_id'sini kabul etmemelidir.
     *
     * @test
     */
    public function store_always_assigns_authenticated_user_as_owner(): void
    {
        $payload = array_merge($this->validPayload, [
            'baslik'  => 'Sahte Sahip Denemesi',
            'user_id' => $this->otherOwner->id, // Manipülasyon girişimi
        ]);

        $this->actingAs($this->owner)
            ->post(route('owner.ilanlar.store'), $payload);

        // user_id mutlaka owner'ın kendi id'si olmalı
        $this->assertDatabaseHas('ilanlar', [
            'baslik'  => 'Sahte Sahip Denemesi',
            'user_id' => $this->owner->id,
        ]);
        $this->assertDatabaseMissing('ilanlar', [
            'baslik'  => 'Sahte Sahip Denemesi',
            'user_id' => $this->otherOwner->id,
        ]);
    }

    // ─── edit / update ───────────────────────────────────────────────────────

    /** @test */
    public function owner_can_access_edit_form_for_own_ilan(): void
    {
        $this->actingAs($this->owner)
            ->get(route('owner.ilanlar.edit', $this->ilan->id))
            ->assertOk()
            ->assertViewIs('owner.ilanlar.edit');
    }

    /**
     * SAB Kural 1 — Cross-tenant edit formu erişimi yasaktır.
     *
     * @test
     */
    public function owner_cannot_access_edit_form_of_other_owners_ilan(): void
    {
        $this->actingAs($this->otherOwner)
            ->get(route('owner.ilanlar.edit', $this->ilan->id))
            ->assertNotFound();
    }

    /** @test */
    public function owner_can_update_own_ilan(): void
    {
        $this->actingAs($this->owner)
            ->put(route('owner.ilanlar.update', $this->ilan->id), $this->validPayload)
            ->assertRedirect(route('owner.ilanlar.show', $this->ilan->id));

        $this->assertDatabaseHas('ilanlar', [
            'id'     => $this->ilan->id,
            'baslik' => 'Güncellenen Test İlanı',
        ]);
    }

    /**
     * SAB Kural 1 — Cross-tenant update yasaktır.
     *
     * @test
     */
    public function owner_cannot_update_other_owners_ilan(): void
    {
        $this->actingAs($this->otherOwner)
            ->put(route('owner.ilanlar.update', $this->ilan->id), $this->validPayload)
            ->assertNotFound(); // Repository ownership scope 404 döndürür

        // Ilan değişmemiş olmalı
        $this->assertDatabaseMissing('ilanlar', [
            'id'     => $this->ilan->id,
            'baslik' => 'Güncellenen Test İlanı',
        ]);
    }

    /**
     * SAB Kural 1 — Owner yayin_durumu değiştiremez.
     *
     * @test
     */
    public function update_cannot_change_yayin_durumu(): void
    {
        $payload = array_merge($this->validPayload, [
            'yayin_durumu' => IlanDurumu::YAYINDA->value, // Manipülasyon girişimi
        ]);

        $this->actingAs($this->owner)
            ->put(route('owner.ilanlar.update', $this->ilan->id), $payload);

        // yayin_durumu taslak kalmalı — Form Request strip ediyor
        $this->assertDatabaseHas('ilanlar', [
            'id'           => $this->ilan->id,
            'yayin_durumu' => IlanDurumu::TASLAK->value,
        ]);
    }

    // ─── destroy ─────────────────────────────────────────────────────────────

    /** @test */
    public function owner_can_delete_own_ilan(): void
    {
        $this->actingAs($this->owner)
            ->delete(route('owner.ilanlar.destroy', $this->ilan->id))
            ->assertRedirect(route('owner.ilanlar.index'));

        // Soft delete — DB'de gözükmemeli
        $this->assertSoftDeleted('ilanlar', ['id' => $this->ilan->id]);
    }

    /**
     * SAB Kural 1 — Cross-tenant silme yasaktır.
     *
     * @test
     */
    public function owner_cannot_delete_other_owners_ilan(): void
    {
        $this->actingAs($this->otherOwner)
            ->delete(route('owner.ilanlar.destroy', $this->ilan->id))
            ->assertNotFound();

        // İlan hâlâ durmalı
        $this->assertDatabaseHas('ilanlar', ['id' => $this->ilan->id, 'deleted_at' => null]);
    }
}
