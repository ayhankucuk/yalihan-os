<?php

namespace Tests\Feature\Governance;

use App\Modules\GovernanceCore\Services\DriftDetectionService;
use App\Modules\GovernanceCore\Services\AutonomousDriftResponder;
use App\Domain\PropertyHub\Resiliency\HealthAutoRecoveryService;
use App\Models\PropertyConfigVersion;
use Tests\TestCase;

class ServiceResolutionTest extends TestCase
{
    /** @test */
    public function it_resolves_all_governance_services()
    {
        $responder = resolve(AutonomousDriftResponder::class);
        $this->assertNotNull($responder);

        $detector = resolve(DriftDetectionService::class);
        $this->assertNotNull($detector);

        $recovery = resolve(HealthAutoRecoveryService::class);
        $this->assertNotNull($recovery);

        echo "Resolution Success!\n";
    }
}
