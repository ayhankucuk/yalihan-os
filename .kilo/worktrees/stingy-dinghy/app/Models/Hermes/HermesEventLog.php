<?php

namespace App\Models\Hermes;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * HermesEventLog Model
 *
 * Event log storage for Hermes event bus.
 * Records all events that flow through Hermes for auditing and replay.
 *
 * @sab-context7-table hermes_event_log
 */
class HermesEventLog extends BaseModel
{
    use HasFactory;

    protected $table = 'hermes_event_logs';

    protected $fillable = [
        'event_name',
        'event_class',
        'payload',
        'tenant_id',
        'occurred_at',
        'processed_at',
        'status',
        'handler_results',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'tenant_id' => 'integer',
        'occurred_at' => 'datetime',
        'processed_at' => 'datetime',
        'handler_results' => 'array',
    ];

    /**
     * Event status constants
     */
    public const STATUS_RECEIVED = 'received';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

    /**
     * Scope: tenant isolation
     */
    public function scopeTenant($query, ?int $tenantId)
    {
        if ($tenantId === null) {
            return $query;
        }
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Mark as processing
     */
    public function markProcessing(): self
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
        ]);
        return $this;
    }

    /**
     * Mark as processed
     */
    public function markProcessed(array $handlerResults = []): self
    {
        $this->update([
            'status' => self::STATUS_PROCESSED,
            'processed_at' => now(),
            'handler_results' => $handlerResults,
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
            'processed_at' => now(),
            'error_message' => $errorMessage,
        ]);
        return $this;
    }
}
