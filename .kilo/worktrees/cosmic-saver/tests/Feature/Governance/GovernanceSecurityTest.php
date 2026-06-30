<?php

namespace Tests\Feature\Governance;

use Tests\TestCase;
use Tests\Fakes\Governance\FakeGovernedEntityRepository;
use App\Contracts\Governance\GovernedEntityRepositoryInterface;
use App\Contracts\Governance\GovernanceServiceInterface;
use App\Services\Governance\GovernanceService;
use App\DataTransferObjects\Governance\PublishPromotedCommand;
use App\DataTransferObjects\Governance\UpdateDraftCommand;
use App\Enums\Governance\GovernanceState;
use App\Enums\Governance\GovernanceActionType;
use App\Models\GovernanceAuditLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use DomainException;

/**
 * 🛡️ GovernanceSecurityTest
 * Final adversarial and idempotency verification for Production Seal (SAB).
 */
class GovernanceSecurityTest extends TestCase
{
    use DatabaseTransactions;

    private GovernanceServiceInterface $service;
    private FakeGovernedEntityRepository $fakeRepo;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fakeRepo = new FakeGovernedEntityRepository();
        $this->app->instance(GovernedEntityRepositoryInterface::class, $this->fakeRepo);
        $this->app->bind(GovernanceServiceInterface::class, GovernanceService::class);
        
        $this->service = $this->app->make(GovernanceServiceInterface::class);
    }

    /**
     * ADVERSARIAL: Immutable state violation (Published -> Draft mutation)
     */
    public function test_cannot_update_payload_in_published_state(): void
    {
        $this->fakeRepo->seed('TestEntity', 99, GovernanceState::PUBLISHED->value);
        $correlationId = 'adv-security-1';

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Immutable state violation');

        try {
            $this->service->updateDraft(new UpdateDraftCommand('TestEntity', 99, ['hacked' => true], null, $correlationId));
        } finally {
            $log = GovernanceAuditLog::where('correlation_id', $correlationId)->first();
            $this->assertNotNull($log, 'Audit log must record the rejection.');
            $this->assertEquals(GovernanceActionType::TRANSITION_REJECTED->value, $log->action_type);
        }
    }

    /**
     * IDEMPOTENCY: Double Publish (Success -> Rejection of redundant call)
     */
    public function test_double_publish_is_rejected_on_second_call(): void
    {
        $this->fakeRepo->seed('TestEntity', 100, GovernanceState::PROMOTED->value);
        
        // 1st Call: Success
        $this->service->publish(new PublishPromotedCommand('TestEntity', 100, null, 'idemp-1'));
        $this->assertEquals(GovernanceState::PUBLISHED->value, $this->fakeRepo->currentState('TestEntity', 100));

        // 2nd Call: Rejected (Already Published)
        $this->expectException(DomainException::class);
        
        $this->service->publish(new PublishPromotedCommand('TestEntity', 100, null, 'idemp-2'));
    }

    /**
     * ADVERSARIAL: Invalid jump (Draft -> Published directly)
     */
    public function test_direct_jump_from_draft_to_published_is_blocked(): void
    {
        $this->fakeRepo->seed('TestEntity', 101, GovernanceState::DRAFT->value);
        
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid governance transition');

        $this->service->publish(new PublishPromotedCommand('TestEntity', 101, null, 'adv-jump-1'));
    }
}
