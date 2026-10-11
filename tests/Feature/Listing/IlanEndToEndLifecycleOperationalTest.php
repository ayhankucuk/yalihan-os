<?php

namespace Tests\Feature\Listing;

use App\Enums\IlanDurumu;
use App\Models\Ilan;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Models\V2\Ilan as V2Ilan;
use App\Services\Listing\YalihanLifecycle;
use App\Services\SaaS\TenantContextService;
use Tests\TestCase;

class IlanEndToEndLifecycleOperationalTest extends TestCase
{
    private const TEST_ULKE_ID = 77;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $danismanA;
    protected User $danismanA2; // Same tenant, different advisor
    protected User $danismanB;  // Different tenant

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::firstOrCreate(
            ['domain' => 'e2e-tenant-a.local'],
            ['name' => 'E2E Tenant A']
        );

        $this->tenantB = Tenant::firstOrCreate(
            ['domain' => 'e2e-tenant-b.local'],
            ['name' => 'E2E Tenant B']
        );

        app(TenantContextService::class)->setTenant($this->tenantA);
        $this->danismanA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'ulke_id' => self::TEST_ULKE_ID,
            'aktiflik_durumu' => 1,
        ]);

        $this->danismanA2 = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'ulke_id' => self::TEST_ULKE_ID,
            'aktiflik_durumu' => 1,
        ]);

        app(TenantContextService::class)->setTenant($this->tenantB);
        $this->danismanB = User::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'ulke_id' => self::TEST_ULKE_ID,
            'aktiflik_durumu' => 1,
        ]);
    }

    /**
     * Test full listing lifecycle:
     * 1. Create -> 2. Save/Persist -> 3. Edit -> 4. Publish -> 5. Archive -> 6. Verify Archived Behavior
     */
    public function test_full_listing_lifecycle_e2e(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);
        $this->actingAs($this->danismanA, 'sanctum');

        // ── 1. CREATE LISTING ──────────────────────────────────────────
        $createPayload = [
            'baslik' => 'E2E Luxury Villa Bodrum',
            'aciklama' => 'Muhteşem deniz manzaralı lüks villa geniş bahçeli ve özel havuzlu.',
            'alan_m2' => 350.5,
            'birim_fiyat' => 45000000,
            'il' => 'Muğla',
            'ilce' => 'Bodrum',
            'mahalle' => 'Yalıkavak',
            'lat' => 37.105,
            'lng' => 27.295,
            'one_cikan' => true,
        ];

        $createResponse = $this->postJson('/api/v1/ilanlar', $createPayload);
        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'İlan başarıyla oluşturuldu');

        $ilanId = $createResponse->json('data.id');
        $this->assertNotNull($ilanId);

        // ── 2. SAVE / PERSISTENCE VERIFICATION ─────────────────────────
        $this->assertDatabaseHas('ilanlar', [
            'id' => $ilanId,
            'tenant_id' => $this->tenantA->id,
            'danisman_id' => $this->danismanA->id,
            'baslik' => 'E2E Luxury Villa Bodrum',
            'yayin_durumu' => IlanDurumu::TASLAK->value,
            'aktiflik_durumu' => 1,
        ]);

        // Unauthenticated or public show of draft should be concealed (404)
        app('auth')->forgetGuards();
        $draftPublicResponse = $this->getJson("/api/v1/ilanlar/{$ilanId}");
        $draftPublicResponse->assertStatus(404);

        // ── 3. EDIT LISTING ───────────────────────────────────────────
        $this->actingAs($this->danismanA, 'sanctum');
        $updatePayload = [
            'baslik' => 'E2E Luxury Villa Bodrum (Updated Edition)',
            'aciklama' => 'Muhteşem güncellenmiş deniz manzaralı lüks villa ve akıllı ev sistemi.',
            'alan_m2' => 380.0,
            'birim_fiyat' => 48000000,
        ];

        $updateResponse = $this->putJson("/api/v1/ilanlar/{$ilanId}", $updatePayload);
        $updateResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'İlan başarıyla güncellendi');

        $this->assertDatabaseHas('ilanlar', [
            'id' => $ilanId,
            'baslik' => 'E2E Luxury Villa Bodrum (Updated Edition)',
            'fiyat' => 48000000,
        ]);

        // ── 4. PUBLISH LISTING ─────────────────────────────────────────
        $kategori = \App\Models\IlanKategori::factory()->create();
        $sablon = \App\Models\YayinTipiSablonu::factory()->create(['kategori_id' => $kategori->id]);

        $ilanDb = Ilan::withoutGlobalScopes()->findOrFail($ilanId);
        $ilanDb->ana_kategori_id = $kategori->id;
        $ilanDb->yayin_tipi_id = $sablon->id;
        $ilanDb->saveQuietly();

        $mockScore = \Mockery::mock(\App\Services\Listing\ListingScoreService::class);
        $mockScore->shouldReceive('computeCompletionScore')->andReturn(100);
        $mockScore->shouldReceive('computeQualityScore')->andReturn(85);
        $this->app->instance(\App\Services\Listing\ListingScoreService::class, $mockScore);

        $publishResponse = $this->patchJson("/api/v1/ilanlar/{$ilanId}/publish");
        $publishResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'İlan yayınlandı');

        $this->assertDatabaseHas('ilanlar', [
            'id' => $ilanId,
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
        ]);

        // Published listing is now visible publicly
        app('auth')->forgetGuards();
        $publicShowResponse = $this->getJson("/api/v1/ilanlar/{$ilanId}");
        $publicShowResponse->assertStatus(200);

        // ── 5. ARCHIVE LISTING ─────────────────────────────────────────
        $ilanModel = Ilan::withoutGlobalScopes()->findOrFail($ilanId);
        $lifecycle = app(YalihanLifecycle::class);
        $archivedListing = $lifecycle->transition($ilanModel, IlanDurumu::ARSIV, meta: ['source' => 'e2e_test']);

        $this->assertEquals(IlanDurumu::ARSIV->value, $archivedListing->fresh()->yayin_durumu->value);
        $this->assertDatabaseHas('ilanlar', [
            'id' => $ilanId,
            'yayin_durumu' => IlanDurumu::ARSIV->value,
        ]);

        // ── 6. VERIFY ARCHIVED LISTING BEHAVIOR ────────────────────────
        // Public detail API must conceal archived listing from external public
        app('auth')->forgetGuards();
        $archivedPublicResponse = $this->getJson("/api/v1/ilanlar/{$ilanId}");
        $archivedPublicResponse->assertStatus(404);

        // Web public route must return 404 for archived listing
        $webPublicResponse = $this->get("/ilanlar/{$ilanId}");
        $webPublicResponse->assertStatus(404);

        // Public index must not include archived listing
        $indexResponse = $this->getJson('/api/v1/ilanlar');
        $indexResponse->assertStatus(200);
        $items = $indexResponse->json('data') ?? [];
        $ids = array_column($items, 'id');
        $this->assertNotContains($ilanId, $ids);
    }

    /**
     * Test tenant isolation on listing operations:
     * Tenant B cannot read draft, edit, publish, or delete Tenant A listing.
     */
    public function test_tenant_isolation_enforcement(): void
    {
        // 1. Create listing in Tenant A
        app(TenantContextService::class)->setTenant($this->tenantA);
        $this->actingAs($this->danismanA, 'sanctum');

        $createResponse = $this->postJson('/api/v1/ilanlar', [
            'baslik' => 'Tenant A Isolated Listing',
            'aciklama' => 'Tenant A özel portföyü için oluşturulmuş detaylı açıklama metni.',
            'alan_m2' => 200,
            'birim_fiyat' => 10000000,
            'il' => 'Muğla',
            'ilce' => 'Bodrum',
            'mahalle' => 'Gümüşlük',
            'lat' => 37.05,
            'lng' => 27.25,
        ]);
        $ilanId = $createResponse->json('data.id');

        // 2. Tenant B advisor attempts cross-tenant mutations
        app(TenantContextService::class)->setTenant($this->tenantB);
        $this->actingAs($this->danismanB, 'sanctum');

        // Update attempt from Tenant B -> 404 (ID enumeration protection)
        $updateAttempt = $this->putJson("/api/v1/ilanlar/{$ilanId}", [
            'baslik' => 'Cross-tenant malicious edit attempt',
        ]);
        $updateAttempt->assertStatus(404);

        // Publish attempt from Tenant B -> 404
        $publishAttempt = $this->patchJson("/api/v1/ilanlar/{$ilanId}/publish");
        $publishAttempt->assertStatus(404);

        // Delete attempt from Tenant B -> 404
        $deleteAttempt = $this->deleteJson("/api/v1/ilanlar/{$ilanId}");
        $deleteAttempt->assertStatus(404);

        // Database remains unchanged
        $this->assertDatabaseHas('ilanlar', [
            'id' => $ilanId,
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Tenant A Isolated Listing',
            'yayin_durumu' => IlanDurumu::TASLAK->value,
        ]);
    }

    /**
     * Test authorization boundaries:
     * 1. Unauthenticated users cannot create or edit.
     * 2. Different advisor in same tenant cannot edit another advisor's listing (403).
     */
    public function test_authorization_boundaries(): void
    {
        // 1. Unauthenticated attempts
        app('auth')->forgetGuards();
        $unauthCreate = $this->postJson('/api/v1/ilanlar', [
            'baslik' => 'Unauthorized Listing',
        ]);
        $this->assertContains($unauthCreate->status(), [401, 403]);

        // 2. Create listing owned by Danisman A
        app(TenantContextService::class)->setTenant($this->tenantA);
        $this->actingAs($this->danismanA, 'sanctum');

        $createResponse = $this->postJson('/api/v1/ilanlar', [
            'baslik' => 'Danisman A Private Listing',
            'aciklama' => 'Bu ilan danışman A tarafından yönetilmektedir.',
            'alan_m2' => 150,
            'birim_fiyat' => 8000000,
            'il' => 'Muğla',
            'ilce' => 'Bodrum',
            'mahalle' => 'Bitez',
            'lat' => 37.03,
            'lng' => 27.38,
        ]);
        $ilanId = $createResponse->json('data.id');

        // 3. Danisman A2 (same tenant, different user) attempts to edit -> 403 Forbidden
        $this->actingAs($this->danismanA2, 'sanctum');
        $sameTenantOtherAdvisorEdit = $this->putJson("/api/v1/ilanlar/{$ilanId}", [
            'baslik' => 'Hijacked by other advisor',
        ]);
        $sameTenantOtherAdvisorEdit->assertStatus(403);
    }
}
