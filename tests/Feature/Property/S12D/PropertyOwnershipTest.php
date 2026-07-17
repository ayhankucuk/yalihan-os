<?php

namespace Tests\Feature\Property\S12D;

use App\Models\Property;
use App\Domain\PropertyOwnership\Models\PropertyOwnership;
use App\Enums\SahiplikTipi;
use App\Models\Kisi;
use App\Models\SaaS\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 12D Feature Tests
 *
 * Tests the 12 mandatory scenarios from the SAAB Board Resolution.
 */
class PropertyOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Property::$skipWorkspaceIdGuard = true;

        $this->tenantA = Tenant::create(['name' => 'Tenant A']);
        $this->tenantB = Tenant::create(['name' => 'Tenant B']);

        $this->userA = User::factory()->create(['tenant_id' => $this->tenantA->id]);
        $this->userB = User::factory()->create(['tenant_id' => $this->tenantB->id]);
    }

    private function actingAsTenantA(): self
    {
        $this->actingAs($this->userA);
        app(\App\Services\SaaS\TenantContextService::class)->setTenant($this->tenantA);
        return $this;
    }

    private function actingAsTenantB(): self
    {
        $this->actingAs($this->userB);
        app(\App\Services\SaaS\TenantContextService::class)->setTenant($this->tenantB);
        return $this;
    }

    // ─── Scenario 1: Cross-tenant malik atama reddedilir ───────────────

    public function test_cross_tenant_ownership_assignment_is_rejected(): void
    {
        $this->actingAsTenantA();

        $property = Property::factory()->create(['tenant_id' => $this->tenantA->id]);
        $kisiB = Kisi::factory()->create(['tenant_id' => $this->tenantB->id]);

        $service = app(\App\Services\Property\Ownership\PropertyOwnershipService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('does not belong to current tenant');

        $service->assignOwnership(
            property: $property,
            kisi: $kisiB,
            share: 1.0,
            ownershipType: SahiplikTipi::OWNER,
            effectiveDate: now()->toDateString(),
            actorId: $this->userA->id,
        );
    }

    // ─── Scenario 2: Aktif pay toplamı 1.0 aşarsa transaction rollback ──

    public function test_ownership_share_sum_exceeding_one_rolls_back(): void
    {
        $this->actingAsTenantA();

        $property = Property::factory()->create(['tenant_id' => $this->tenantA->id]);
        $owner1 = Kisi::factory()->create(['tenant_id' => $this->tenantA->id]);
        $owner2 = Kisi::factory()->create(['tenant_id' => $this->tenantA->id]);

        $service = app(\App\Services\Property\Ownership\PropertyOwnershipService::class);

        $service->assignOwnership(
            property: $property,
            kisi: $owner1,
            share: 0.7,
            ownershipType: SahiplikTipi::OWNER,
            effectiveDate: now()->toDateString(),
            actorId: $this->userA->id,
        );

        try {
            $service->assignOwnership(
                property: $property,
                kisi: $owner2,
                share: 0.5,
                ownershipType: SahiplikTipi::OWNER,
                effectiveDate: now()->toDateString(),
                actorId: $this->userA->id,
            );
            $this->fail('Expected DomainException was not thrown');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('exceed 1.0', $e->getMessage());
        }

        $count = PropertyOwnership::where('property_id', $property->id)->active()->count();
        $this->assertEquals(1, $count);
    }

    // ─── Scenario 3: Geçmiş sahiplik değiştirme reddedilir ──────────

    public function test_changing_historical_ownership_is_rejected(): void
    {
        $property = Property::factory()->create(['tenant_id' => $this->tenantA->id]);
        $kisi = Kisi::factory()->create(['tenant_id' => $this->tenantA->id]);

        $ownership = PropertyOwnership::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $property->id,
            'kisi_id' => $kisi->id,
            'pay_orani' => 0.5,
            'sahiplik_tipi' => 'OWNER',
            'baslangic_tarihi' => now()->subMonth()->toDateString(),
            'bitis_tarihi' => now()->subMonth()->toDateString(),
            'atama_kaynagi' => 'MANUAL',
            'idempotency_key' => 'test-historical-' . uniqid(),
            'olusturan_id' => $this->userA->id,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('immutable');

        $ownership->pay_orani = 1.0;
        $ownership->save();
    }

    // ─── Scenario 4: Mali değişimi: eski dönem kapanır, yeni açılır ──

    public function test_ownership_transfer_closes_old_opens_new(): void
    {
        $this->actingAsTenantA();

        $property = Property::factory()->create(['tenant_id' => $this->tenantA->id]);
        $oldOwner = Kisi::factory()->create(['tenant_id' => $this->tenantA->id]);
        $newOwner = Kisi::factory()->create(['tenant_id' => $this->tenantA->id]);

        $service = app(\App\Services\Property\Ownership\PropertyOwnershipService::class);

        $initial = $service->assignOwnership(
            property: $property,
            kisi: $oldOwner,
            share: 1.0,
            ownershipType: SahiplikTipi::OWNER,
            effectiveDate: now()->subMonth()->toDateString(),
            actorId: $this->userA->id,
        );
        $this->assertTrue($initial->isActive());

        $transferDate = now()->toDateString();
        [$closed, $opened] = $service->transferOwnership(
            property: $property,
            fromKisi: $oldOwner,
            toKisi: $newOwner,
            share: 1.0,
            effectiveDate: $transferDate,
            actorId: $this->userA->id,
        );

        $this->assertNotNull($closed->bitis_tarihi);
        $this->assertEquals($closed->bitis_tarihi->toDateString(), $transferDate);
        $this->assertEquals($opened->kisi_id, $newOwner->id);
        $this->assertTrue($opened->isActive());

        $activeCount = PropertyOwnership::where('property_id', $property->id)->active()->count();
        $this->assertEquals(1, $activeCount);
    }

    // ─── Scenario 5: Aynı idempotency key aynı kaydı döndürür ───

    public function test_idempotent_custody_transfer_returns_same_record(): void
    {
        $this->actingAsTenantA();

        $property = Property::factory()->create(['tenant_id' => $this->tenantA->id]);
        $asset = \App\Domain\PropertyAccess\Models\PropertyAccessAsset::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $property->id,
            'varlik_tipi' => 'KEY',
            'durum' => 'AKTIF',
            'olusturan_id' => $this->userA->id,
        ]);
        $holder = Kisi::factory()->create(['tenant_id' => $this->tenantA->id]);

        $service = app(\App\Services\Property\Access\PropertyAccessAssetService::class);

        $key = 'idempotent-key-' . uniqid();
        $custody1 = $service->transferCustody($asset, $holder, note: 'First', idempotencyKey: $key);
        $custody2 = $service->transferCustody($asset, $holder, note: 'Second', idempotencyKey: $key);

        $this->assertEquals($custody1->id, $custody2->id);
    }

    // ─── Scenario 6: Anahtar iade edilir, IADE kaydı oluşur ────────

    public function test_key_return_creates_return_record(): void
    {
        $this->actingAsTenantA();

        $property = Property::factory()->create(['tenant_id' => $this->tenantA->id]);
        $asset = \App\Domain\PropertyAccess\Models\PropertyAccessAsset::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $property->id,
            'varlik_tipi' => 'KEY',
            'durum' => 'AKTIF',
            'olusturan_id' => $this->userA->id,
        ]);
        $holder = Kisi::factory()->create(['tenant_id' => $this->tenantA->id]);

        $service = app(\App\Services\Property\Access\PropertyAccessAssetService::class);
        $service->transferCustody($asset, $holder);
        $return = $service->returnCustody($asset);

        $this->assertEquals(
            \App\Domain\PropertyAccess\Models\PropertyKeyCustody::TYPE_IADE,
            $return->islem_tipi
        );
    }

    // ─── Scenario 7: Anahtar yeniden teslim edilir, yeni custody kaydı oluşur ──

    public function test_key_retransfer_creates_new_custody_record(): void
    {
        $this->actingAsTenantA();

        $property = Property::factory()->create(['tenant_id' => $this->tenantA->id]);
        $asset = \App\Domain\PropertyAccess\Models\PropertyAccessAsset::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $property->id,
            'varlik_tipi' => 'KEY',
            'durum' => 'AKTIF',
            'olusturan_id' => $this->userA->id,
        ]);
        $holder1 = Kisi::factory()->create(['tenant_id' => $this->tenantA->id]);
        $holder2 = Kisi::factory()->create(['tenant_id' => $this->tenantA->id]);

        $service = app(\App\Services\Property\Access\PropertyAccessAssetService::class);

        $c1 = $service->transferCustody($asset, $holder1);
        $this->assertEquals($holder1->id, $c1->kisi_id);

        $service->returnCustody($asset);

        $c2 = $service->transferCustody($asset, $holder2);
        $this->assertEquals($holder2->id, $c2->kisi_id);

        $historyCount = $asset->custodies()->count();
        $this->assertEquals(3, $historyCount);
    }

    // ─── Scenario 8: Süresi dolan belge bir kez expired yapılır ─────

    public function test_document_expired_once_and_not_again(): void
    {
        $property = Property::factory()->create(['tenant_id' => $this->tenantA->id]);

        $doc = \App\Domain\PropertyDocument\Models\PropertyDocument::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $property->id,
            'dokuman_tipi' => 'TITLE_DEED',
            'son_gecerlilik_tarihi' => now()->subDay()->toDateString(),
            'durum' => 'AKTIF',
            'idempotency_key' => 'test-expired-' . uniqid(),
            'olusturan_id' => $this->userA->id,
        ]);

        $this->assertEquals('AKTIF', $doc->durum);
        $this->assertTrue($doc->isExpired());

        $doc->markExpired();
        $this->assertEquals('SURESI_DOLMUS', $doc->durum);

        $doc->markExpired();
        $this->assertEquals('SURESI_DOLMUS', $doc->durum);
    }

    // ─── Scenario 9: Scheduler yeniden çalışır, duplicate event oluşmaz ──

    public function test_document_expiry_is_idempotent(): void
    {
        $property = Property::factory()->create(['tenant_id' => $this->tenantA->id]);

        $doc = \App\Domain\PropertyDocument\Models\PropertyDocument::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $property->id,
            'dokuman_tipi' => 'INSURANCE',
            'son_gecerlilik_tarihi' => now()->subDay()->toDateString(),
            'durum' => 'AKTIF',
            'idempotency_key' => 'test-expiry-' . uniqid(),
            'olusturan_id' => $this->userA->id,
        ]);

        $doc->markExpired();
        $this->assertEquals('SURESI_DOLMUS', $doc->durum);

        $doc->refresh();
        $doc->markExpired();
        $this->assertEquals('SURESI_DOLMUS', $doc->durum);
    }

    // ─── Scenario 10: Timeline replay orijinal kayıtları değiştirmez ──

    public function test_timeline_query_does_not_mutate_records(): void
    {
        $this->actingAsTenantA();

        $property = Property::factory()->create(['tenant_id' => $this->tenantA->id]);
        $kisi = Kisi::factory()->create(['tenant_id' => $this->tenantA->id]);

        $ownership = PropertyOwnership::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $property->id,
            'kisi_id' => $kisi->id,
            'pay_orani' => 1.0,
            'sahiplik_tipi' => 'OWNER',
            'baslangic_tarihi' => now()->toDateString(),
            'atama_kaynagi' => 'MANUAL',
            'idempotency_key' => 'test-timeline-' . uniqid(),
            'olusturan_id' => $this->userA->id,
        ]);

        $originalBitis = $ownership->bitis_tarihi;
        $originalPay = $ownership->pay_orani;

        $service = app(\App\Domain\PropertyTimeline\PropertyTimelineService::class);
        $service->getTimeline($property);

        $ownership->refresh();
        $this->assertEquals($originalBitis, $ownership->bitis_tarihi);
        $this->assertEquals($originalPay, $ownership->pay_orani);
    }

    // ─── Scenario 11: Yetkisiz kullanıcı gizli alanları göremez ─────

    public function test_sensitive_credential_hidden_by_default(): void
    {
        $property = Property::factory()->create(['tenant_id' => $this->tenantA->id]);

        $asset = \App\Domain\PropertyAccess\Models\PropertyAccessAsset::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $property->id,
            'varlik_tipi' => 'ALARM_CODE',
            'tanimlayici_no' => '1234',
            'durum' => 'AKTIF',
            'olusturan_id' => $this->userA->id,
        ]);

        $array = $asset->toArray();
        $this->assertArrayNotHasKey('tanimlayici_no', $array);

        $this->assertNull($asset->getCredentialForViewer(null));
    }

    // ─── Scenario 12: Tenant A timeline Tenant B kayıtlarını içermez ──

    public function test_timeline_excludes_other_tenant_records(): void
    {
        $this->actingAsTenantA();

        $propertyA = Property::factory()->create(['tenant_id' => $this->tenantA->id]);
        $propertyB = Property::factory()->create(['tenant_id' => $this->tenantB->id]);
        $kisiA = Kisi::factory()->create(['tenant_id' => $this->tenantA->id]);
        $kisiB = Kisi::factory()->create(['tenant_id' => $this->tenantB->id]);

        PropertyOwnership::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $propertyA->id,
            'kisi_id' => $kisiA->id,
            'pay_orani' => 1.0,
            'sahiplik_tipi' => 'OWNER',
            'baslangic_tarihi' => now()->toDateString(),
            'atama_kaynagi' => 'MANUAL',
            'idempotency_key' => 'tenant-a-' . uniqid(),
            'olusturan_id' => $this->userA->id,
        ]);

        PropertyOwnership::create([
            'tenant_id' => $this->tenantB->id,
            'property_id' => $propertyB->id,
            'kisi_id' => $kisiB->id,
            'pay_orani' => 1.0,
            'sahiplik_tipi' => 'OWNER',
            'baslangic_tarihi' => now()->toDateString(),
            'atama_kaynagi' => 'MANUAL',
            'idempotency_key' => 'tenant-b-' . uniqid(),
            'olusturan_id' => $this->userB->id,
        ]);

        $service = app(\App\Domain\PropertyTimeline\PropertyTimelineService::class);
        $timeline = $service->getTimeline($propertyA);

        $this->assertCount(1, $timeline->events);
    }
}
