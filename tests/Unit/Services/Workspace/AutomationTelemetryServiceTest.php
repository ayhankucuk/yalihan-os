<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Workspace;

use App\Models\EtkiAlaniOlayi;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Workspace\AutomationTelemetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class AutomationTelemetryServiceTest
 *
 * Sprint 6.1-E07: Unit tests for AutomationTelemetryService
 *
 * @package Tests\Unit\Services\Workspace
 */
class AutomationTelemetryServiceTest extends TestCase
{
    use RefreshDatabase;

    private AutomationTelemetryService $service;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(AutomationTelemetryService::class);

        $this->tenant = Tenant::create([
            'name' => 'Telemetry Test Tenant',
            'domain' => 'telemetry.test',
            'aktiflik_durumu' => 1,
        ]);
    }

    /**
     * Test calculation returns 100% when there are no events recorded.
     */
    public function test_calculate_returns_100_when_no_events_exist(): void
    {
        $score = $this->service->calculateBusinessAutomationIndex($this->tenant->id);
        $this->assertEquals(100, $score);
    }

    /**
     * Test calculation computes the correct automation index ratio.
     */
    public function test_calculate_correctly_computes_automation_index(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        // 1. Create automated domain events (user_id = null)
        EtkiAlaniOlayi::create([
            'tenant_id' => $this->tenant->id,
            'aggregate_type' => 'PropertyWorkspace',
            'aggregate_id' => 1,
            'event_type' => 'WorkspaceCreated',
            'sequence_number' => 1,
            'payload' => [],
            'user_id' => null,
        ]);

        EtkiAlaniOlayi::create([
            'tenant_id' => $this->tenant->id,
            'aggregate_type' => 'PropertyWorkspace',
            'aggregate_id' => 1,
            'event_type' => 'StateChanged',
            'sequence_number' => 2,
            'payload' => [],
            'user_id' => null,
        ]);

        // 2. Create manual domain event (user_id = $user->id)
        EtkiAlaniOlayi::create([
            'tenant_id' => $this->tenant->id,
            'aggregate_type' => 'PropertyWorkspace',
            'aggregate_id' => 1,
            'event_type' => 'StateChanged',
            'sequence_number' => 3,
            'payload' => [],
            'user_id' => $user->id,
        ]);

        // E_auto = 2, E_manual = 1
        // Score = (2 / 3) * 100 = 67%
        $score = $this->service->calculateBusinessAutomationIndex($this->tenant->id);
        $this->assertEquals(67, $score);
    }
}
