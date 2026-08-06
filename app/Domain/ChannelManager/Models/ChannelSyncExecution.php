<?php

namespace App\Domain\ChannelManager\Models;

use App\Domain\ChannelManager\Enums\Channel;
use App\Domain\ChannelManager\Enums\SyncDirection;
use App\Domain\ChannelManager\Enums\SyncState;
use Illuminate\Database\Eloquent\Model;

/**
 * ChannelSyncExecution — Immutable sync operation record
 *
 * CHANNEL_MANAGER Wave 1: Retry Engine
 *
 * Represents a single sync operation execution.
 * Immutable: once created, cannot be modified.
 * Replay creates a new execution.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $property_id
 * @property string $channel
 * @property string $direction
 * @property string $correlation_id
 * @property string $idempotency_key
 * @property string $state
 * @property int $attempt
 * @property string|null $error_code
 * @property string|null $error_message
 * @property string|null $external_ref
 * @property int $created_at
 * @property int|null $completed_at
 */
class ChannelSyncExecution extends Model
{
    public $timestamps = false;

    protected $table = 'channel_sync_executions';

    protected $fillable = [
        'tenant_id',
        'property_id',
        'channel',
        'direction',
        'correlation_id',
        'idempotency_key',
        'state',
        'attempt',
        'error_code',
        'error_message',
        'external_ref',
        'created_at',
        'completed_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'property_id' => 'integer',
        'attempt' => 'integer',
        'created_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Create a new execution record
     */
    public static function createExecution(
        int $tenantId,
        int $propertyId,
        Channel $channel,
        SyncDirection $direction,
        string $correlationId,
        string $idempotencyKey,
        int $attempt = 1,
    ): self {
        return self::create([
            'tenant_id' => $tenantId,
            'property_id' => $propertyId,
            'channel' => $channel->value,
            'direction' => $direction->value,
            'correlation_id' => $correlationId,
            'idempotency_key' => $idempotencyKey,
            'state' => SyncState::PENDING->value,
            'attempt' => $attempt,
            'error_code' => null,
            'error_message' => null,
            'external_ref' => null,
            'created_at' => now(),
            'completed_at' => null,
        ]);
    }

    /**
     * Mark execution as in progress
     */
    public function markInProgress(): void
    {
        $this->update(['state' => SyncState::IN_PROGRESS->value]);
    }

    /**
     * Mark execution as successful
     */
    public function markSuccess(string $externalRef): void
    {
        $this->update([
            'state' => SyncState::SUCCESS->value,
            'external_ref' => $externalRef,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark execution as failed
     */
    public function markFailed(string $errorCode, string $errorMessage): void
    {
        $this->update([
            'state' => SyncState::FAILED->value,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    /**
     * Increment attempt counter
     */
    public function incrementAttempt(): void
    {
        $this->update(['attempt' => $this->attempt + 1]);
    }

    /**
     * Check if execution is in terminal state
     */
    public function isTerminal(): bool
    {
        return SyncState::from($this->state)->isTerminal();
    }

    /**
     * Check if execution succeeded
     */
    public function isSuccess(): bool
    {
        return SyncState::from($this->state)->isSuccess();
    }

    /**
     * Check if execution failed
     */
    public function isFailed(): bool
    {
        return SyncState::from($this->state)->isFailed();
    }

    /**
     * Check if can retry
     */
    public function canRetry(): bool
    {
        return $this->isFailed() && $this->attempt < 3;
    }

    /**
     * Scope: by tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: by property
     */
    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    /**
     * Scope: pending or in progress
     */
    public function scopeActive($query)
    {
        return $query->whereIn('state', [
            SyncState::PENDING->value,
            SyncState::IN_PROGRESS->value,
        ]);
    }
}
