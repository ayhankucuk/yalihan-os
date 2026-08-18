<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ChannelSyncExecution — Immutable sync execution record
 *
 * Sprint 13 E03: Per-Channel Execution Isolation
 *
 * Represents a single availability synchronization execution.
 * This is an immutable record — never update after creation.
 * Replay creates a NEW execution with a new idempotency key.
 *
 * E03 Change: The `channel` field enables per-channel execution isolation.
 * A single business operation (e.g., reservation block) produces one
 * execution record per registered channel. Each channel's execution is
 * independent — one channel's failure does NOT affect another.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $property_id
 * @property int|null $reservation_id
 * @property string|null $channel (airbnb|booking|...) — NULL = aggregated legacy
 * @property string $operation
 * @property string|null $block_reason
 * @property string $date_range_start
 * @property string $date_range_end
 * @property bool $target_availability
 * @property array $synced_dates
 * @property array $conflicts
 * @property string $idempotency_key
 * @property string $correlation_id
 * @property string $status (dispatched|processing|completed|completed_with_conflicts|failed|retry_exhausted)
 * @property int $attempts
 * @property int|null $synced_count
 * @property string|null $error_message
 * @property \Carbon\Carbon|null $processed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ChannelSyncExecution extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'channel_sync_executions';

    protected $fillable = [
        'tenant_id',
        'property_id',
        'reservation_id',
        'channel',
        'operation',
        'block_reason',
        'date_range_start',
        'date_range_end',
        'target_availability',
        'synced_dates',
        'conflicts',
        'idempotency_key',
        'correlation_id',
        'status',
        'attempts',
        'synced_count',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'target_availability' => 'boolean',
        'synced_dates' => 'array',
        'conflicts' => 'array',
        'processed_at' => 'datetime',
        'synced_count' => 'integer',
        'attempts' => 'integer',
    ];

    /**
     * Mark execution as processed
     */
    public function markProcessed(int $syncedCount, array $conflicts = []): void
    {
        $this->update([
            'status' => empty($conflicts) ? 'completed' : 'completed_with_conflicts',
            'synced_count' => $syncedCount,
            'conflicts' => $conflicts,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark execution as failed (single attempt threw an exception)
     */
    public function markFailed(string $errorMessage, int $attempts = 1): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'attempts' => $attempts,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark execution as retry_exhausted — all job attempts exhausted.
     *
     * SAAB Decision 4.6 — D4.6-E: Retry Exhaustion Protocol.
     * Called by SynchronizeAvailabilityJob::failed() after $tries is exhausted.
     */
    public function markRetryExhausted(string $errorMessage, int $attempts): void
    {
        $this->update([
            'status' => 'retry_exhausted',
            'error_message' => $errorMessage,
            'attempts' => $attempts,
            'processed_at' => now(),
        ]);
    }

    /**
     * Scope: pending executions
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['dispatched', 'processing']);
    }

    /**
     * Scope: for a specific property
     */
    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    /**
     * Scope: by idempotency key
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('idempotency_key', $key);
    }

    /**
     * Scope: by channel
     */
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }
}
