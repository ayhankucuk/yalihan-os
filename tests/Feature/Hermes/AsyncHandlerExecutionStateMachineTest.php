<?php

namespace Tests\Feature\Hermes;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Jobs\Hermes\AsyncHandlerDispatchJob;
use App\Models\Hermes\WorkforceExecutionLog;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * AsyncHandlerExecutionStateMachineTest
 *
 * TASK: HERMES_ASYNC_EXECUTION_STATE_REMEDIATION_02
 *
 * Findings under test:
 *   FINDING A — HERMES-ASYNC-EXECUTION-STATE-MISMATCH
 *   FINDING B — HERMES-ASYNC-QUEUE-UNIQUENESS-NOT-ENFORCED
 *
 * These tests prove the repaired state-machine contract:
 *
 *   1. PENDING → RUNNING happens BEFORE the handler observes the record.
 *   2. Handler sees status = RUNNING when invoked (via probe).
 *   3. Success transitions the SAME record RUNNING → COMPLETED.
 *   4. COMPLETED record does not execute handler again.
 *   5. SKIPPED record does not execute handler again.
 *   6. Concurrent claim contention: only the first claimer runs the handler;
 *      a second job on the same RUNNING record is a bounded no-op.
 *   7. Failure semantics: failed() transitions PENDING/RUNNING → FAILED.
 *   8. hermes_event_log_id linkage is preserved.
 *   9. agent_class identity is preserved.
 *  10. Queue routing remains 'hermes'.
 *  11. Retry/backoff/timeout constants unchanged.
 *  12. ShouldBeUnique is implemented; uniqueId is stable for same logical
 *      execution and its constituent identity fields.
 *  13. Different logical events (event_log_id / event name) do NOT collide.
 *  14. Null hermesEventLogId falls back to a stable payload-derived
 *      fingerprint (unrelated null-id jobs do NOT collapse).
 *  15. Exactly-once EXTERNAL delivery is NOT asserted — residual crash
 *      window is explicit.
 *
 * NO EXTERNAL CALLS. Stub handlers only. No queue driver, no Redis.
 */
class AsyncHandlerExecutionStateMachineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Log::spy();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Test 1 + 2: PENDING → RUNNING BEFORE handler execution
    // ─────────────────────────────────────────────────────────────────────

    public function test_pending_record_transitions_to_running_before_handler_invocation(): void
    {
        StubStateHandler::reset();
        $this->app->bind(StubStateHandler::class, fn () => new StubStateHandler());

        $execLog = $this->createPendingExecLog(StubStateHandler::class);
        $event   = new StubStateEvent('state.test.event', 1, ['x' => 1]);

        $job = new AsyncHandlerDispatchJob(
            StubStateHandler::class,
            $event,
            $execLog->hermes_event_log_id
        );
        $job->handle();

        // Handler must have observed status = RUNNING at invocation time.
        $this->assertNotNull(
            StubStateHandler::$observedStatusAtInvocation,
            'Handler was not invoked'
        );
        $this->assertSame(
            WorkforceExecutionLog::STATUS_RUNNING,
            StubStateHandler::$observedStatusAtInvocation,
            'Handler must observe status = RUNNING when invoked'
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // Test 3: Same record RUNNING → COMPLETED
    // ─────────────────────────────────────────────────────────────────────

    public function test_success_transitions_same_record_to_completed(): void
    {
        StubStateHandler::reset();
        $this->app->bind(StubStateHandler::class, fn () => new StubStateHandler());

        $execLog = $this->createPendingExecLog(StubStateHandler::class);
        $event   = new StubStateEvent('state.test.event', 1, ['x' => 2]);
        $originalId = $execLog->id;

        (new AsyncHandlerDispatchJob(
            StubStateHandler::class,
            $event,
            $execLog->hermes_event_log_id
        ))->handle();

        $fresh = WorkforceExecutionLog::find($originalId);
        $this->assertNotNull($fresh);
        $this->assertSame(WorkforceExecutionLog::STATUS_COMPLETED, $fresh->status);
        $this->assertNotNull($fresh->completed_at);
        $this->assertSame($execLog->hermes_event_log_id, $fresh->hermes_event_log_id);
        $this->assertSame(StubStateHandler::class, $fresh->agent_class);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Test 4: COMPLETED record does not execute handler again
    // ─────────────────────────────────────────────────────────────────────

    public function test_completed_execution_does_not_execute_handler_again(): void
    {
        StubStateHandler::reset();
        $this->app->bind(StubStateHandler::class, fn () => new StubStateHandler());

        $execLog = $this->createPendingExecLog(StubStateHandler::class);
        $execLog->update(['status' => WorkforceExecutionLog::STATUS_COMPLETED]);

        $event = new StubStateEvent('state.test.event', 1, ['x' => 3]);
        (new AsyncHandlerDispatchJob(
            StubStateHandler::class,
            $event,
            $execLog->hermes_event_log_id
        ))->handle();

        $this->assertSame(0, StubStateHandler::$invocationCount);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Test 5: SKIPPED record does not execute handler again
    // ─────────────────────────────────────────────────────────────────────

    public function test_skipped_execution_does_not_execute_handler_again(): void
    {
        StubStateHandler::reset();
        $this->app->bind(StubStateHandler::class, fn () => new StubStateHandler());

        $execLog = $this->createPendingExecLog(StubStateHandler::class);
        $execLog->update(['status' => WorkforceExecutionLog::STATUS_SKIPPED]);

        $event = new StubStateEvent('state.test.event', 1, ['x' => 4]);
        (new AsyncHandlerDispatchJob(
            StubStateHandler::class,
            $event,
            $execLog->hermes_event_log_id
        ))->handle();

        $this->assertSame(0, StubStateHandler::$invocationCount);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Test 6: Concurrent claim — second job on already-RUNNING record is no-op
    // ─────────────────────────────────────────────────────────────────────

    public function test_second_job_cannot_execute_handler_while_first_claim_owns_running_record(): void
    {
        StubStateHandler::reset();
        $this->app->bind(StubStateHandler::class, fn () => new StubStateHandler());

        // Simulate: worker A already claimed the record (PENDING → RUNNING)
        $execLog = $this->createPendingExecLog(StubStateHandler::class);
        $execLog->update([
            'status'     => WorkforceExecutionLog::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        // Worker B pulls a duplicate job for the same logical execution
        $event = new StubStateEvent('state.test.event', 1, ['x' => 5]);
        (new AsyncHandlerDispatchJob(
            StubStateHandler::class,
            $event,
            $execLog->hermes_event_log_id
        ))->handle();

        // Handler must NOT run — worker A already owns the RUNNING record
        $this->assertSame(0, StubStateHandler::$invocationCount);

        $fresh = WorkforceExecutionLog::find($execLog->id);
        $this->assertSame(WorkforceExecutionLog::STATUS_RUNNING, $fresh->status);
    }

    public function test_atomic_claim_guarantees_only_one_winner_under_direct_contention(): void
    {
        StubStateHandler::reset();
        $execLog = $this->createPendingExecLog(StubStateHandler::class);

        // Two references to the same underlying row
        $a = WorkforceExecutionLog::find($execLog->id);
        $b = WorkforceExecutionLog::find($execLog->id);

        $claimedByA = $a->claimForRun();
        $claimedByB = $b->claimForRun();

        // Exactly one wins the atomic claim.
        $this->assertSame(1, $claimedByA);
        $this->assertSame(0, $claimedByB);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Test 7: Failure semantics — failed() transitions to FAILED
    // ─────────────────────────────────────────────────────────────────────

    public function test_failure_transitions_record_to_failed(): void
    {
        StubStateHandler::reset();

        $execLog = $this->createPendingExecLog(StubStateHandler::class);
        $execLog->update(['status' => WorkforceExecutionLog::STATUS_RUNNING]);

        $job = new AsyncHandlerDispatchJob(
            StubStateHandler::class,
            new StubStateEvent('state.test.event', 1, ['x' => 6]),
            $execLog->hermes_event_log_id
        );
        $job->failed(new \RuntimeException('boom'));

        $fresh = WorkforceExecutionLog::find($execLog->id);
        $this->assertSame(WorkforceExecutionLog::STATUS_FAILED, $fresh->status);
        $this->assertSame('boom', $fresh->error_message);
    }

    public function test_handler_exception_propagates_and_leaves_record_running_for_retry(): void
    {
        StubStateHandler::reset();
        StubStateHandler::$throwOnHandle = new \RuntimeException('handler failed');
        $this->app->bind(StubStateHandler::class, fn () => new StubStateHandler());

        $execLog = $this->createPendingExecLog(StubStateHandler::class);
        $event   = new StubStateEvent('state.test.event', 1, ['x' => 7]);

        $job = new AsyncHandlerDispatchJob(
            StubStateHandler::class,
            $event,
            $execLog->hermes_event_log_id
        );

        $threw = false;
        try {
            $job->handle();
        } catch (\RuntimeException $e) {
            $threw = true;
            $this->assertSame('handler failed', $e->getMessage());
        }
        $this->assertTrue($threw, 'Handler exception must propagate for Laravel retry');

        // The record was claimed (PENDING → RUNNING) but not yet terminal —
        // Laravel's queue driver will retry and eventually call failed().
        $fresh = WorkforceExecutionLog::find($execLog->id);
        $this->assertSame(WorkforceExecutionLog::STATUS_RUNNING, $fresh->status);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Test 8 + 9: Identity preservation
    // ─────────────────────────────────────────────────────────────────────

    public function test_hermes_event_log_id_and_handler_class_preserved_across_success(): void
    {
        StubStateHandler::reset();
        $this->app->bind(StubStateHandler::class, fn () => new StubStateHandler());

        $execLog = $this->createPendingExecLog(StubStateHandler::class);
        $eventLogId = $execLog->hermes_event_log_id;

        $event = new StubStateEvent('state.test.event', 1, ['x' => 8]);
        (new AsyncHandlerDispatchJob(
            StubStateHandler::class,
            $event,
            $eventLogId
        ))->handle();

        $fresh = WorkforceExecutionLog::find($execLog->id);
        $this->assertSame($eventLogId, $fresh->hermes_event_log_id);
        $this->assertSame(StubStateHandler::class, $fresh->agent_class);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Test 10 + 11: Queue routing + retry/timeout unchanged
    // ─────────────────────────────────────────────────────────────────────

    public function test_queue_and_retry_semantics_unchanged(): void
    {
        $event = new StubStateEvent('state.test.event', 1, ['x' => 9]);
        $job   = new AsyncHandlerDispatchJob(StubStateHandler::class, $event, 1);

        $this->assertSame('hermes', $job->queue());
        $this->assertSame(3, $job->tries);
        $this->assertSame(1, $job->maxExceptions);
        $this->assertSame(300, $job->timeout);
        $this->assertSame([10, 60, 300], $job->backoff);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Test 12: ShouldBeUnique + stable uniqueId
    // ─────────────────────────────────────────────────────────────────────

    public function test_job_implements_should_be_unique(): void
    {
        $event = new StubStateEvent('state.test.event', 1, ['x' => 10]);
        $job   = new AsyncHandlerDispatchJob(StubStateHandler::class, $event, 42);

        $this->assertInstanceOf(ShouldBeUnique::class, $job);
        $this->assertSame(1800, $job->uniqueFor);
    }

    public function test_unique_id_is_stable_for_same_logical_execution(): void
    {
        $eventA = new StubStateEvent('state.test.event', 1, ['x' => 11]);
        $eventB = new StubStateEvent('state.test.event', 1, ['x' => 999]); // Different payload

        $jobA = new AsyncHandlerDispatchJob(StubStateHandler::class, $eventA, 42);
        $jobB = new AsyncHandlerDispatchJob(StubStateHandler::class, $eventB, 42);

        // Same (log_id, handler, event name) → same uniqueId regardless of payload.
        $this->assertSame($jobA->uniqueId(), $jobB->uniqueId());
        $this->assertStringContainsString('42', $jobA->uniqueId());
        $this->assertStringContainsString(StubStateHandler::class, $jobA->uniqueId());
        $this->assertStringContainsString('state.test.event', $jobA->uniqueId());
    }

    // ─────────────────────────────────────────────────────────────────────
    // Test 13: Different logical events do NOT collide
    // ─────────────────────────────────────────────────────────────────────

    public function test_different_event_log_ids_produce_different_unique_ids(): void
    {
        $event = new StubStateEvent('state.test.event', 1, ['x' => 12]);
        $jobA  = new AsyncHandlerDispatchJob(StubStateHandler::class, $event, 100);
        $jobB  = new AsyncHandlerDispatchJob(StubStateHandler::class, $event, 200);

        $this->assertNotSame($jobA->uniqueId(), $jobB->uniqueId());
    }

    public function test_different_event_names_produce_different_unique_ids(): void
    {
        $eventA = new StubStateEvent('state.test.event.a', 1, ['x' => 13]);
        $eventB = new StubStateEvent('state.test.event.b', 1, ['x' => 13]);

        $jobA = new AsyncHandlerDispatchJob(StubStateHandler::class, $eventA, 42);
        $jobB = new AsyncHandlerDispatchJob(StubStateHandler::class, $eventB, 42);

        $this->assertNotSame($jobA->uniqueId(), $jobB->uniqueId());
    }

    // ─────────────────────────────────────────────────────────────────────
    // Test 14: Null hermesEventLogId — payload fingerprint isolation
    // ─────────────────────────────────────────────────────────────────────

    public function test_null_event_log_id_does_not_collapse_unrelated_jobs(): void
    {
        $eventA = new StubStateEvent('state.test.event', 1, ['x' => 14, 'y' => 'a']);
        $eventB = new StubStateEvent('state.test.event', 1, ['x' => 14, 'y' => 'b']);

        $jobA = new AsyncHandlerDispatchJob(StubStateHandler::class, $eventA, null);
        $jobB = new AsyncHandlerDispatchJob(StubStateHandler::class, $eventB, null);

        // Same handler + event name but distinct payloads must produce
        // distinct uniqueIds — otherwise Laravel would drop the second job.
        $this->assertNotSame($jobA->uniqueId(), $jobB->uniqueId());
        $this->assertStringStartsWith('no-log-', $jobA->uniqueId());
        $this->assertStringStartsWith('no-log-', $jobB->uniqueId());
    }

    public function test_null_event_log_id_is_stable_for_identical_events(): void
    {
        $event = new StubStateEvent('state.test.event', 1, ['x' => 15]);
        $jobA  = new AsyncHandlerDispatchJob(StubStateHandler::class, $event, null);
        $jobB  = new AsyncHandlerDispatchJob(StubStateHandler::class, $event, null);

        $this->assertSame($jobA->uniqueId(), $jobB->uniqueId());
    }

    public function test_null_event_log_id_executes_handler_without_tracking(): void
    {
        StubStateHandler::reset();
        $this->app->bind(StubStateHandler::class, fn () => new StubStateHandler());

        $before = WorkforceExecutionLog::count();

        $event = new StubStateEvent('state.test.event', 1, ['x' => 16]);
        (new AsyncHandlerDispatchJob(StubStateHandler::class, $event, null))->handle();

        $this->assertSame(1, StubStateHandler::$invocationCount);

        // No new record should have been written by the job for the null-id path.
        $this->assertSame($before, WorkforceExecutionLog::count());
    }

    // ─────────────────────────────────────────────────────────────────────
    // Test 15: Residual external side-effect window — NOT closed
    // ─────────────────────────────────────────────────────────────────────

    public function test_residual_external_side_effect_window_is_documented_not_asserted_closed(): void
    {
        // This test intentionally asserts nothing about external delivery.
        // It exists to record that the state-machine repair does NOT close
        // the crash window between a successful external side effect and the
        // COMPLETED write. That remains HERMES-ASYNC-EXTERNAL-SIDE-EFFECT-
        // NON-ATOMIC-WINDOW.
        $this->assertTrue(true, 'External exactly-once delivery is NOT claimed.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function createPendingExecLog(string $handlerClass): WorkforceExecutionLog
    {
        static $seq = 1;
        $seq++;

        return WorkforceExecutionLog::create([
            'hermes_event_log_id' => 10_000 + $seq,
            'ilan_id'             => null,
            'tenant_id'           => 1,
            'chain_id'            => 'test-chain-' . $seq,
            'agent_name'          => 'stub_state_handler',
            'agent_class'         => $handlerClass,
            'event_received'      => 'state.test.event',
            'event_chain_step'    => 0,
            'input_payload'       => ['seq' => $seq],
            'output_payload'      => [],
            'status'              => WorkforceExecutionLog::STATUS_PENDING,
            'started_at'          => now(),
        ]);
    }
}

// ─────────────────────────────────────────────────────────────────────────
// Named stubs (must be serializable; anonymous classes are not)
// ─────────────────────────────────────────────────────────────────────────

class StubStateEvent implements HermesEventContract
{
    public function __construct(
        private readonly string $name,
        private readonly ?int $tenantId,
        private readonly array $payload,
    ) {}

    public function eventName(): string
    {
        return $this->name;
    }

    public function tenantId(): ?int
    {
        return $this->tenantId;
    }

    public function toPayload(): array
    {
        return $this->payload;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-09-21T12:00:00+03:00');
    }
}

class StubStateHandler implements HermesHandlerContract
{
    public static int $invocationCount = 0;
    public static ?string $observedStatusAtInvocation = null;
    public static ?\Throwable $throwOnHandle = null;

    public static function reset(): void
    {
        self::$invocationCount = 0;
        self::$observedStatusAtInvocation = null;
        self::$throwOnHandle = null;
    }

    public function subscribesTo(): array
    {
        return ['state.test.event', 'state.test.event.a', 'state.test.event.b'];
    }

    public function handle(HermesEventContract $event): array
    {
        self::$invocationCount++;

        // Probe the DB to record what status the record has when the
        // handler is invoked. Proves atomic claim happened BEFORE handler.
        $log = WorkforceExecutionLog::query()
            ->where('agent_class', self::class)
            ->orderByDesc('id')
            ->first();
        self::$observedStatusAtInvocation = $log?->status;

        if (self::$throwOnHandle !== null) {
            throw self::$throwOnHandle;
        }

        return ['handled' => true, 'event' => $event->eventName()];
    }

    public function isAsync(): bool
    {
        return true;
    }
}
