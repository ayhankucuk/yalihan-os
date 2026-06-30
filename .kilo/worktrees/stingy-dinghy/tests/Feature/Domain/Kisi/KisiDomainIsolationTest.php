<?php

namespace Tests\Feature\Domain\Kisi;

use Tests\TestCase;
use App\Domain\Kisi\KisiDomainYonetici;
use App\Domain\CQRS\Messaging\EventDispatcher;
use App\Exceptions\Governance\TenantMismatchException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Domain\Kisi\Projections\KisiProjectionHandler;
use App\Exceptions\Governance\ProjectionSequenceException;
use Database\Seeders\TenantBaselineSeeder;

/**
 * Class KisiDomainIsolationTest
 * @package Tests\Feature\Domain\Kisi
 * @description Phase 16 Sprint 2 & 3: Kisi/Lead dikey dilim sınır güvenliği, olay fırlatma ve asenkron idempotent projeksiyon senkronizasyonu testi.
 */
class KisiDomainIsolationTest extends TestCase
{
    use RefreshDatabase;

    private KisiDomainYonetici $domainYonetici;
    private mixed $mockDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        // Anayasal çoklu kiracı baseline verilerini SQLite in-memory veritabanına yükle
        $this->seed(TenantBaselineSeeder::class);

        $this->mockDispatcher = $this->createMock(EventDispatcher::class);
        $this->domainYonetici = new KisiDomainYonetici($this->mockDispatcher);
    }

    /**
     * @test
     */
    public function it_blocks_cross_tenant_lead_ingestion_and_throws_exception(): void
    {
        $user = new User();
        $user->tenant_id = 1;
        $this->actingAs($user);

        $this->expectException(TenantMismatchException::class);
        $this->expectExceptionMessage("🚨 SAB SECURITY BREACH: Cross-tenant lead ingestion blocked in KisiDomainYonetici.");

        $this->domainYonetici->secureLeadIngestion([
            'tenant_id' => 99, // Yabancı kiracı
            'ad_soyad' => 'Cross Tenant Lead',
            'telefon_numarasi' => '5551234567',
        ]);
    }

    /**
     * @test
     */
    public function it_ingests_lead_successfully_and_dispatches_event(): void
    {
        $user = new User();
        $user->tenant_id = 1;
        $this->actingAs($user);

        $this->mockDispatcher->expects($this->once())
            ->method('dispatchSingle')
            ->with(
                $this->callback(function (array $event) {
                    return $event['event_type'] === 'KisiAdayKaydiOlusturuldu' &&
                           $event['tenant_id'] === 1 &&
                           $event['payload']['kisi_tipi'] === 'lead' &&
                           $event['sequence_number'] === 1;
                })
            );

        $kisiId = $this->domainYonetici->secureLeadIngestion([
            'tenant_id' => 1,
            'ad_soyad' => 'Ali Veli',
            'telefon_numarasi' => '5551234567',
            'eposta' => 'ali.veli@yaliihan.com',
            'kisi_tipi' => 'lead'
        ]);

        $this->assertGreaterThan(0, $kisiId);

        // Verify database state directly
        $dbRecord = DB::table('kisiler')->where('id', $kisiId)->first();
        $this->assertNotNull($dbRecord);
        $this->assertEquals('Ali', $dbRecord->ad);
        $this->assertEquals('Veli', $dbRecord->soyad);
        $this->assertEquals('5551234567', $dbRecord->telefon);
        $this->assertEquals('ali.veli@yaliihan.com', $dbRecord->eposta);
    }

    /**
     * @test
     */
    public function it_synchronizes_projection_idempotently(): void
    {
        $handler = new KisiProjectionHandler();

        // 1. Yazma tablosuna (source) kisi ekle
        $kisiId = DB::table('kisiler')->insertGetId([
            'ad' => 'Ahmet',
            'soyad' => 'Yılmaz',
            'telefon' => '5559998877',
            'eposta' => 'ahmet@yaliihan.com',
            'kisi_tipi' => 'lead',
            'aktiflik_durumu' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 2. İlk kez projeksiyonu işle (sira_numarasi: 1)
        $handler->handleKisiAdayKaydiOlusturuldu([
            'kisi_id' => $kisiId,
            'tenant_id' => 1,
            'musteri_segmenti' => 'lead',
            'sira_numarasi' => 1
        ]);

        // 3. Projeksiyon kaydının oluştuğunu ve sira_numarasi'nin 1 olduğunu teyit et
        $projection = DB::table('kisiler_read_model')->where('id', $kisiId)->first();
        $this->assertNotNull($projection);
        $this->assertEquals('Ahmet Yılmaz', $projection->ad_soyad);
        $this->assertEquals(1, $projection->son_islenen_sira_numarasi);

        // 4. Eski sıra numaralı olayı gönder (sira_numarasi: 1, idempotent süzgece takılmalı)
        $handler->handleKisiAdayKaydiOlusturuldu([
            'kisi_id' => $kisiId,
            'tenant_id' => 1,
            'musteri_segmenti' => 'vip',
            'sira_numarasi' => 1 // Aynı sequence, güncellenmemeli!
        ]);

        $projectionAfterSkip = DB::table('kisiler_read_model')->where('id', $kisiId)->first();
        $this->assertEquals('lead', $projectionAfterSkip->musteri_segmenti); // VIP olmamalı, skip edilmeli!

        // 5. Yeni sıra numaralı olayı gönder (sira_numarasi: 2, işlenmeli)
        $handler->handleKisiAdayKaydiOlusturuldu([
            'kisi_id' => $kisiId,
            'tenant_id' => 1,
            'musteri_segmenti' => 'vip',
            'sira_numarasi' => 2
        ]);

        $projectionAfterUpdate = DB::table('kisiler_read_model')->where('id', $kisiId)->first();
        $this->assertEquals('vip', $projectionAfterUpdate->musteri_segmenti);
        $this->assertEquals(2, $projectionAfterUpdate->son_islenen_sira_numarasi);
    }
}
