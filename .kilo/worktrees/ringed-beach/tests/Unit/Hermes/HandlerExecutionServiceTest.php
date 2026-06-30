<?php

namespace Tests\Unit\Hermes;

use App\Models\HandlerExecution;
use App\Models\HandlerDeadLetter;
use App\Services\Hermes\HandlerExecutionService;
use App\Services\Hermes\DefaultRetryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 3.6: Hermes Async Queue Foundation
 *
 * Tests for HandlerExecutionService
 */
class HandlerExecutionServiceTest extends TestCase
{
    use RefreshDatabase;

    private HandlerExecutionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HandlerExecutionService(new DefaultRetryPolicy());
    }

    /** @test */
    public function creates_execution_record_with_correct_fields(): void
    {
        $execution = $this->service->createExecution(
            handlerName: 'TelegramNotificationHandler',
            eventName: 'ilan.created',
            payload: ['ilan_id' => 123, 'tenant_id' => 1],
            eventId: 'evt_123',
            tenantId: 1
        );

        $this->assertInstanceOf(HandlerExecution::class, $execution);
        $this->assertEquals('TelegramNotificationHandler', $execution->handler_name);
        $this->assertEquals('ilan.created', $execution->event_name);
        $this->assertEquals('evt_123', $execution->event_id);
        $this->assertEquals('pending', $execution->status);
        $this->assertEquals(0, $execution->attempt_count);
        $this->assertEquals(['ilan_id' => 123, 'tenant_id' => 1], $execution->event_payload);
    }

    /** @test */
    public function marks_execution_as_running(): void
    {
        $execution = $this->service->createExecution(
            'TestHandler', 'test.event', ['data' => 'test']
        );

        $this->service->markRunning($execution);
        $execution->refresh();

        $this->assertEquals('running', $execution->status);
        $this->assertNotNull($execution->started_at);
    }

    /** @test */
    public function marks_execution_as_success(): void
    {
        $execution = $this->service->createExecution(
            'TestHandler', 'test.event', ['data' => 'test']
        );

        $this->service->markSuccess($execution);
        $execution->refresh();

        $this->assertEquals('success', $execution->status);
        $this->assertNotNull($execution->finished_at);
    }

    /** @test */
    public function handle_failure_increments_attempt_count(): void
    {
        $execution = $this->service->createExecution(
            'TestHandler', 'test.event', ['data' => 'test']
        );

        $this->service->handleFailure($execution, 'Test error');
        $execution->refresh();

        $this->assertEquals(1, $execution->attempt_count);
        $this->assertEquals('failed', $execution->status);
        $this->assertEquals('Test error', $execution->error_message);
    }

    /** @test */
    public function handle_failure_creates_dead_letter_after_max_attempts(): void
    {
        // Create policy with 2 max attempts
        $service = new HandlerExecutionService(new DefaultRetryPolicy(
            maxAttempts: 2,
            baseDelaySeconds: 1
        ));

        $execution = $this->service->createExecution(
            'TestHandler', 'test.event', ['data' => 'test']
        );

        // First failure - should retry
        $shouldRetry = $service->handleFailure($execution, 'Error 1');
        $this->assertTrue($shouldRetry);
        $this->assertEquals(1, $execution->fresh()->attempt_count);

        // Second failure - should NOT retry, creates dead letter
        $shouldRetry = $service->handleFailure($execution, 'Error 2');
        $this->assertFalse($shouldRetry);

        $execution->refresh();
        $this->assertEquals('dead_letter', $execution->status);

        // Dead letter record created
        $deadLetter = HandlerDeadLetter::where('handler_name', 'TestHandler')->first();
        $this->assertNotNull($deadLetter);
        $this->assertEquals(['data' => 'test'], $deadLetter->event_payload);
        $this->assertEquals(2, $deadLetter->final_attempt_count);
    }

    /** @test */
    public function dead_letter_preserves_original_payload(): void
    {
        $execution = $this->service->createExecution(
            'TestHandler',
            'ilan.created',
            ['ilan_id' => 999, 'fiyat' => 5000000, 'tenant_id' => 1],
            eventId: 'evt_abc'
        );

        // Fail it
        $service = new HandlerExecutionService(new DefaultRetryPolicy(maxAttempts: 1));
        $service->handleFailure($execution, 'Final error');

        $deadLetter = HandlerDeadLetter::first();

        $this->assertEquals(['ilan_id' => 999, 'fiyat' => 5000000, 'tenant_id' => 1], $deadLetter->event_payload);
        $this->assertEquals('evt_abc', $deadLetter->event_id);
        $this->assertEquals('Final error', $deadLetter->last_error_message);
    }

    /** @test */
    public function can_retry_returns_correct_value(): void
    {
        $execution = $this->service->createExecution(
            'TestHandler', 'test.event', ['data' => 'test']
        );

        // No attempts yet - can retry
        $this->assertTrue($this->service->canRetry($execution));

        // After 1 failure with 3 max - can still retry
        $this->service->handleFailure($execution, 'Error');
        $this->assertTrue($this->service->canRetry($execution));

        // After 2 failures with 3 max - can still retry
        $this->service->handleFailure($execution, 'Error');
        $this->assertTrue($this->service->canRetry($execution));

        // After 3 failures - cannot retry
        $this->service->handleFailure($execution, 'Error');
        $this->assertFalse($this->service->canRetry($execution));
    }

    /** @test */
    public function get_retry_delay_uses_exponential_backoff(): void
    {
        $execution = $this->service->createExecution(
            'TestHandler', 'test.event', ['data' => 'test']
        );

        // Policy: base=10s, multiplier=2.0
        $this->assertEquals(10, $this->service->getRetryDelay($execution));

        $this->service->handleFailure($execution, 'Error');
        $this->assertEquals(20, $this->service->getRetryDelay($execution->fresh()));

        $this->service->handleFailure($execution->fresh(), 'Error');
        $this->assertEquals(40, $this->service->getRetryDelay($execution->fresh()));
    }

    /** @test */
    public function prepare_for_retry_resets_status(): void
    {
        $execution = $this->service->createExecution(
            'TestHandler', 'test.event', ['data' => 'test']
        );

        $this->service->handleFailure($execution, 'Error');
        $this->assertEquals('failed', $execution->fresh()->status);

        $this->service->prepareForRetry($execution->fresh());
        $execution->refresh();

        $this->assertEquals('pending', $execution->status);
        $this->assertNull($execution->error_message);
        // Attempt count is NOT reset
        $this->assertEquals(1, $execution->attempt_count);
    }

    /** @test */
    public function tenant_isolation_is_preserved(): void
    {
        $executionA = $this->service->createExecution(
            'Handler', 'event', ['data' => 'A'],
            tenantId: 1
        );

        $executionB = $this->service->createExecution(
            'Handler', 'event', ['data' => 'B'],
            tenantId: 2
        );

        $this->assertEquals(1, $executionA->tenant_id);
        $this->assertEquals(2, $executionB->tenant_id);
        $this->assertNotEquals($executionA->tenant_id, $executionB->tenant_id);
    }
}
