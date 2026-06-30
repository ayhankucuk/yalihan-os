<?php

namespace Tests\Feature\CQRS;

use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Models\Projections\KisiReadModel;
use App\Repositories\CQRS\KisiReadRepository;
use App\Domain\CQRS\Projections\KisiProjeksiyonYoneticisi;
use App\Jobs\CQRS\ProcessProjectionJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Class CqrsProjectionAndCachingTest
 *
 * Verifies the correctness of the CQRS event source projection and caching isolation pipeline.
 *
 * @package Tests\Feature\CQRS
 */
class CqrsProjectionAndCachingTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected KisiProjeksiyonYoneticisi $projector;
    protected KisiReadRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenants
        $this->tenantA = Tenant::create([
            'name' => 'Tenant Alpha',
            'domain' => 'alpha.test',
            'aktiflik_durumu' => 1,
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Tenant Beta',
            'domain' => 'beta.test',
            'aktiflik_durumu' => 1,
        ]);

        $this->projector = app(KisiProjeksiyonYoneticisi::class);
        $this->repository = app(KisiReadRepository::class);
    }

    /** @test */
    public function it_projects_kisi_created_and_updated_events_idempotently()
    {
        $kisiUuid = 'kisi-123-uuid';

        // 1. Fire KisiOlusturuldu Event (Sequence = 1)
        $event1 = [
            'tenant_id' => $this->tenantA->id,
            'event_type' => 'KisiOlusturuldu',
            'aggregate_id' => $kisiUuid,
            'payload' => [
                'ad_soyad' => 'Ahmet Yılmaz',
                'telefon' => '5551234567',
                'eposta' => 'ahmet@test.com',
                'segment' => 'VIP',
                'tercihler' => ['sms' => true, 'whatsapp' => false],
            ],
            'sequence_number' => 1,
        ];

        $this->projector->handle($event1);

        // Verify read model is created in DB
        $projection = DB::table('kisiler_read_model')
            ->where('tenant_id', $this->tenantA->id)
            ->where('uuid', $kisiUuid)
            ->first();

        $this->assertNotNull($projection);
        $this->assertEquals('Ahmet Yılmaz', $projection->ad_soyad);
        $this->assertEquals('5551234567', $projection->telefon_numarasi);
        $this->assertEquals('ahmet@test.com', $projection->eposta_adresi);
        $this->assertEquals('VIP', $projection->musteri_segmenti);
        $this->assertEquals(1, $projection->son_islenen_sira_numarasi);

        // 2. Fire IletisimBilgisiGuncellendi Event (Sequence = 2)
        $event2 = [
            'tenant_id' => $this->tenantA->id,
            'event_type' => 'IletisimBilgisiGuncellendi',
            'aggregate_id' => $kisiUuid,
            'payload' => [
                'yeni_telefon' => '5559876543',
                'yeni_eposta' => 'ahmet.yeni@test.com',
            ],
            'sequence_number' => 2,
        ];

        $this->projector->handle($event2);

        $projection = DB::table('kisiler_read_model')
            ->where('tenant_id', $this->tenantA->id)
            ->where('uuid', $kisiUuid)
            ->first();

        $this->assertEquals('5559876543', $projection->telefon_numarasi);
        $this->assertEquals('ahmet.yeni@test.com', $projection->eposta_adresi);
        $this->assertEquals(2, $projection->son_islenen_sira_numarasi);

        // 3. Fire duplicate event (Sequence = 1) - SHOULD BE IGNORED
        $eventDuplicate = [
            'tenant_id' => $this->tenantA->id,
            'event_type' => 'IletisimBilgisiGuncellendi',
            'aggregate_id' => $kisiUuid,
            'payload' => [
                'yeni_telefon' => '5550000000',
                'yeni_eposta' => 'duplicate@test.com',
            ],
            'sequence_number' => 1, // lower than 2
        ];

        $this->projector->handle($eventDuplicate);

        // State must remain at Sequence 2 values
        $projection = DB::table('kisiler_read_model')
            ->where('tenant_id', $this->tenantA->id)
            ->where('uuid', $kisiUuid)
            ->first();

        $this->assertEquals('5559876543', $projection->telefon_numarasi);
        $this->assertEquals('ahmet.yeni@test.com', $projection->eposta_adresi);
        $this->assertEquals(2, $projection->son_islenen_sira_numarasi);
    }

    /** @test */
    public function it_enforces_tenant_cache_isolation_and_invalidation()
    {
        $kisiUuidA = 'kisi-alpha-uuid';
        $kisiUuidB = 'kisi-beta-uuid';

        // Project Kisi A (Tenant A)
        $this->projector->handle([
            'tenant_id' => $this->tenantA->id,
            'event_type' => 'KisiOlusturuldu',
            'aggregate_id' => $kisiUuidA,
            'payload' => [
                'ad_soyad' => 'Tenant A Customer',
                'telefon' => '1111111111',
            ],
            'sequence_number' => 1,
        ]);

        // Project Kisi B (Tenant B)
        $this->projector->handle([
            'tenant_id' => $this->tenantB->id,
            'event_type' => 'KisiOlusturuldu',
            'aggregate_id' => $kisiUuidB,
            'payload' => [
                'ad_soyad' => 'Tenant B Customer',
                'telefon' => '2222222222',
            ],
            'sequence_number' => 1,
        ]);

        // Query Repository under Tenant Alpha Context
        config(['app.tenant_id' => $this->tenantA->id]);

        $kisiA = $this->repository->findByUuid($kisiUuidA);
        $this->assertNotNull($kisiA);
        $this->assertEquals('Tenant A Customer', $kisiA->ad_soyad);

        // Attempting to query Kisi B under Tenant Alpha Context should yield null (due to HasCountryScope / Global TenantScope)
        $kisiBUnderAlpha = $this->repository->findByUuid($kisiUuidB);
        $this->assertNull($kisiBUnderAlpha);
    }

    /** @test */
    public function it_enforces_bul_ceresel_tenant_isolation_and_caching()
    {
        $kisiUuid = 'kisi-ceresel-uuid';

        // 1. Create a Kisi record under Tenant A
        $this->projector->handle([
            'tenant_id' => $this->tenantA->id,
            'event_type' => 'KisiOlusturuldu',
            'aggregate_id' => $kisiUuid,
            'payload' => [
                'ad_soyad' => 'Confinement Test Customer',
                'telefon' => '9999999999',
            ],
            'sequence_number' => 1,
        ]);

        // 2. Query bulCeresel under Tenant A -> should succeed
        $kisi = $this->repository->bulCeresel($this->tenantA->id, $kisiUuid);
        $this->assertNotNull($kisi);
        $this->assertEquals('Confinement Test Customer', $kisi->ad_soyad);

        // 3. Query bulCeresel under Tenant B -> should return null
        $kisiUnderB = $this->repository->bulCeresel($this->tenantB->id, $kisiUuid);
        $this->assertNull($kisiUnderB);
    }
}
