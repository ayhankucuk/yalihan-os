<?php

namespace Tests\Feature\Governance;

use Tests\TestCase;
use Tests\Fakes\Governance\FakeGovernedEntityRepository;
use App\Contracts\Governance\GovernedEntityRepositoryInterface;
use App\Contracts\Governance\GovernanceServiceInterface;
use App\Services\Governance\GovernanceService;
use App\Services\Governance\GovernanceTransitionGuard;
use App\Contracts\Governance\AuditLoggerInterface;
use App\DataTransferObjects\Governance\PromoteDraftCommand;
use App\DataTransferObjects\Governance\PublishPromotedCommand;
use App\DataTransferObjects\Governance\ArchiveGovernedEntityCommand;
use App\DataTransferObjects\Governance\CreateDraftCommand;
use App\DataTransferObjects\Governance\UpdateDraftCommand;
use App\Enums\Governance\GovernanceActionType;
use App\Enums\Governance\GovernanceState;
use App\Models\GovernanceAuditLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use DomainException;

class GovernanceLifecycleE2ETest extends TestCase
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

    // --- PUBLISH TESTS ---
    public function test_publish_success_from_promoted(): void
    {
        $this->fakeRepo->seed('TestEntity', 2, GovernanceState::PROMOTED->value);
        $correlationId = 'test-publish-ok';
        
        $this->service->publish(new PublishPromotedCommand('TestEntity', 2, null, $correlationId));
        
        $this->assertSame(GovernanceState::PUBLISHED->value, $this->fakeRepo->currentState('TestEntity', 2));
        $log = GovernanceAuditLog::where('correlation_id', $correlationId)->first();
        $this->assertNotNull($log);
        $this->assertEquals(GovernanceActionType::PUBLISHED->value, $log->action_type);
    }

    public function test_publish_rejection_from_draft(): void
    {
        $this->fakeRepo->seed('TestEntity', 3, GovernanceState::DRAFT->value);
        $correlationId = 'test-publish-fail';
        
        $this->expectException(DomainException::class);
        
        try {
            $this->service->publish(new PublishPromotedCommand('TestEntity', 3, null, $correlationId));
        } finally {
            $this->assertSame(GovernanceState::DRAFT->value, $this->fakeRepo->currentState('TestEntity', 3));
            $log = GovernanceAuditLog::where('correlation_id', $correlationId)->first();
            $this->assertNotNull($log);
            $this->assertEquals(GovernanceActionType::TRANSITION_REJECTED->value, $log->action_type);
            $this->assertEquals('publish', $log->payload_snapshot['attempted_transition']);
        }
    }

    // --- ARCHIVE TESTS ---
    public function test_archive_success_from_published(): void
    {
        $this->fakeRepo->seed('TestEntity', 4, GovernanceState::PUBLISHED->value);
        $correlationId = 'test-archive-ok';
        
        $this->service->archive(new ArchiveGovernedEntityCommand('TestEntity', 4, null, $correlationId));
        
        $this->assertSame(GovernanceState::ARCHIVED->value, $this->fakeRepo->currentState('TestEntity', 4));
        $log = GovernanceAuditLog::where('correlation_id', $correlationId)->first();
        $this->assertEquals(GovernanceActionType::ARCHIVED->value, $log->action_type);
    }

    public function test_archive_rejection_from_promoted(): void
    {
        $this->fakeRepo->seed('TestEntity', 5, GovernanceState::PROMOTED->value);
        $correlationId = 'test-archive-fail';
        
        $this->expectException(DomainException::class);
        
        try {
            // "promoted -> archived sadece ihtiyaç kanıtlanırsa" policy implemented in transitionGuard disables this.
            $this->service->archive(new ArchiveGovernedEntityCommand('TestEntity', 5, null, $correlationId));
        } finally {
            $this->assertSame(GovernanceState::PROMOTED->value, $this->fakeRepo->currentState('TestEntity', 5));
            $log = GovernanceAuditLog::where('correlation_id', $correlationId)->first();
            $this->assertEquals(GovernanceActionType::TRANSITION_REJECTED->value, $log->action_type);
        }
    }

    // --- DRAFT MUTATION TESTS ---
    public function test_create_draft_sets_initial_state_and_payload(): void
    {
        $correlationId = 'test-create-ok';
        $payload = ['title' => 'New Draft'];
        
        $this->service->createDraft(new CreateDraftCommand('TestEntity', $payload, null, $correlationId));
        
        $log = GovernanceAuditLog::where('correlation_id', $correlationId)->first();
        $this->assertNotNull($log);
        $this->assertEquals(GovernanceActionType::DRAFT_CREATED->value, $log->action_type);
        
        $this->assertSame(GovernanceState::DRAFT->value, $this->fakeRepo->currentState('TestEntity', $log->entity_id));
    }

    public function test_update_draft_fails_on_published_entity(): void
    {
        // 🚨 ZERO-TRUST MUTATION GUARD TEST 🚨
        $this->fakeRepo->seed('TestEntity', 6, GovernanceState::PUBLISHED->value);
        $correlationId = 'test-update-fail';
        
        $this->expectException(DomainException::class);
        
        try {
            $this->service->updateDraft(new UpdateDraftCommand('TestEntity', 6, ['hacked' => true], null, $correlationId));
        } finally {
             $log = GovernanceAuditLog::where('correlation_id', $correlationId)->first();
             $this->assertEquals(GovernanceActionType::TRANSITION_REJECTED->value, $log->action_type);
             $this->assertEquals('updateDraft', $log->payload_snapshot['attempted_mutation']);
        }
    }
}
