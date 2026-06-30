<?php

namespace Tests\Feature\CQRS;

use App\Models\SaaS\Tenant;
use App\Models\Projections\KisiReadModel;
use App\Jobs\CQRS\ProcessProjectionJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Class CqrsRebuildAndDlqRetryTest
 *
 * Verifies the correctness of the rebuild engine and DLQ retry engine commands.
 *
 * @package Tests\Feature\CQRS
 */
class CqrsRebuildAndDlqRetryTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Tenant Alpha',
            'domain' => 'alpha.test',
            'aktiflik_durumu' => 1,
        ]);
    }

    /** @test */
    public function it_rebuilds_read_models_from_immutable_event_store()
    {
        $kisiUuid = 'kisi-rebuild-uuid';

        // 1. Insert a mock domain event directly into the immutable event store (etki_alani_olaylari)
        DB::table('etki_alani_olaylari')->insert([
            'tenant_id' => $this->tenant->id,
            'aggregate_type' => 'App\Domain\CQRS\Aggregates\KisiAggregate',
            'aggregate_id' => 12345, // Kisi aggregate ID
            'event_type' => 'KisiOlusturuldu',
            'sequence_number' => 1,
            'payload' => json_encode([
                'ad_soyad' => 'Ahmet Rebuilder',
                'telefon' => '5559876543',
                'eposta' => 'rebuild@test.com',
                'segment' => 'Regular',
                'tercihler' => ['sms' => true],
            ]),
            'created_at' => now(),
        ]);

        // 2. Pre-seed the read model to ensure the rebuild command truncates it correctly
        DB::table('kisiler_read_model')->insert([
            'tenant_id' => $this->tenant->id,
            'uuid' => $kisiUuid,
            'ad_soyad' => 'Stale Data To Be Wiped',
            'telefon_numarasi' => '0000000000',
            'eposta_adresi' => 'stale@test.com',
            'musteri_segmenti' => 'Stale',
            'son_islenen_sira_numarasi' => 999,
            'olusturulma_zamani' => now()->toIso8601String(),
        ]);

        // Verify the pre-seeded stale read model exists
        $this->assertDatabaseHas('kisiler_read_model', [
            'ad_soyad' => 'Stale Data To Be Wiped',
        ]);

        // 3. Run the projections:rebuild Artisan Command
        $this->artisan('projections:rebuild', ['--domain' => 'kisiler'])->assertExitCode(0);

        // 4. Since Queue::fake() is active, retrieve the pushed jobs and run them manually
        $pushedJobs = Queue::pushed(ProcessProjectionJob::class);
        foreach ($pushedJobs as $job) {
            $job->handle();
        }

        // 5. Verify stale data is wiped, and new data is successfully projected from event store
        $this->assertDatabaseMissing('kisiler_read_model', [
            'ad_soyad' => 'Stale Data To Be Wiped',
        ]);

        $this->assertDatabaseHas('kisiler_read_model', [
            'tenant_id' => $this->tenant->id,
            'uuid' => '12345',
            'ad_soyad' => 'Ahmet Rebuilder',
            'telefon_numarasi' => '5559876543',
            'eposta_adresi' => 'rebuild@test.com',
            'musteri_segmenti' => 'Regular',
            'son_islenen_sira_numarasi' => 1,
        ]);
    }

    /** @test */
    public function it_retries_failed_events_from_dlq_successfully()
    {
        $kisiUuid = '9999';

        // 1. Prepare event array that will be replayed
        $eventData = [
            'tenant_id' => $this->tenant->id,
            'aggregate_type' => 'App\Domain\CQRS\Aggregates\KisiAggregate',
            'aggregate_id' => 9999,
            'event_type' => 'KisiOlusturuldu',
            'sequence_number' => 1,
            'payload' => [
                'ad_soyad' => 'Fatma DLQ',
                'telefon' => '5557777777',
                'eposta' => 'dlq@test.com',
                'segment' => 'VIP',
                'tercihler' => ['email' => true],
            ],
        ];

        // 2. Insert into Dead Letter Queue (etki_alani_olaylari_hatali)
        $dlqId = DB::table('etki_alani_olaylari_hatali')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'olay_turu' => 'KisiOlusturuldu',
            'kaynak_kimligi' => '9999',
            'olay_verisi' => json_encode($eventData),
            'hata_mesaji' => 'DivisionByZeroException',
            'stack_trace' => '#0 ...',
            'islem_durumu' => 1, // 1: İncelemede
            'olusturulma_zamani' => now(),
        ]);

        // Verify it is in DLQ
        $this->assertDatabaseHas('etki_alani_olaylari_hatali', [
            'id' => $dlqId,
            'islem_durumu' => 1,
        ]);

        // 3. Run the DLQ Retry Command
        $this->artisan('sentinel:dlq-retry', [
            '--id' => $dlqId,
        ])->assertExitCode(0);

        // 4. Verify that the event has been queued
        Queue::assertPushed(ProcessProjectionJob::class);

        // Manually run the job to verify the database projection effect (since Queue is faked)
        (new ProcessProjectionJob($eventData))->handle();

        // Verify read model is created in DB
        $this->assertDatabaseHas('kisiler_read_model', [
            'tenant_id' => $this->tenant->id,
            'uuid' => $kisiUuid,
            'ad_soyad' => 'Fatma DLQ',
            'telefon_numarasi' => '5557777777',
        ]);

        // 5. Verify the DLQ record has been updated to status 2 (Yeniden Oynatıldı) and has islenme_zamani populated
        $dlqRecord = DB::table('etki_alani_olaylari_hatali')->where('id', $dlqId)->first();
        $this->assertEquals(2, $dlqRecord->islem_durumu);
        $this->assertNotNull($dlqRecord->islenme_zamani);
    }
}
