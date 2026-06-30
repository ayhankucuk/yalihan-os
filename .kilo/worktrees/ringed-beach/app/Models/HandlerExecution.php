<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Sprint 3.6: Hermes Async Queue Foundation
 *
 * Tracks handler execution status, attempts, and timing.
 * Supports both sync and async handler execution modes.
 * Tenant-scoped for cross-tenant isolation.
 *
 * @property int $id
 * @property string $handler_name
 * @property string $event_name
 * @property string|null $event_id
 * @property array $event_payload
 * @property string $status
 * @property int $attempt_count
 * @property string|null $error_message
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $finished_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int|null $tenant_id
 */
class HandlerExecution extends BaseModel
{
    use HasFactory, BelongsToTenant;

    protected $table = 'handler_executions';

    protected $fillable = [
        'handler_name',
        'event_name',
        'event_id',
        'event_payload',
        'status',
        'attempt_count',
        'error_message',
        'started_at',
        'finished_at',
        'tenant_id',
    ];

    protected $casts = [
        'event_payload' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'attempt_count' => 'integer',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_DISPATCHED = 'dispatched';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DEAD_LETTER = 'dead_letter';

    /**
     * Check if execution can be retried
     */
    public function canRetry(int $maxAttempts): bool
    {
        return $this->attempt_count < $maxAttempts
            && !in_array($this->status, [self::STATUS_SUCCESS, self::STATUS_DEAD_LETTER]);
    }

    /**
     * Mark as dispatched (async mode)
     */
    public function markDispatched(): self
    {
        $this->update([
            'status' => self::STATUS_DISPATCHED,
        ]);
        return $this;
    }

    /**
     * Mark as running
     */
    public function markRunning(): self
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
        return $this;
    }

    /**
     * Mark as success
     */
    public function markSuccess(): self
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'finished_at' => now(),
        ]);
        return $this;
    }

    /**
     * Mark as failed
     */
    public function markFailed(string $errorMessage): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'finished_at' => now(),
        ]);
        return $this;
    }

    /**
     * Increment attempt count
     */
    public function incrementAttempt(): self
    {
        $this->increment('attempt_count');
        return $this;
    }
}
