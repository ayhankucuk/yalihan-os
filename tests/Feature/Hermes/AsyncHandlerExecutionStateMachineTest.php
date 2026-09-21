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
    // Test 16-24: HERMES_ASYNC_RUNNING_LEASE_REMEDIATION_04
    //
    // Regression suite for HERMES-ASYNC-RUNNING-STATE-RETRY-SUPPRESSION.
    //
    // A RUNNING record is NOT proof that a worker is alive. A worker crash
    // between claim and completion previously left the record permanently
    // RUNNING and silently suppressed retries. These tests prove that:
    //
    //   - Fresh RUNNING lease still suppresses duplicate execution.
    //   - Expired RUNNING lease is atomically re-claimable.
    //   - Concurrent recovery attempts produce exactly one winner.
    //   - Recovered stale execution can reach COMPLETED terminally.
    //   - Pre-handler crash is no longer permanent work loss.
    //   - Lease timing does not alter queue/tries/timeout/backoff/uniqueFor.
    //   - Lease duration is strictly bounded relative to $timeout and
    //     $uniqueFor (avoids contradictory recovery windows).
    // ─────────────────────────────────────────────────────────────────────

    public function test_lease_duration_is_bounded_between_timeout_and_unique_for(): void
    {
        // Authority: WorkforceExecutionLog::LEASE_DURATION_SECONDS is derived
        // from AsyncHandlerDispatchJob's queue contract. This test encodes
        // the invariants the derivation must satisfy.
        $job = new AsyncHandlerDispatchJob(
            StubStateHandler::class,
            new StubStateEvent('state.test.event', 1, ['x' => 16]),
            null
        );

        $lease = WorkforceExecutionLog::LEASE_DURATION_SECONDS;

        // Lease MUST exceed the max legitimate single-handler wall-clock so
        // a legitimately running handler is never mistakenly reclaimed.
        $this->assertGreaterThan(
            $job->timeout,
            $lease,
            'LEASE_DURATION_SECONDS must exceed $timeout to avoid reclaiming live handlers'
        );

        // Lease MUST fit inside the unique-lock TTL so stale reclaim is
        // reachable before the unique lock expires and userland re-dispatch
        // could theoretically insert a competing job.
        $this->assertLessThan(
            $job->uniqueFor,
            $lease,
            'LEASE_DURATION_SECONDS must be shorter than $uniqueFor to keep recovery reachable within the unique-lock window'
        );
    }

    public function test_fresh_running_lease_suppresses_duplicate_execution(): void
    {
        StubStateHandler::reset();
        $this->app->bind(StubStateHandler::class, fn () => new StubStateHandler());

        // Worker A claimed the record just now — the lease is live.
        $execLog = $this->createPendingExecLog(StubStateHandler::class);
        $execLog->update([
            'status'     => WorkforceExecutionLog::STATUS_RUNNING,
            'started_at' => now(), // fresh lease
        ]);

        $event = new StubStateEvent('state.test.event', 1, ['x' => 17]);
        (new AsyncHandlerDispatchJob(
            StubStateHandler::class,
            $event,
            $execLog->hermes_event_log_id
        ))->handle();

        // Handler MUST NOT execute — the lease is live, worker A may still be running.
        $this->assertSame(0, StubStateHandler::$invocationCount);

        // Record must remain RUNNING with the fresh started_at unchanged.
        $fresh = WorkforceExecutionLog::find($execLog->id);
        $this->assertSame(WorkforceExecutionLog::STATUS_RUNNING, $fresh->status);
    }

    public function test_expired_running_lease_is_reclaimable_and_executes_handler(): void
    {
        StubStateHandler::reset();
        $this->app->bind(StubStateHandler::class, fn () => new StubStateHandler());

        // Worker A claimed the record long ago and vanished. Lease is stale.
        $execLog = $this->createPendingExecLog(StubStateHandler::class);
        $execLog->update([
            'status'     => WorkforceExecutionLog::STATUS_RUNNING,
            'started_at' => now()->subSeconds(
                WorkforceExecutionLog::LEASE_DURATION_SECONDS + 60
            ),
        ]);

        $event = new StubStateEvent('state.test.event', 1, ['x' => 18]);
        (new AsyncHandlerDispatchJob(
            StubStateHandler::class,
            $event,
            $execLog->hermes_event_log_id
        ))->handle();

        // Handler MUST execute — the stale lease was atomically reclaimed.
        $this->assertSame(1, StubStateHandler::$invocationCount);

        // The record must have progressed to COMPLETED via the same-record
        // markCompleted contract.
        $fresh = WorkforceExecutionLog::find($execLog->id);
        $this->assertSame(WorkforceExecutionLog::STATUS_COMPLETED, $fresh->status);
        $this->assertNotNull($fresh->completed_at);
    }

    public function test_concurrent_stale_reclaim_produces_exactly_one_winner(): void
    {
        StubStateHandler::reset();

        $execLog = $this->createPendingExecLog(StubStateHandler::class);
        $execLog->update([
            'status'     => WorkforceExecutionLog::STATUS_RUNNING,
            'started_at' => now()->subSeconds(
                WorkforceExecutionLog::LEASE_DURATION_SECONDS + 30
            ),
        ]);

        // Two workers race to recover the same stale record.
        $a = WorkforceExecutionLog::find($execLog->id);
        $b = WorkforceExecutionLog::find($execLog->id);

        $reclaimedByA = $a->reclaimIfStale();
        $reclaimedByB = $b->reclaimIfStale();

        // Exactly one wins the atomic compare-and-set.
        $this->assertSame(1, $reclaimedByA);
        $this->assertSame(
            0,
            $reclaimedByB,
            'Second reclaim MUST lose — the lease was refreshed by the first winner'
        );

        // After A's reclaim, B's second attempt sees a fresh lease and loses.
        // The record must remain RUNNING with A's refreshed started_at.
        $fresh = WorkforceExecutionLog::find($execLog->id);
        $this->assertSame(WorkforceExecutionLog::STATUS_RUNNING, $fresh->status);
    }

    public function test_pre_handler_crash_is_no_longer_permanent_work_loss(): void
    {
        // The exact scenario HERMES-ASYNC-RUNNING-STATE-RETRY-SUPPRESSION
        // describes: worker A successfully claimed the record via
        // claimForRun() but died BEFORE invoking the handler. The record is
        // left RUNNING with a stale started_at. Before this remediation,
        // every subsequent retry saw RUNNING and silently no-op'd forever.
        //
        // After this remediation the retry must reclaim the stale lease and
        // execute the handler exactly once.
        StubStateHandler::reset();
        $this->app->bind(StubStateHandler::class, fn () => new StubStateHandler());

        $execLog = $this->createPendingExecLog(StubStateHandler::class);

        // Worker A: successful atomic claim.
        $execLog->update([
            'status'     => WorkforceExecutionLog::STATUS_RUNNING,
            'started_at' => now(),
        ]);
        // ...worker A dies before calling $handler->handle(). No completion.

        // Time passes. The queue eventually redelivers the same job.
        $execLog->update([
            'started_at' => now()->subSeconds(
                WorkforceExecutionLog::LEASE_DURATION_SECONDS + 5
            ),
        ]);

        // Worker B pops the redelivered job.
        $event = new StubStateEvent('state.test.event', 1, ['x' => 19]);
        (new AsyncHandlerDispatchJob(
            StubStateHandler::class,
            $event,
            $execLog->hermes_event_log_id
        ))->handle();

        // Work is recovered exactly once.
        $this->assertSame(
            1,
            StubStateHandler::$invocationCount,
            'Pre-handler crash must be recoverable — handler must execute on retry after lease expiry'
        );

        $fresh = WorkforceExecutionLog::find($execLog->id);
        $this->assertSame(
            WorkforceExecutionLog::STATUS_COMPLETED,
            $fresh->status,
            'Recovered stale execution must reach COMPLETED'
        );
    }

    public function test_live_lease_release_path_does_not_alter_record_status(): void
    {
        StubStateHandler::reset();
        $this->app->bind(StubStateHandler::class, fn () => new StubStateHandler());

        $execLog = $this->createPendingExecLog(StubStateHandler::class);
        $execLog->update([
            'status'     => WorkforceExecutionLog::STATUS_RUNNING,
            'started_at' => now()->subSeconds(30), // well under lease
        ]);
        $originalStartedAt = WorkforceExecutionLog::find($execLog->id)->started_at;

        $event = new StubStateEvent('state.test.event', 1, ['x' => 20]);
        (new AsyncHandlerDispatchJob(
            StubStateHandler::class,
            $event,
            $execLog->hermes_event_log_id
        ))->handle();

        // Neither status nor started_at may change on the live-lease branch.
        // release() is a no-op when $this->job is not bound (unit test path).
        $fresh = WorkforceExecutionLog::find($execLog->id);
        $this->assertSame(WorkforceExecutionLog::STATUS_RUNNING, $fresh->status);
        $this->assertSame(
            $originalStartedAt->toIso8601String(),
            $fresh->started_at->toIso8601String(),
            'Live-lease branch MUST NOT rewrite started_at'
        );
    }

    public function test_reclaim_refreshes_started_at_but_preserves_status(): void
    {
        // The lease anchor moves forward on reclaim, but status stays RUNNING.
        // Any duration measured by markCompleted / markFailed after reclaim
        // reflects the RECOVERED handler run, not the abandoned one.
        $execLog = $this->createPendingExecLog(StubStateHandler::class);
        $staleTime = now()->subSeconds(
            WorkforceExecutionLog::LEASE_DURATION_SECONDS + 120
        );
        $execLog->update([
            'status'     => WorkforceExecutionLog::STATUS_RUNNING,
            'started_at' => $staleTime,
        ]);

        $reclaimed = WorkforceExecutionLog::find($execLog->id)->reclaimIfStale();
        $this->assertSame(1, $reclaimed);

        $fresh = WorkforceExecutionLog::find($execLog->id);
        $this->assertSame(WorkforceExecutionLog::STATUS_RUNNING, $fresh->status);
        $this->assertTrue(
            $fresh->started_at->greaterThan($staleTime),
            'reclaimIfStale must advance started_at forward'
        );
    }

    public function test_reclaim_does_not_promote_terminal_records(): void
    {
        // reclaimIfStale is scoped to RUNNING only. It must never resurrect
        // COMPLETED / FAILED / SKIPPED records into RUNNING even if their
        // started_at happens to be old.
        foreach (
            [
                WorkforceExecutionLog::STATUS_COMPLETED,
                WorkforceExecutionLog::STATUS_FAILED,
                WorkforceExecutionLog::STATUS_SKIPPED,
            ] as $terminalStatus
        ) {
            $execLog = $this->createPendingExecLog(StubStateHandler::class);
            $execLog->update([
                'status'     => $terminalStatus,
                'started_at' => now()->subSeconds(
                    WorkforceExecutionLog::LEASE_DURATION_SECONDS * 10
                ),
            ]);

            $reclaimed = WorkforceExecutionLog::find($execLog->id)->reclaimIfStale();
            $this->assertSame(
                0,
                $reclaimed,
                "reclaimIfStale MUST NOT touch terminal status: {$terminalStatus}"
            );

            $fresh = WorkforceExecutionLog::find($execLog->id);
            $this->assertSame($terminalStatus, $fresh->status);
        }
    }

    public function test_lease_helpers_are_consistent(): void
    {
        // isLeaseExpired() and remainingLeaseSeconds() must agree.
        $execLog = $this->createPendingExecLog(StubStateHandler::class);

        // Fresh claim: lease is live.
        $execLog->update([
            'status'     => WorkforceExecutionLog::STATUS_RUNNING,
            'started_at' => now(),
        ]);
        $fresh = WorkforceExecutionLog::find($execLog->id);
        $this->assertFalse($fresh->isLeaseExpired());
        $this->assertGreaterThan(0, $fresh->remainingLeaseSeconds());

        // Stale claim: lease has expired.
        $execLog->update([
            'started_at' => now()->subSeconds(
                WorkforceExecutionLog::LEASE_DURATION_SECONDS + 10
            ),
        ]);
        $stale = WorkforceExecutionLog::find($execLog->id);
        $this->assertTrue($stale->isLeaseExpired());
        $this->assertSame(0, $stale->remainingLeaseSeconds());
    }

    public function test_lease_remediation_does_not_alter_queue_or_retry_semantics(): void
    {
        // Explicit invariant: the lease remediation MUST NOT change queue
        // routing, tries, timeout, backoff, or uniqueFor. These are the
        // contract that the lease duration itself is derived from.
        $job = new AsyncHandlerDispatchJob(
            StubStateHandler::class,
            new StubStateEvent('state.test.event', 1, ['x' => 21]),
            null
        );

        $this->assertSame('hermes', $job->queue());
        $this->assertSame(3, $job->tries);
        $this->assertSame(1, $job->maxExceptions);
        $this->assertSame(300, $job->timeout);
        $this->assertSame([10, 60, 300], $job->backoff);
        $this->assertSame(1800, $job->uniqueFor);
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
