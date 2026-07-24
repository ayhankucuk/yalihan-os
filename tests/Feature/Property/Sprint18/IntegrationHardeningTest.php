<?php

namespace Tests\Feature\Property\Sprint18;

use App\Models\CommercialOffering;
use App\Models\Property;
use App\Models\PropertyWorkspace;
use App\Models\WorkforceExecution;
use App\Models\Hermes\HermesEventLog;
use App\Services\Property\CommercialOfferingService;
use App\Domain\Shared\ValueObjects\Money;
use App\Listeners\Property\RecordCommercialOfferingOnTimeline;
use App\Domain\Property\Events\CommercialOfferingCreated;
use App\Domain\Property\Events\CommercialOfferingActivated;
use App\Domain\Property\Events\CommercialOfferingPriceChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class IntegrationHardeningTest extends TestCase
{
    use RefreshDatabase;

    private CommercialOfferingService $service;
    private RecordCommercialOfferingOnTimeline $timelineListener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CommercialOfferingService();
        $this->timelineListener = new RecordCommercialOfferingOnTimeline();
    }

    public function test_commercial_offering_creation_logs_workforce_execution(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Audit Test Workspace',
            'code' => 'WS-AUD-01',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-hard-1',
            'ada' => '301',
            'parsel' => '1',
        ]);

        $offering = $this->service->createOffering($property, [
            'offering_type' => 'SATILIK',
            'fiyat' => 8500000.00,
            'para_birimi' => 'TRY',
        ]);

        // Verify WorkforceExecution entry
        $execution = WorkforceExecution::where('aggregate_type', 'CommercialOffering')
            ->where('aggregate_id', $offering->id)
            ->first();

        $this->assertNotNull($execution);
        $this->assertEquals('create_offering', $execution->capability);
        $this->assertEquals('SUCCESS', $execution->execution_status);
        $this->assertEquals(1, $execution->tenant_id);
        $this->assertEquals($workspace->id, $execution->workspace_id);
    }

    public function test_record_commercial_offering_on_timeline_listener_persists_hermes_event_log(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Timeline Test Workspace',
            'code' => 'WS-TIM-01',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-hard-2',
            'ada' => '302',
            'parsel' => '2',
        ]);

        $offering = $this->service->createOffering($property, [
            'offering_type' => 'KIRALIK',
            'fiyat' => 35000.00,
        ]);

        // Manually invoke listener for integration verification
        $this->timelineListener->handleCreated(new CommercialOfferingCreated($offering));

        $log = HermesEventLog::where('event_name', 'Commercial Offering Created')->first();

        $this->assertNotNull($log);
        $this->assertEquals(1, $log->tenant_id);
        $this->assertEquals($workspace->id, $log->payload['workspace_id']);
        $this->assertEquals(35000.00, (float) $log->payload['fiyat']);
    }
}
