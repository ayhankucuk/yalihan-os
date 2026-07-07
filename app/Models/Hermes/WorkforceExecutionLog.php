<?php

namespace App\Models\Hermes;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * WorkforceExecutionLog Model
 *
 * Sprint 4.3: AI Workforce Vertical Slice
 *
 * Records each agent execution within the AI workforce chain.
 * Enables dashboard metrics, replay, and audit trail.
 *
 * @sab-context7-table workforce_execution_log
 */
class WorkforceExecutionLog extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'workforce_execution_logs';

    public const UPDATED_AT = null; // Workers don't update once written

    protected $fillable = [
        'hermes_event_log_id',
        'ilan_id',
        'tenant_id',
        'chain_id',
        'agent_name',
        'agent_class',
        'event_received',
        'event_chain_step',
        'input_payload',
        'output_payload',
        'status',
        'error_message',
        'started_at',
        'completed_at',
        'duration_ms',
    ];

    protected $casts = [
        'ilan_id' => 'integer',
        'tenant_id' => 'integer',
        'hermes_event_log_id' => 'integer',
        'input_payload' => 'array',
        'output_payload' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_ms' => 'float',
    ];

    // ─── Status Constants ──────────────────────────────────────────────
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    // ─── Scopes ────────────────────────────────────────────────────────

    public function scopeTenant($query, ?int $tenantId)
    {
        if ($tenantId === null) {
            return $query;
        }
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForChain($query, string $chainId)
    {
        return $query->where('chain_id', $chainId);
    }

    public function scopeForIlan($query, int $ilanId)
    {
        return $query->where('ilan_id', $ilanId);
    }

    public function scopeByAgent($query, string $agentName)
    {
        return $query->where('agent_name', $agentName);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeOrderByChain($query)
    {
        return $query->orderBy('event_chain_step');
    }

    // ─── Helpers ───────────────────────────────────────────────────────

    public function markRunning(): self
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
        return $this;
    }

    public function markCompleted(array $outputPayload = []): self
    {
        $completedAt = now();
        $durationMs = $this->started_at
            ? round($completedAt->diffInMilliseconds($this->started_at, true) / 1000, 2)
            : 0;

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => $completedAt,
            'duration_ms' => $durationMs,
            'output_payload' => $outputPayload,
        ]);
        return $this;
    }

    public function markFailed(string $errorMessage): self
    {
        $completedAt = now();
        $durationMs = $this->started_at
            ? round($completedAt->diffInMilliseconds($this->started_at, true) / 1000, 2)
            : 0;

        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => $completedAt,
            'duration_ms' => $durationMs,
            'error_message' => $errorMessage,
        ]);
        return $this;
    }

    public function markSkipped(string $reason, array $outputPayload = []): self
    {
        $this->update([
            'status' => self::STATUS_SKIPPED,
            'completed_at' => now(),
            'output_payload' => array_merge($outputPayload, ['skip_reason' => $reason]),
        ]);
        return $this;
    }

    /**
     * Check if chain is complete (all steps done)
     */
    public static function isChainComplete(string $chainId): bool
    {
        $expectedSteps = 4; // portfolio, photo, description, notification
        $completedCount = self::where('chain_id', $chainId)
            ->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_SKIPPED])
            ->count();
        return $completedCount >= $expectedSteps;
    }

    /**
     * Get chain summary
     */
    public static function getChainSummary(string $chainId): array
    {
        $logs = self::where('chain_id', $chainId)->orderByChain()->get();

        return [
            'chain_id' => $chainId,
            'total_steps' => $logs->count(),
            'completed' => $logs->where('status', self::STATUS_COMPLETED)->count(),
            'failed' => $logs->where('status', self::STATUS_FAILED)->count(),
            'skipped' => $logs->where('status', self::STATUS_SKIPPED)->count(),
            'pending' => $logs->where('status', self::STATUS_PENDING)->count(),
            'total_duration_ms' => $logs->sum('duration_ms'),
            'agents' => $logs->map(fn ($log) => [
                'agent' => $log->agent_name,
                'status' => $log->status,
                'duration_ms' => $log->duration_ms,
                'event' => $log->event_received,
            ])->toArray(),
        ];
    }
}
