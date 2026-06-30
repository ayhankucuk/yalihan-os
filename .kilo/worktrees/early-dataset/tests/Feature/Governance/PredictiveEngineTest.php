<?php

namespace Tests\Feature\Governance;

use App\Modules\GovernanceCore\Core\GovernanceRiskScorer;
use App\Modules\GovernanceCore\Core\VersionStateMachine;
use App\Models\PropertyConfigVersion;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * @group skip-until-migration-complete
 * Ghost class: Predictive engine infrastructure henüz implement edilmedi.
 */
class PredictiveEngineTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    /** @test */
    public function it_scores_risk_as_critical_on_structural_change()
    {
        $v1 = PropertyConfigVersion::create([
            'version_hash' => 'v1',
            'yonetim_durumu' => VersionStateMachine::DURUM_AKTIF,
            'snapshot_json' => ['meta' => ['version_schema' => '1.0'], 'rules' => []],
            'signature' => 'sig1'
        ]);

        $v2 = PropertyConfigVersion::create([
            'version_hash' => 'v2',
            'yonetim_durumu' => VersionStateMachine::DURUM_TASLAK,
            'snapshot_json' => ['meta' => ['version_schema' => '2.0'], 'rules' => []], // Schema jump
            'signature' => 'sig2'
        ]);

        $scorer = resolve(GovernanceRiskScorer::class);
        $risk = $scorer->calculate($v2);

        $this->assertEquals(GovernanceRiskScorer::RISK_CRITICAL, $risk['level']);
        $this->assertGreaterThanOrEqual(80, $risk['score']);
    }

    /** @test */
    public function it_blocks_activation_of_critical_risk_versions()
    {
         $v1 = PropertyConfigVersion::create([
            'version_hash' => 'v1',
            'yonetim_durumu' => VersionStateMachine::DURUM_AKTIF,
            'snapshot_json' => ['meta' => ['version_schema' => '1.0'], 'rules' => []],
            'signature' => 'sig1'
        ]);

        $v2 = PropertyConfigVersion::create([
            'version_hash' => 'v2',
            'yonetim_durumu' => VersionStateMachine::DURUM_ONAYLANDI, // Move to ONAYLANDI first
            'snapshot_json' => ['meta' => ['version_schema' => '2.0'], 'rules' => []],
            'signature' => 'sig2'
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('GOVERNANCE CRITICAL: Activation blocked');

        $sm = resolve(VersionStateMachine::class);
        $sm->assertTransition($v2, VersionStateMachine::DURUM_AKTIF);
    }

    /** @test */
    public function it_requires_dual_approval_for_high_risk_versions()
    {
        // Setup massive rule change to trigger HIGH risk
        $v1 = PropertyConfigVersion::create([
            'version_hash' => 'v1',
            'yonetim_durumu' => VersionStateMachine::DURUM_AKTIF,
            'snapshot_json' => ['meta' => ['version_schema' => '1.0'], 'rules' => []],
            'signature' => 'sig1'
        ]);

        $rules = [];
        for($i=0; $i<15; $i++) { $rules[] = ['id' => $i]; } // 15 rules delta = 75 score (HIGH)

        $v2 = PropertyConfigVersion::create([
            'version_hash' => 'v2',
            'yonetim_durumu' => VersionStateMachine::DURUM_ONAYLANDI, // Move to ONAYLANDI first
            'snapshot_json' => ['meta' => ['version_schema' => '1.0'], 'rules' => $rules],
            'signature' => 'sig2',
            'is_approved_by_dual_control' => false
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('GOVERNANCE ALERT: High risk configuration requires dual-approval');

        $sm = resolve(VersionStateMachine::class);
        $sm->assertTransition($v2, VersionStateMachine::DURUM_AKTIF);
    }

    /** @test */
    public function it_allows_activation_of_high_risk_if_dual_approved()
    {
         $v1 = PropertyConfigVersion::create([
            'version_hash' => 'v1',
            'yonetim_durumu' => VersionStateMachine::DURUM_AKTIF,
            'snapshot_json' => ['meta' => ['version_schema' => '1.0'], 'rules' => []],
            'signature' => 'sig1'
        ]);

        $rules = [];
        for($i=0; $i<15; $i++) { $rules[] = ['id' => $i]; }

        $v2 = PropertyConfigVersion::create([
            'version_hash' => 'v2',
            'yonetim_durumu' => VersionStateMachine::DURUM_ONAYLANDI, // Move to ONAYLANDI first
            'snapshot_json' => ['meta' => ['version_schema' => '1.0'], 'rules' => $rules],
            'signature' => 'sig2',
            'is_approved_by_dual_control' => true // APPROVED
        ]);

        $sm = resolve(VersionStateMachine::class);
        // Should not throw exception
        $sm->assertTransition($v2, VersionStateMachine::DURUM_AKTIF);
        $this->assertTrue(true);
    }
}
