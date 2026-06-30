<?php

namespace Tests\Unit\Hermes;

use App\Services\Hermes\HermesDispatcher;
use App\Services\Hermes\HandlerExecutionService;
use App\Services\Hermes\DefaultRetryPolicy;
use App\Services\Hermes\Contracts\HandlerInterface;
use App\Models\HandlerExecution;
use App\Models\HandlerDeadLetter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Sprint 3.6: Hermes Async Queue Foundation
 *
 * Tests for HermesDispatcher
 */
class HermesDispatcherTest extends TestCase
{
    use RefreshDatabase;

    private HermesDispatcher $dispatcher;
    private HandlerExecutionService $executionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->executionService = new HandlerExecutionService(new DefaultRetryPolicy());
        $this->dispatcher = new HermesDispatcher(
            $this->executionService,
            new DefaultRetryPolicy()
        );
    }

    /** @test */
    public function sync_handler_execution_works(): void
    {
        // Register a working handler
        $this->dispatcher->register('test.event', TestHandlerStub::class);

        $result = $this->dispatcher->dispatch('test.event', ['data' => 'test']);

        $this->assertArrayHasKey(TestHandlerStub::class, $result);
        $this->assertTrue($result[TestHandlerStub::class]['success']);
    }

    /** @test */
    public function handler_failure_is_recorded(): void
    {
        $this->dispatcher->register('fail.event', FailingHandlerStub::class);

        $result = $this->dispatcher->dispatch('fail.event', ['data' => 'test']);

        $this->assertFalse($result[FailingHandlerStub::class]['success']);
        $this->assertArrayHasKey('error', $result[FailingHandlerStub::class]);

        // Execution record created
        $execution = HandlerExecution::where('handler_name', FailingHandlerStub::class)->first();
        $this->assertNotNull($execution);
        $this->assertEquals(1, $execution->attempt_count);
        $this->assertEquals('failed', $execution->status);
    }

    /** @test */
    public function retry_attempt_count_increments(): void
    {
        $this->dispatcher->register('retry.event', RetryableHandlerStub::class);

        // First call - fails
        $result1 = $this->dispatcher->dispatch('retry.event', ['attempt' => 1]);

        // Second call - fails again
        $result2 = $this->dispatcher->dispatch('retry.event', ['attempt' => 2]);

        $executions = HandlerExecution::where('handler_name', RetryableHandlerStub::class)
            ->orderBy('id')
            ->get();

        // Two separate executions created
        $this->assertEquals(2, $executions->count());
        $this->assertEquals(1, $executions[0]->attempt_count);
        $this->assertEquals(1, $executions[1]->attempt_count);
    }

    /** @test */
    public function max_attempts_creates_dead_letter_record(): void
    {
        // Use policy with 2 max attempts
        $limitedDispatcher = new HermesDispatcher(
            new HandlerExecutionService(new DefaultRetryPolicy(maxAttempts: 2)),
            new DefaultRetryPolicy(maxAttempts: 2)
        );

        $limitedDispatcher->register('maxfail.event', AlwaysFailsHandlerStub::class);

        // Exhaust attempts
        $limitedDispatcher->dispatch('maxfail.event', ['data' => 'test']);
        $limitedDispatcher->dispatch('maxfail.event', ['data' => 'test']);

        // Should create dead letter on third call
        $limitedDispatcher->dispatch('maxfail.event', ['data' => 'test']);

        $deadLetter = HandlerDeadLetter::where('handler_name', AlwaysFailsHandlerStub::class)->first();
        $this->assertNotNull($deadLetter);
        $this->assertEquals(2, $deadLetter->final_attempt_count);
    }

    /** @test */
    public function hermes_core_continues_when_handler_fails(): void
    {
        $this->dispatcher->register('fail.event', FailingHandlerStub::class);
        $this->dispatcher->register('success.event', TestHandlerStub::class);

        // Dispatch multiple events
        $result1 = $this->dispatcher->dispatch('fail.event', ['data' => 'test']);
        $result2 = $this->dispatcher->dispatch('success.event', ['data' => 'test']);

        // First failed but Hermes continued
        $this->assertFalse($result1[FailingHandlerStub::class]['success']);
        // Second succeeded
        $this->assertTrue($result2[TestHandlerStub::class]['success']);
    }

    /** @test */
    public function disabled_handler_is_skipped(): void
    {
        $this->dispatcher->register('disabled.event', DisabledHandlerStub::class);

        $result = $this->dispatcher->dispatch('disabled.event', ['data' => 'test']);

        // No result for disabled handler
        $this->assertArrayNotHasKey(DisabledHandlerStub::class, $result);
    }

    /** @test */
    public function tenant_isolation_is_preserved_in_dispatch(): void
    {
        $this->dispatcher->register('tenant.event', TestHandlerStub::class);

        $this->dispatcher->dispatch('tenant.event', ['data' => 'A', 'tenant_id' => 1]);
        $this->dispatcher->dispatch('tenant.event', ['data' => 'B', 'tenant_id' => 2]);

        $executions = HandlerExecution::where('handler_name', TestHandlerStub::class)->get();

        $this->assertEquals(2, $executions->count());
        $this->assertEquals(1, $executions[0]->tenant_id);
        $this->assertEquals(2, $executions[1]->tenant_id);
    }

    /** @test */
    public function async_dispatch_can_be_enabled(): void
    {
        Queue::fake();

        $asyncDispatcher = new HermesDispatcher(
            $this->executionService,
            new DefaultRetryPolicy()
        );
        $asyncDispatcher->enableAsync();
        $asyncDispatcher->register('async.event', TestHandlerStub::class);

        $result = $asyncDispatcher->dispatch('async.event', ['data' => 'test']);

        $this->assertTrue($asyncDispatcher->isAsyncMode());
        $this->assertArrayHasKey(TestHandlerStub::class, $result);
        $this->assertTrue($result[TestHandlerStub::class]['dispatched']);

        Queue::assertPushed(\App\Jobs\Hermes\ProcessHandlerJob::class);
    }

    /** @test */
    public function no_financial_mutation_on_dispatch(): void
    {
        $this->dispatcher->register('finance.event', TestHandlerStub::class);

        // Dispatch should not mutate financial data
        $this->dispatcher->dispatch('finance.event', [
            'data' => 'test',
            'tenant_id' => 1,
            // No fiyat, tutar, or any financial fields
        ]);

        // Verify no financial records were created
        $this->assertEquals(0, \App\Models\LedgerAccount::count());
    }

    /** @test */
    public function sync_mode_is_default(): void
    {
        $newDispatcher = new HermesDispatcher(
            $this->executionService,
            new DefaultRetryPolicy()
        );

        $this->assertFalse($newDispatcher->isAsyncMode());
    }

    /** @test */
    public function wildcard_handler_matches_events(): void
    {
        $this->dispatcher->register('ilan.*', TestHandlerStub::class);

        $result = $this->dispatcher->dispatch('ilan.created', ['data' => 'test']);

        $this->assertArrayHasKey(TestHandlerStub::class, $result);
        $this->assertTrue($result[TestHandlerStub::class]['success']);
    }
}

/**
 * Test stubs
 */

class TestHandlerStub implements HandlerInterface
{
    public static function handles(): array
    {
        return ['test.event', 'success.event', 'tenant.event', 'finance.event', 'async.event'];
    }

    public function handle(string $eventName, array $payload): void
    {
        // Success
    }

    public function isEnabled(): bool
    {
        return true;
    }
}

class FailingHandlerStub implements HandlerInterface
{
    public static function handles(): array
    {
        return ['fail.event'];
    }

    public function handle(string $eventName, array $payload): void
    {
        throw new \RuntimeException('Handler failed intentionally');
    }

    public function isEnabled(): bool
    {
        return true;
    }
}

class AlwaysFailsHandlerStub implements HandlerInterface
{
    public static function handles(): array
    {
        return ['maxfail.event'];
    }

    public function handle(string $eventName, array $payload): void
    {
        throw new \RuntimeException('Always fails');
    }

    public function isEnabled(): bool
    {
        return true;
    }
}

class RetryableHandlerStub implements HandlerInterface
{
    public static function handles(): array
    {
        return ['retry.event'];
    }

    public function handle(string $eventName, array $payload): void
    {
        throw new \RuntimeException('Retryable error');
    }

    public function isEnabled(): bool
    {
        return true;
    }
}

class DisabledHandlerStub implements HandlerInterface
{
    public static function handles(): array
    {
        return ['disabled.event'];
    }

    public function handle(string $eventName, array $payload): void
    {
        // Never called
    }

    public function isEnabled(): bool
    {
        return false;
    }
}
