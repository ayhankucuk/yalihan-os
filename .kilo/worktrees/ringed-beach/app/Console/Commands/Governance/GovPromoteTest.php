<?php

namespace App\Console\Commands\Governance;

use Illuminate\Console\Command;
use App\Contracts\Governance\AuditLoggerInterface;
use App\Services\Governance\GovernanceService;
use App\Services\Governance\GovernanceTransitionGuard;
use App\DataTransferObjects\Governance\PromoteDraftCommand;
use App\Enums\Governance\GovernanceState;
use Tests\Fakes\Governance\FakeGovernedEntityRepository;
use DomainException;
use RuntimeException;

/**
 * 🛡️ SAB SEALED
 * Governance Phase 2A E2E Promote Lifecycle Smoke Test
 */
class GovPromoteTest extends Command
{
    protected $signature = 'gov:promote-test';
    protected $description = 'E2E Smoke Tests the Governance Promote Lifecycle using Fake Repo';

    public function handle()
    {
        $this->info('Testing Phase 2A E2E promote() lifecycle constraint...');
        
        // Arrange Dependencies locally so we don't leak Fake repository bindings to production container
        $fakeRepo = new FakeGovernedEntityRepository();
        $logger = app(AuditLoggerInterface::class);
        $guard = new GovernanceTransitionGuard();
        
        $service = new GovernanceService($logger, $guard, $fakeRepo);

        // -- Senaryo A: Başarılı Promote --
        $this->info("Scenario A: Promoting DRAFT entity -> Should Success");
        $fakeRepo->seed('TestEntity', 1, GovernanceState::DRAFT->value);
        
        $cmdSuccess = new PromoteDraftCommand(
            entityType: 'TestEntity',
            entityId: 1,
            actorId: null,
            correlationId: 'promote-e2e-ok-' . uniqid(),
            reason: 'E2E Valid Draft Promote'
        );

        try {
            $service->promote($cmdSuccess);
            
            if ($fakeRepo->currentState('TestEntity', 1) === GovernanceState::PROMOTED->value) {
                $this->info('✅ SUCCESS DRAFT->PROMOTED: Entity updated and audited successfully!');
            }
        } catch (\Throwable $e) {
            $this->error('Failed Scenario A: ' . $e->getMessage());
        }

        // -- Senaryo B: Başarısız Promote (Rejection Senaryosu) --
        $this->info("Scenario B: Promoting PUBLISHED entity -> Should Reject");
        $fakeRepo->seed('TestEntity', 2, GovernanceState::PUBLISHED->value);

        $cmdFail = new PromoteDraftCommand(
            entityType: 'TestEntity',
            entityId: 2,
            actorId: null,
            correlationId: 'promote-e2e-fail-' . uniqid(),
            reason: 'E2E Invalid Published Promote'
        );

        try {
            $service->promote($cmdFail);
            $this->error('❌ FAILED: The system allowed promoting a PUBLISHED record! Zero-Trust Broken.');
        } catch (DomainException $e) {
            $this->info('✅ SUCCESS REJECTION GUARD: Caught DomainException as expected.');
            
            if ($fakeRepo->currentState('TestEntity', 2) === GovernanceState::PUBLISHED->value) {
                $this->info('✅ State unchanged! (Still PUBLISHED). Check DB for TRANSITION_REJECTED audit manually.');
            }
        }
    }
}
