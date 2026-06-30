<?php

namespace App\Services\Hermes;

use App\Models\HandlerExecution;
use App\Models\HandlerDeadLetter;
use App\Services\Hermes\Contracts\RetryPolicyInterface;
use Illuminate\Support\Facades\Log;

/**
 * Sprint 3.6: Hermes Async Queue Foundation
 *
 * Service for managing handler execution lifecycle.
 * Handles tracking, retry logic, and dead-letter creation.
 */
class HandlerExecutionService
{
    public function __construct(
        private readonly RetryPolicyInterface $retryPolicy,
    ) {}

    /**
     * Create a new execution record
     */
    public function createExecution(
        string $handlerName,
        string $eventName,
        array $payload,
        ?string $eventId = null,
        ?int $tenantId = null,
    ): HandlerExecution {
        return HandlerExecution::create([
            'handler_name' => $handlerName,
            'event_name' => $eventName,
            'event_id' => $eventId,
            'event_payload' => $payload,
            'status' => HandlerExecution::STATUS_PENDING,
            'attempt_count' => 0,
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Mark execution as running
     */
    public function markRunning(HandlerExecution $execution): void
    {
        $execution->markRunning();
    }

    /**
     * Mark execution as success
     */
    public function markSuccess(HandlerExecution $execution): void
    {
        $execution->markSuccess();
    }

    /**
     * Handle execution failure with retry logic
     *
     * @return bool True if should retry, false if max attempts exceeded
     */
    public function handleFailure(HandlerExecution $execution, string $errorMessage): bool
    {
        $execution->incrementAttempt();
        $execution->markFailed($errorMessage);

        $maxAttempts = $this->retryPolicy->getMaxAttempts();

        Log::warning('Hermes: Handler execution failed', [
            'handler' => $execution->handler_name,
            'event' => $execution->event_name,
            'attempt' => $execution->attempt_count,
            'max_attempts' => $maxAttempts,
            'error' => $errorMessage,
        ]);

        if (!$execution->canRetry($maxAttempts)) {
            $this->createDeadLetter($execution, $errorMessage);
            return false;
        }

        return true;
    }

    /**
     * Create dead letter record from failed execution
     */
    public function createDeadLetter(HandlerExecution $execution, string $lastError): HandlerDeadLetter
    {
        $deadLetter = HandlerDeadLetter::createFromExecution($execution, $lastError);

        $execution->update(['status' => HandlerExecution::STATUS_DEAD_LETTER]);

        Log::error('Hermes: Handler moved to dead letter', [
            'handler' => $execution->handler_name,
            'event' => $execution->event_name,
            'total_attempts' => $execution->attempt_count,
        ]);

        return $deadLetter;
    }

    /**
     * Calculate retry delay using exponential backoff
     */
    public function getRetryDelay(HandlerExecution $execution): int
    {
        return $this->retryPolicy->getDelaySeconds($execution->attempt_count);
    }

    /**
     * Check if execution can be retried
     */
    public function canRetry(HandlerExecution $execution): bool
    {
        return $execution->canRetry($this->retryPolicy->getMaxAttempts());
    }

    /**
     * Reset execution for retry (keep original payload, reset status)
     */
    public function prepareForRetry(HandlerExecution $execution): void
    {
        $execution->update([
            'status' => HandlerExecution::STATUS_PENDING,
            'error_message' => null,
        ]);
    }
}
