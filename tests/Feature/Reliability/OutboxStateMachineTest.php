<?php

namespace Tests\Feature\Reliability;

use App\Events\IlanCreated;
use App\Models\Ilan;
use App\Models\OutboxEntry;
use App\Services\Reliability\OutboxService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OutboxStateMachineTest extends TestCase
{
    use DatabaseTransactions;

    private OutboxService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OutboxService();
    }

    /** @test */
    public function it_processes_pending_outbox_events_successfully()
    {
        Event::fake([IlanCreated::class]);

        // Create an Ilan
        $ilan = Ilan::factory()->create([
            'baslik' => 'Outbox Test Ilan',
            'aktiflik_durumu' => 1
        ]);

        // Publish event to outbox
        $entry = $this->service->publish('ilan.created', [
            'ilan' => ['id' => $ilan->id]
        ], 'idemp-outbox-key-1');

        $this->assertEquals('PENDING', $entry->yayin_durumu);
        $this->assertEquals(0, $entry->attempts);

        // Run the console command to process outbox
        $this->artisan('outbox:process', ['--once' => true])
            ->assertExitCode(0);

        // Refresh entry from DB
        $entry->refresh();

        $this->assertEquals('COMPLETED', $entry->yayin_durumu);
        $this->assertEquals(1, $entry->attempts);
        $this->assertNull($entry->error_message);
        $this->assertNotNull($entry->processed_at);

        // Assert event was dispatched
        Event::assertDispatched(IlanCreated::class, function ($event) use ($ilan) {
            return $event->ilan->id === $ilan->id;
        });
    }

    /** @test */
    public function it_handles_failed_outbox_events_and_dead_letter_limits()
    {
        // Publish invalid event that will fail (null ID for required model)
        $entry = $this->service->publish('ilan.created', [
            'ilan' => null
        ], 'idemp-outbox-key-2');

        $this->assertEquals('PENDING', $entry->yayin_durumu);

        // Run processing once: should fail
        $this->artisan('outbox:process', ['--once' => true])
            ->assertExitCode(0);

        $entry->refresh();
        $this->assertEquals('FAILED', $entry->yayin_durumu);
        $this->assertEquals(1, $entry->attempts);
        $this->assertStringContainsString('Model ID for parameter', $entry->error_message);

        // Set attempts to 4 to simulate repeated failures
        $entry->attempts = 4;
        $entry->save();

        // Run processing again: should move to DEAD_LETTER
        $this->artisan('outbox:process', ['--once' => true])
            ->assertExitCode(0);

        $entry->refresh();
        $this->assertEquals('DEAD_LETTER', $entry->yayin_durumu);
        $this->assertEquals(5, $entry->attempts);
    }
}
