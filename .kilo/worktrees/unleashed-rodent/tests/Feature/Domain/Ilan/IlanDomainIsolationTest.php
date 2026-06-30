<?php

namespace Tests\Feature\Domain\Ilan;

use Tests\TestCase;
use App\Domain\Ilan\IlanDomainYonetici;
use App\Services\Ilan\IlanCrudService;
use App\Domain\CQRS\Messaging\EventDispatcher;
use App\Exceptions\Governance\TenantMismatchException;
use App\Models\Ilan;
use App\Models\User;
use App\Enums\IlanDurumu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Class IlanDomainIsolationTest
 * @package Tests\Feature\Domain\Ilan
 * @description Phase 16: Ilan dikey dilim sınır güvenliği ve asenkron olay fırlatma mekanizması anayasal birim testi.
 */
class IlanDomainIsolationTest extends TestCase
{
    use RefreshDatabase;

    private IlanDomainYonetici $domainYonetici;
    private mixed $mockCrudService;
    private mixed $mockDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCrudService = $this->createMock(IlanCrudService::class);
        $this->mockDispatcher = $this->createMock(EventDispatcher::class);

        $this->domainYonetici = new IlanDomainYonetici(
            $this->mockCrudService,
            $this->mockDispatcher
        );
    }

    /**
     * @test
     */
    public function it_blocks_cross_tenant_listing_mutation_and_throws_exception(): void
    {
        $user = new User();
        $user->tenant_id = 1;
        $this->actingAs($user);

        // SQLite in-memory test şemasına atomik mock veri enjeksiyonu
        $ilanId = DB::table('ilanlar')->insertGetId([
            'tenant_id' => 99, // Yabancı kiracı
            'baslik' => 'SAB Cross-Tenant İhlal Testi',
            'slug' => 'sab-cross-tenant-ihlal-testi',
            'yayin_durumu' => IlanDurumu::TASLAK->value,
            'aktiflik_durumu' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->expectException(TenantMismatchException::class);
        $this->expectExceptionMessage("SAB SECURITY BREACH: Cross-tenant domain mutation blocked");

        $this->domainYonetici->yayinDurumuMutasyonu($ilanId, IlanDurumu::YAYINDA->value);
    }

    /**
     * @test
     */
    public function it_mutates_yayin_durumu_successfully_and_dispatches_single_canonical_event(): void
    {
        $user = new User();
        $user->tenant_id = 1;
        $this->actingAs($user);

        $ilanId = DB::table('ilanlar')->insertGetId([
            'tenant_id' => 1, // Meşru kiracı
            'baslik' => 'SAB Mühürlü Villa',
            'slug' => 'sab-muhurlu-villa',
            'yayin_durumu' => IlanDurumu::TASLAK->value,
            'aktiflik_durumu' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Monolitik alt servisin başarılı dönmesi simülasyonu
        $dummyIlan = new Ilan();
        $dummyIlan->id = $ilanId;
        $dummyIlan->tenant_id = 1;
        $dummyIlan->yayin_durumu = IlanDurumu::YAYINDA;

        $this->mockCrudService->expects($this->once())
            ->method('update')
            ->willReturn($dummyIlan);

        // Phase 15 & 16 dispatchSingle imza ve parameter kontrolü
        $this->mockDispatcher->expects($this->once())
            ->method('dispatchSingle')
            ->with(
                $this->callback(function (array $event) use ($ilanId) {
                    return $event['event_type'] === 'IlanYayinDurumuDegisti' &&
                           $event['aggregate_id'] === $ilanId &&
                           $event['payload']['yeni_durum'] === IlanDurumu::YAYINDA->value &&
                           $event['sequence_number'] === 1;
                })
            );

        $result = $this->domainYonetici->yayinDurumuMutasyonu($ilanId, IlanDurumu::YAYINDA->value);
        $this->assertTrue($result);
    }
}
