<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Sprint 3.6: Hermes Async Queue Foundation
 *
 * Stores failed handler executions that exceeded max retry attempts.
 * Preserves original event payload for manual inspection/replay.
 * Tenant-scoped for cross-tenant isolation.
 *
 * @property int $id
 * @property string $handler_name
 * @property string $event_name
 * @property string|null $event_id
 * @property array $event_payload
 * @property int $final_attempt_count
 * @property string $last_error_message
 * @property \Carbon\Carbon $failed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int|null $tenant_id
 */
class HandlerDeadLetter extends BaseModel
{
    use HasFactory, BelongsToTenant;

    protected $table = 'handler_dead_letters';

    protected $fillable = [
        'handler_name',
        'event_name',
        'event_id',
        'event_payload',
        'final_attempt_count',
        'last_error_message',
        'failed_at',
        'tenant_id',
    ];

    protected $casts = [
        'event_payload' => 'array',
        'failed_at' => 'datetime',
        'final_attempt_count' => 'integer',
    ];

    /**
     * Get original payload for retry
     */
    public function getOriginalPayload(): array
    {
        return $this->event_payload;
    }

    /**
     * Create dead letter from failed execution
     */
    public static function createFromExecution(HandlerExecution $execution, string $lastError): self
    {
        return self::create([
            'handler_name' => $execution->handler_name,
            'event_name' => $execution->event_name,
            'event_id' => $execution->event_id,
            'event_payload' => $execution->event_payload,
            'final_attempt_count' => $execution->attempt_count,
            'last_error_message' => $lastError,
            'failed_at' => now(),
            'tenant_id' => $execution->tenant_id,
        ]);
    }
}
