<?php

namespace App\Models\Hermes;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
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
    use HasCountryScope;

    protected $table = 'hermes_event_logs';

    protected $fillable = [
        'event_name',
        'event_class',
        'payload',
        'tenant_id',
        'occurred_at',
        'processed_at',
        'status', // context7-ignore
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
    public const STATUS_RECEIVED = 'received'; // context7-ignore
    public const STATUS_PROCESSING = 'processing'; // context7-ignore
    public const STATUS_PROCESSED = 'processed'; // context7-ignore
    public const STATUS_FAILED = 'failed'; // context7-ignore

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
            'status' => self::STATUS_PROCESSING, // context7-ignore
        ]);
        return $this;
    }

    /**
     * Mark as processed
     */
    public function markProcessed(array $handlerResults = []): self
    {
        $this->update([
            'status' => self::STATUS_PROCESSED, // context7-ignore
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
            'status' => self::STATUS_FAILED, // context7-ignore
            'processed_at' => now(),
            'error_message' => $errorMessage,
        ]);
        return $this;
    }
}
