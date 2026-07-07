<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * WorkspaceExecution — Sprint 4.7: Workspace Execution Engine
 *
 * Every long-running workspace operation gets its own execution record.
 * Execution lifecycle: queued → running → succeeded/failed/retrying/cancelled/timed_out
 *
 * Replay: creates a NEW execution record (never mutates the failed one).
 * Retry:  creates a NEW execution record with same payload.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int|null $ilan_id
 * @property int|null $tenant_id
 * @property string $execution_type
 * @property string $execution_label
 * @property string|null $chain_id
 * @property string $state
 * @property \Carbon\Carbon|null $queued_at
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property int|null $duration_ms
 * @property int $attempt_number
 * @property int $max_attempts
 * @property int $retry_count
 * @property string|null $backoff_intervals
 * @property int|null $original_execution_id
 * @property string|null $failure_reason
 * @property array|null $failure_context
 * @property array|null $input_payload
 * @property array|null $output_result
 * @property int|null $progress_pct
 * @property string $queue_name
 * @property string|null $job_id
 * @property int $timeout_seconds
 * @property string|null $triggered_by
 * @property int|null $triggered_by_user_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class WorkspaceExecution extends Model
{
    use HasFactory;
    use SoftDeletes;

    // ─── Execution States ────────────────────────────────────────────────────
    public const STATE_QUEUED     = 'queued';
    public const STATE_RUNNING    = 'running';
    public const STATE_WAITING   = 'waiting';
    public const STATE_RETRYING  = 'retrying';
    public const STATE_SUCCEEDED  = 'succeeded';
    public const STATE_FAILED     = 'failed';
    public const STATE_CANCELLED = 'cancelled';
    public const STATE_TIMED_OUT  = 'timed_out';

    // ─── Trigger Sources ─────────────────────────────────────────────────────
    public const TRIGGERED_BY_HERMES  = 'hermes';
    public const TRIGGERED_BY_MANUAL  = 'manual';
    public const TRIGGERED_BY_SCHEDULE = 'schedule';
    public const TRIGGERED_BY_REPLAY   = 'replay';

    // ─── Default Backoff (seconds) ──────────────────────────────────────────
    public const DEFAULT_BACKOFF = [10, 60, 300]; // 10s, 1m, 5m

    protected $fillable = [
        'workspace_id',
        'ilan_id',
        'tenant_id',
        'execution_type',
        'execution_label',
        'chain_id',
        'state',
        'queued_at',
        'started_at',
        'completed_at',
        'duration_ms',
        'attempt_number',
        'max_attempts',
        'retry_count',
        'backoff_intervals',
        'original_execution_id',
        'failure_reason',
        'failure_context',
        'input_payload',
        'output_result',
        'progress_pct',
        'queue_name',
        'job_id',
        'timeout_seconds',
        'triggered_by',
        'triggered_by_user_id',
    ];

    protected $casts = [
        'queued_at'         => 'datetime',
        'started_at'        => 'datetime',
        'completed_at'      => 'datetime',
        'duration_ms'       => 'integer',
        'attempt_number'    => 'integer',
        'max_attempts'      => 'integer',
        'retry_count'       => 'integer',
        'backoff_intervals' => 'array',
        'failure_context'   => 'array',
        'input_payload'     => 'array',
        'output_result'     => 'array',
        'progress_pct'      => 'integer',
        'timeout_seconds'   => 'integer',
    ];

    protected $attributes = [
        'state'             => self::STATE_QUEUED,
        'attempt_number'     => 1,
        'max_attempts'      => 3,
        'retry_count'       => 0,
        'queue_name'        => 'workspace',
        'timeout_seconds'   => 300,
    ];

    // ─── Boot ───────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (self $exec) {
            if (!$exec->tenant_id && $exec->workspace_id) {
                $ws = \App\Models\PortfolioDriveWorkspace::find($exec->workspace_id);
                if ($ws) {
                    $exec->tenant_id = $ws->tenant_id;
                }
            }
            if (!$exec->queued_at) {
                $exec->queued_at = now();
            }
            if (!$exec->backoff_intervals) {
                $exec->backoff_intervals = self::DEFAULT_BACKOFF;
            }
        });
    }

    // ─── Relations ───────────────────────────────────────────────────────────

    /** @belongsTo */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(PortfolioDriveWorkspace::class);
    }

    /** @belongsTo */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class);
    }

    /** @belongsTo */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    /** @belongsTo — points to original execution for retry/replay chains */
    public function originalExecution(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_execution_id');
    }

    /** @hasMany */
    public function retryExecutions(): HasMany
    {
        return $this->hasMany(self::class, 'original_execution_id');
    }

    // ─── State Machine Helpers ───────────────────────────────────────────────

    public function markRunning(): void
    {
        $this->update([
            'state'      => self::STATE_RUNNING,
            'started_at' => now(),
        ]);
    }

    public function markSucceeded(array $result = []): void
    {
        $this->update([
            'state'         => self::STATE_SUCCEEDED,
            'completed_at'   => now(),
            'duration_ms'   => $this->started_at ? (int) now()->diffInMilliseconds($this->started_at) : null,
            'output_result'  => $result,
        ]);
    }

    public function markFailed(string $reason, array $context = []): void
    {
        $this->update([
            'state'           => self::STATE_FAILED,
            'completed_at'     => now(),
            'duration_ms'      => $this->started_at ? (int) now()->diffInMilliseconds($this->started_at) : null,
            'failure_reason'   => $reason,
            'failure_context'  => $context,
        ]);
    }

    public function markRetrying(): void
    {
        $this->update([
            'state'       => self::STATE_RETRYING,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    public function markCancelled(): void
    {
        $this->update([
            'state'         => self::STATE_CANCELLED,
            'completed_at'   => now(),
            'duration_ms'   => $this->started_at ? (int) now()->diffInMilliseconds($this->started_at) : null,
        ]);
    }

    public function markTimedOut(): void
    {
        $this->update([
            'state'           => self::STATE_TIMED_OUT,
            'completed_at'     => now(),
            'failure_reason'   => 'Execution timed out after ' . $this->timeout_seconds . 's',
        ]);
    }

    // ─── Query Scopes ────────────────────────────────────────────────────────

    public function scopeQueued($q)    { return $q->where('state', self::STATE_QUEUED); }
    public function scopeRunning($q)   { return $q->where('state', self::STATE_RUNNING); }
    public function scopeSucceeded($q){ return $q->where('state', self::STATE_SUCCEEDED); }
    public function scopeFailed($q)    { return $q->where('state', self::STATE_FAILED); }
    public function scopeActive($q)    { return $q->whereIn('state', [self::STATE_QUEUED, self::STATE_RUNNING, self::STATE_RETRYING]); }

    public function scopeForWorkspace($q, int $workspaceId)
    {
        return $q->where('workspace_id', $workspaceId);
    }

    public function scopeForIlan($q, int $ilanId)
    {
        return $q->where('ilan_id', $ilanId);
    }

    public function scopeByType($q, string $type)
    {
        return $q->where('execution_type', $type);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return in_array($this->state, [
            self::STATE_QUEUED,
            self::STATE_RUNNING,
            self::STATE_RETRYING,
        ], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this->state, [
            self::STATE_SUCCEEDED,
            self::STATE_FAILED,
            self::STATE_CANCELLED,
            self::STATE_TIMED_OUT,
        ], true);
    }

    public function canRetry(): bool
    {
        return $this->state === self::STATE_FAILED
            && $this->retry_count < $this->max_attempts;
    }

    public function canReplay(): bool
    {
        return $this->isTerminal();
    }

    public function getBackoffSeconds(): int
    {
        $intervals = $this->backoff_intervals ?? self::DEFAULT_BACKOFF;
        $index = min($this->retry_count, count($intervals) - 1);
        return (int) ($intervals[$index] ?? 60);
    }

    public function getDurationHuman(): ?string
    {
        if (!$this->duration_ms) {
            return null;
        }
        $ms = $this->duration_ms;
        if ($ms < 1000) {
            return $ms . 'ms';
        }
        return round($ms / 1000, 1) . 's';
    }

    public function getStateColor(): string
    {
        return match ($this->state) {
            self::STATE_QUEUED     => 'slate',
            self::STATE_RUNNING    => 'blue',
            self::STATE_WAITING    => 'amber',
            self::STATE_RETRYING   => 'amber',
            self::STATE_SUCCEEDED  => 'emerald',
            self::STATE_FAILED     => 'red',
            self::STATE_CANCELLED  => 'gray',
            self::STATE_TIMED_OUT  => 'orange',
            default                => 'slate',
        };
    }

    public function getStateLabel(): string
    {
        return match ($this->state) {
            self::STATE_QUEUED     => 'Sırada',
            self::STATE_RUNNING    => 'Çalışıyor',
            self::STATE_WAITING    => 'Bekliyor',
            self::STATE_RETRYING   => 'Yeniden Deniyor',
            self::STATE_SUCCEEDED  => 'Tamamlandı',
            self::STATE_FAILED     => 'Başarısız',
            self::STATE_CANCELLED  => 'İptal Edildi',
            self::STATE_TIMED_OUT  => 'Zaman Aşımı',
            default                => $this->state,
        };
    }
}
