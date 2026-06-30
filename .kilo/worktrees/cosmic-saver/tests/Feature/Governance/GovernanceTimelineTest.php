<?php

namespace Tests\Feature\Governance;

use App\Domain\PropertyHub\Observability\GovernanceTimelineService;
use App\Models\GovernanceIncident;
use App\Models\PropertyConfigVersion;
use Tests\TestCase;

class GovernanceTimelineTest extends TestCase
{

    /** @test */
    public function it_calculates_lineage_and_drift_overlay()
    {
        $v1 = PropertyConfigVersion::create([
            'version_hash' => 'h1',
            'governance_state' => 'ARSIVLENDI',
            'snapshot_json' => ['rules' => []],
            'signature' => 'sig1'
        ]);

        $v2 = PropertyConfigVersion::create([
            'version_hash' => 'h2',
            'parent_version_hash' => 'h1',
            'governance_state' => 'AKTIF',
            'snapshot_json' => ['rules' => []],
            'signature' => 'sig2'
        ]);

        // Create an incident for v1
        GovernanceIncident::create([
            'olay_tipi' => 'drift_detected',
            'kaynak' => 'Manual',
            'snapshot_id' => $v1->id,
            'risk_seviyesi' => 'HIGH'
        ]);

        $service = resolve(GovernanceTimelineService::class);
        $lineage = $service->getLineage();

        $this->assertCount(2, $lineage['nodes']);
        $this->assertCount(1, $lineage['edges']);

        $v1Node = collect($lineage['nodes'])->firstWhere('id', $v1->id);
        $v2Node = collect($lineage['nodes'])->firstWhere('id', $v2->id);

        $this->assertTrue($v1Node['has_drift']);
        $this->assertFalse($v2Node['has_drift']);
        $this->assertTrue($v2Node['aktiflik_durumu']);
        $this->assertEquals('h1', $lineage['edges'][0]['from']);
    }
}
