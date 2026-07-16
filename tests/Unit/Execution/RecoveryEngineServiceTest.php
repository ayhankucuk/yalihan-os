<?php

namespace Tests\Unit\Execution;

use App\Models\WorkforceExecution;
use App\Repositories\EloquentExecutionRuntimeRepository;
use App\Repositories\ExecutionRuntimeRepositoryInterface;
use App\Services\Execution\ExecutionRuntimeService;
use App\Services\Execution\RecoveryEngineService;
use App\Services\Listing\YalihanLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

/**
 * Sprint 13B Task 003 — Recovery Engine
 *
 * Quality Gates:
 * - Tests PASS
 * - Integrity PASS
 * - Replay Safety PASS
 * - New Blocking Violations = 0
 */
class RecoveryEngineServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RecoveryEngineService $recovery;
    protected ExecutionRuntimeService $runtime;
    protected EloquentExecutionRuntimeRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip lifecycle guards for factory-based listing creation
        \App\Models\Ilan::$skipPropertyIdGuard = true;
        \App\Services\Listing\YalihanLifecycle::$skipGuards = true;
        \App\Services\Listing\YalihanLifecycle::$isTransitioningCounter = 0;

        // Repository
        $this->repository = new EloquentExecutionRuntimeRepository(new WorkforceExecution());
        $this->app->instance(ExecutionRuntimeRepositoryInterface::class, $this->repository);

        // Mock lifecycle for runtime service
        $lifecycleMock = Mockery::mock(YalihanLifecycle::class);
        $lifecycleMock->shouldReceive('transition')->andReturnUsing(function ($ilan, $state) {
            $ilan->yayin_durumu = $state;
            return $ilan;
        });
        $this->app->instance(YalihanLifecycle::class, $lifecycleMock);

        // Runtime service
        $this->runtime = new ExecutionRuntimeService($this->repository, $lifecycleMock);

        // Recovery service under test
        $this->recovery = new RecoveryEngineService($this->repository, $this->runtime);
    }

    protected function tearDown(): void
    {
        \App\Models\Ilan::$skipPropertyIdGuard = false;
        \App\Services\Listing\YalihanLifecycle::$skipGuards = false;
        \App\Services\Listing\YalihanLifecycle::$isTransitioningCounter = 0;
        Mockery::close();
        parent::tearDown();
    }

    // ══════════════════════════════════════════════════════════════════════
    // Failure Classification
    // ══════════════════════════════════════════════════════════════════════

    /** @test */
    public function transient_errors_are_classified_correctly(): void
    {
        $codes = [
            'TIMEOUT'          => RecoveryEngineService::CLASS_TRANSIENT,
            'CONNECTION_REFUSED' => RecoveryEngineService::CLASS_TRANSIENT,
            'HTTP_500'         => RecoveryEngineService::CLASS_TRANSIENT,
            'HTTP_502'         => RecoveryEngineService::CLASS_TRANSIENT,
            'HTTP_503'         => RecoveryEngineService::CLASS_TRANSIENT,
            'HTTP_429'         => RecoveryEngineService::CLASS_TRANSIENT,
            'DISK_FULL'        => RecoveryEngineService::CLASS_TRANSIENT,
            'MEMORY_ERROR'     => RecoveryEngineService::CLASS_TRANSIENT,
        ];

        foreach ($codes as $code => $expected) {
            $exec = $this->makeFailedExecution(['error_code' => $code]);
            $this->assertEquals(
                $expected,
                $this->recovery->classifyFailure($exec),
                "Error code [{$code}] should be classified as {$expected}"
            );
        }
    }

    /** @test */
    public function permanent_errors_are_classified_correctly(): void
    {
        $codes = [
            'VALIDATION_FAILED'    => RecoveryEngineService::CLASS_PERMANENT,
            'BUSINESS_RULE_VIOLATION' => RecoveryEngineService::CLASS_PERMANENT,
            'INVARIANT_BROKEN'     => RecoveryEngineService::CLASS_PERMANENT,
            'GUARD_FAILED'         => RecoveryEngineService::CLASS_PERMANENT,
            'POLICY_DENIED'        => RecoveryEngineService::CLASS_PERMANENT,
            'UNAUTHORIZED'        => RecoveryEngineService::CLASS_PERMANENT,
            'FORBIDDEN'           => RecoveryEngineService::CLASS_PERMANENT,
            'NOT_FOUND'           => RecoveryEngineService::CLASS_PERMANENT,
            'DUPLICATE_ENTRY'     => RecoveryEngineService::CLASS_PERMANENT,
            'RESOURCE_CONFLICT'   => RecoveryEngineService::CLASS_PERMANENT,
        ];

        foreach ($codes as $code => $expected) {
            $exec = $this->makeFailedExecution(['error_code' => $code]);
            $this->assertEquals(
                $expected,
                $this->recovery->classifyFailure($exec),
                "Error code [{$code}] should be classified as {$expected}"
            );
        }
    }

    /** @test */
    public function config_errors_are_classified_correctly(): void
    {
        $codes = [
            'INVALID_API_KEY'    => RecoveryEngineService::CLASS_CONFIG,
            'MISSING_CREDENTIAL'  => RecoveryEngineService::CLASS_CONFIG,
            'API_KEY_EXPIRED'    => RecoveryEngineService::CLASS_CONFIG,
            'SERVICE_UNAVAILABLE'=> RecoveryEngineService::CLASS_CONFIG,
            'RATE_LIMIT_HIT'    => RecoveryEngineService::CLASS_CONFIG,
        ];

        foreach ($codes as $code => $expected) {
            $exec = $this->makeFailedExecution(['error_code' => $code]);
            $this->assertEquals(
                $expected,
                $this->recovery->classifyFailure($exec),
                "Error code [{$code}] should be classified as {$expected}"
            );
        }
    }

    /** @test */
    public function unknown_errors_default_to_unknown_classification(): void
    {
        $exec = $this->makeFailedExecution(['error_code' => 'RANDOM_ERROR_XYZ']);
        $this->assertEquals(RecoveryEngineService::CLASS_UNKNOWN, $this->recovery->classifyFailure($exec));
    }

    // ══════════════════════════════════════════════════════════════════════
    // Recovery Plan
    // ══════════════════════════════════════════════════════════════════════

    /** @test */
    public function plan_returns_can_retry_false_for_permanent_failure(): void
    {
        $exec = $this->makeFailedExecution([
            'error_code'           => 'VALIDATION_FAILED',
            'failure_classification' => RecoveryEngineService::CLASS_PERMANENT,
            'retry_count'          => 0,
            'max_retries'          => 3,
        ]);

        $plan = $this->recovery->planRecovery($exec);

        $this->assertFalse($plan['can_retry']);
        $this->assertEquals(RecoveryEngineService::CLASS_PERMANENT, $plan['classification']);
        $this->assertNull($plan['next_retry_at']);
    }

    /** @test */
    public function plan_returns_can_retry_true_for_transient_failure_with_retries_remaining(): void
    {
        $exec = $this->makeFailedExecution([
            'error_code'              => 'TIMEOUT',
            'failure_classification'  => RecoveryEngineService::CLASS_TRANSIENT,
            'retry_count'             => 0,
            'max_retries'             => 5,
            'retry_policy'             => RecoveryEngineService::POLICY_EXPONENTIAL,
        ]);

        $plan = $this->recovery->planRecovery($exec);

        $this->assertTrue($plan['can_retry']);
        $this->assertEquals(RecoveryEngineService::CLASS_TRANSIENT, $plan['classification']);
        $this->assertEquals(RecoveryEngineService::POLICY_EXPONENTIAL, $plan['policy']);
        $this->assertEquals(10, $plan['delay_seconds']); // First delay = 10s
        $this->assertNotNull($plan['next_retry_at']);
    }

    /** @test */
    public function plan_returns_can_retry_false_when_retry_count_exceeds_max(): void
    {
        $exec = $this->makeFailedExecution([
            'error_code'              => 'TIMEOUT',
            'failure_classification'  => RecoveryEngineService::CLASS_TRANSIENT,
            'retry_count'             => 5, // Exhausted
            'max_retries'             => 5,
            'retry_policy'             => RecoveryEngineService::POLICY_EXPONENTIAL,
        ]);

        $plan = $this->recovery->planRecovery($exec);

        $this->assertFalse($plan['can_retry']);
        $this->assertEquals(5, $plan['retry_count']);
        $this->assertEquals(5, $plan['max_retries']);
    }

    /** @test */
    public function plan_uses_exponential_backoff_delays(): void
    {
        $exec = $this->makeFailedExecution([
            'error_code'             => 'HTTP_500',
            'failure_classification' => RecoveryEngineService::CLASS_TRANSIENT,
            'retry_count'            => 0,
            'max_retries'            => 5,
            'retry_policy'           => RecoveryEngineService::POLICY_EXPONENTIAL,
        ]);

        // Attempt 0 → 10s
        $plan = $this->recovery->planRecovery($exec);
        $this->assertEquals(10, $plan['delay_seconds']);

        // Attempt 1 → 60s
        $exec->retry_count = 1;
        $plan = $this->recovery->planRecovery($exec);
        $this->assertEquals(60, $plan['delay_seconds']);

        // Attempt 2 → 300s
        $exec->retry_count = 2;
        $plan = $this->recovery->planRecovery($exec);
        $this->assertEquals(300, $plan['delay_seconds']);

        // Attempt 3 → 900s
        $exec->retry_count = 3;
        $plan = $this->recovery->planRecovery($exec);
        $this->assertEquals(900, $plan['delay_seconds']);

        // Attempt 4 → 3600s (capped)
        $exec->retry_count = 4;
        $plan = $this->recovery->planRecovery($exec);
        $this->assertEquals(3600, $plan['delay_seconds']);
    }

    /** @test */
    public function plan_uses_linear_backoff_delays(): void
    {
        $exec = $this->makeFailedExecution([
            'error_code'             => 'RANDOM_ERROR_XYZ',
            'failure_classification' => RecoveryEngineService::CLASS_UNKNOWN,
            'retry_count'            => 0,
            'max_retries'            => 4,
            'retry_policy'           => RecoveryEngineService::POLICY_LINEAR,
        ]);

        // Attempt 0 → 30s
        $plan = $this->recovery->planRecovery($exec);
        $this->assertEquals(30, $plan['delay_seconds']);

        // Attempt 1 → 60s
        $exec->retry_count = 1;
        $plan = $this->recovery->planRecovery($exec);
        $this->assertEquals(60, $plan['delay_seconds']);

        // Attempt 2 → 120s
        $exec->retry_count = 2;
        $plan = $this->recovery->planRecovery($exec);
        $this->assertEquals(120, $plan['delay_seconds']);

        // Attempt 3 → 300s
        $exec->retry_count = 3;
        $plan = $this->recovery->planRecovery($exec);
        $this->assertEquals(300, $plan['delay_seconds']);
    }

    // ══════════════════════════════════════════════════════════════════════
    // Auto-Recovery
    // ══════════════════════════════════════════════════════════════════════

    /** @test */
    public function recover_creates_new_execution_and_does_not_mutate_failed(): void
    {
        $ilan = \App\Models\Ilan::factory()->create(['tenant_id' => 1]);

        // Create and fail an execution
        $result = $this->runtime->startExecution(
            aggregateType: 'Ilan',
            aggregateId: $ilan->id,
            capability: 'publish',
            triggerType: WorkforceExecution::TRIGGER_MANUAL,
            tenantId: 1,
        );
        $failed = $this->runtime->failExecution(
            $result['execution']->uuid,
            'HTTP_503',
            'Service temporarily unavailable',
        );

        // Annotate classification
        $annotated = $this->recovery->annotateClassification($failed->uuid, RecoveryEngineService::CLASS_TRANSIENT);

        $originalErrorCode = $failed->error_code;
        $originalStatus = $failed->execution_status;

        // Recover
        $recovery = $this->recovery->recover(
            failedExecutionUuid: $failed->uuid,
            actorId: 1,
            recoveryReason: 'Auto-recovery after HTTP 503',
        );

        // New UUID
        $this->assertNotEquals($failed->uuid, $recovery->uuid);

        // Original unchanged
        $original = WorkforceExecution::where('uuid', $failed->uuid)->first();
        $this->assertEquals($originalErrorCode, $original->error_code);
        $this->assertEquals($originalStatus, $original->execution_status);
        $this->assertEquals('FAILED', $original->execution_status); // Still FAILED
    }

    /** @test */
    public function recover_sets_recovery_metadata_on_new_execution(): void
    {
        $ilan = \App\Models\Ilan::factory()->create(['tenant_id' => 1]);

        $result = $this->runtime->startExecution(
            aggregateType: 'Ilan', aggregateId: $ilan->id,
            capability: 'publish', triggerType: WorkforceExecution::TRIGGER_MANUAL,
            tenantId: 1,
        );
        $failed = $this->runtime->failExecution(
            $result['execution']->uuid, 'HTTP_500', 'Internal server error',
        );
        $this->recovery->annotateClassification($failed->uuid, RecoveryEngineService::CLASS_TRANSIENT);

        $recovery = $this->recovery->recover($failed->uuid, actorId: 1);

        // Recovery metadata set
        $this->assertEquals($failed->uuid, $recovery->recovery_of_uuid);
        $this->assertEquals(RecoveryEngineService::CLASS_TRANSIENT, $recovery->failure_classification);
        $this->assertEquals(1, $recovery->retry_count);
        $this->assertNotNull($recovery->recovered_at);

        // Replay relationship
        $this->assertEquals($failed->uuid, $recovery->replay_of_uuid);
        $this->assertEquals(WorkforceExecution::TRIGGER_REPLAY, $recovery->trigger_type);
    }

    /** @test */
    public function recover_throws_when_execution_not_found(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not found');

        $this->recovery->recover('non-existent-uuid', actorId: 1);
    }

    /** @test */
    public function recover_throws_when_execution_not_failed(): void
    {
        $ilan = \App\Models\Ilan::factory()->create(['tenant_id' => 1]);

        $result = $this->runtime->startExecution(
            aggregateType: 'Ilan', aggregateId: $ilan->id,
            capability: 'publish', triggerType: WorkforceExecution::TRIGGER_MANUAL,
            tenantId: 1,
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('is not FAILED');

        $this->recovery->recover($result['execution']->uuid, actorId: 1);
    }

    /** @test */
    public function recover_throws_when_retry_exhausted(): void
    {
        $ilan = \App\Models\Ilan::factory()->create(['tenant_id' => 1]);

        $result = $this->runtime->startExecution(
            aggregateType: 'Ilan', aggregateId: $ilan->id,
            capability: 'publish', triggerType: WorkforceExecution::TRIGGER_MANUAL,
            tenantId: 1,
        );
        $failed = $this->runtime->failExecution(
            $result['execution']->uuid, 'VALIDATION_FAILED', 'Validation error',
        );
        $this->recovery->annotateClassification($failed->uuid, RecoveryEngineService::CLASS_PERMANENT);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('cannot be retried');

        $this->recovery->recover($failed->uuid, actorId: 1);
    }

    // ══════════════════════════════════════════════════════════════════════
    // Retry Queue
    // ══════════════════════════════════════════════════════════════════════

    /** @test */
    public function get_ready_for_retry_returns_only_retryable_executions(): void
    {
        $ilan = \App\Models\Ilan::factory()->create(['tenant_id' => 1]);

        // Create several failed executions
        $transient = $this->runtime->startExecution(
            aggregateType: 'Ilan', aggregateId: $ilan->id,
            capability: 'publish', triggerType: WorkforceExecution::TRIGGER_MANUAL,
            tenantId: 1,
        );
        $t1 = $this->runtime->failExecution($transient['execution']->uuid, 'TIMEOUT', 'Timeout');
        $this->recovery->annotateClassification($t1->uuid, RecoveryEngineService::CLASS_TRANSIENT);

        $permanent = $this->runtime->startExecution(
            aggregateType: 'Ilan', aggregateId: $ilan->id,
            capability: 'publish', triggerType: WorkforceExecution::TRIGGER_MANUAL,
            tenantId: 1,
        );
        $p1 = $this->runtime->failExecution($permanent['execution']->uuid, 'VALIDATION', 'Invalid');
        $this->recovery->annotateClassification($p1->uuid, RecoveryEngineService::CLASS_PERMANENT);

        // Transient should be retryable
        $ready = $this->recovery->getReadyForRetry(tenantId: 1);
        $this->assertTrue($ready->contains('uuid', $t1->uuid));

        // Permanent should NOT be in retry queue
        $this->assertFalse($ready->contains('uuid', $p1->uuid));
    }

    /** @test */
    public function get_ready_for_retry_respects_tenant_isolation(): void
    {
        $ilan1 = \App\Models\Ilan::factory()->create(['tenant_id' => 1]);
        $ilan2 = \App\Models\Ilan::factory()->create(['tenant_id' => 2]);

        $exec1 = $this->runtime->startExecution(
            aggregateType: 'Ilan', aggregateId: $ilan1->id,
            capability: 'publish', triggerType: WorkforceExecution::TRIGGER_MANUAL,
            tenantId: 1,
        );
        $f1 = $this->runtime->failExecution($exec1['execution']->uuid, 'TIMEOUT', '');
        $this->recovery->annotateClassification($f1->uuid, RecoveryEngineService::CLASS_TRANSIENT);

        $exec2 = $this->runtime->startExecution(
            aggregateType: 'Ilan', aggregateId: $ilan2->id,
            capability: 'publish', triggerType: WorkforceExecution::TRIGGER_MANUAL,
            tenantId: 2,
        );
        $f2 = $this->runtime->failExecution($exec2['execution']->uuid, 'TIMEOUT', '');
        $this->recovery->annotateClassification($f2->uuid, RecoveryEngineService::CLASS_TRANSIENT);

        // Tenant 1 only sees tenant 1's executions
        $ready1 = $this->recovery->getReadyForRetry(tenantId: 1);
        $this->assertTrue($ready1->contains('uuid', $f1->uuid));
        $this->assertFalse($ready1->contains('uuid', $f2->uuid));

        // Tenant 2 only sees tenant 2's executions
        $ready2 = $this->recovery->getReadyForRetry(tenantId: 2);
        $this->assertTrue($ready2->contains('uuid', $f2->uuid));
        $this->assertFalse($ready2->contains('uuid', $f1->uuid));
    }

    /** @test */
    public function annotate_classification_sets_all_recovery_fields(): void
    {
        $ilan = \App\Models\Ilan::factory()->create(['tenant_id' => 1]);

        $result = $this->runtime->startExecution(
            aggregateType: 'Ilan', aggregateId: $ilan->id,
            capability: 'publish', triggerType: WorkforceExecution::TRIGGER_MANUAL,
            tenantId: 1,
        );
        $failed = $this->runtime->failExecution(
            $result['execution']->uuid, 'UNKNOWN_ERROR', 'Something went wrong',
        );

        $annotated = $this->recovery->annotateClassification(
            $failed->uuid,
            RecoveryEngineService::CLASS_TRANSIENT
        );

        $this->assertEquals(RecoveryEngineService::CLASS_TRANSIENT, $annotated->failure_classification);
        $this->assertEquals(RecoveryEngineService::POLICY_EXPONENTIAL, $annotated->retry_policy);
        $this->assertEquals(5, $annotated->max_retries);
    }

    /** @test */
    public function annotate_classification_throws_on_invalid_classification(): void
    {
        $ilan = \App\Models\Ilan::factory()->create(['tenant_id' => 1]);

        $result = $this->runtime->startExecution(
            aggregateType: 'Ilan', aggregateId: $ilan->id,
            capability: 'publish', triggerType: WorkforceExecution::TRIGGER_MANUAL,
            tenantId: 1,
        );
        $failed = $this->runtime->failExecution($result['execution']->uuid, 'ERR', '');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invalid classification');

        $this->recovery->annotateClassification($failed->uuid, 'INVALID_CLASS');
    }

    // ══════════════════════════════════════════════════════════════════════
    // Helper
    // ══════════════════════════════════════════════════════════════════════

    private function makeFailedExecution(array $attrs = []): WorkforceExecution
    {
        return new WorkforceExecution(array_merge([
            'uuid'                  => \Illuminate\Support\Str::uuid()->toString(),
            'aggregate_type'        => 'Ilan',
            'aggregate_id'          => 1,
            'capability'            => 'publish',
            'trigger_type'          => WorkforceExecution::TRIGGER_MANUAL,
            'tenant_id'             => 1,
            'execution_status'      => WorkforceExecution::STATUS_FAILED,
            'error_code'            => 'TEST_ERROR',
            'error_message'         => 'Test error',
            'retry_count'           => 0,
            'max_retries'           => 3,
            'failure_classification' => null,
            'retry_policy'          => null,
        ], $attrs));
    }
}
