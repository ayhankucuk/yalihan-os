<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

/**
 * WorkforceExecution — Canonical runtime execution record.
 *
 * SAB Sprint 13: Replay & Recovery
 *
 * Mimari ayrım:
 *   ListingStateTransition = Immutable domain history (değiştirilemez, hiçbir zaman)
 *   WorkforceExecution    = Runtime execution metadata (yeni record oluşturulabilir)
 *
 * Replay güvenliği garantileri:
 *   1. Replay her zaman yeni UUID üretir (asla mevcut record'u update etmez)
 *   2. replay_of_uuid orijinal execution'a referans verir
 *   3. parent_uuid retry/replay zincirini korur
 *   4. idempotency_key duplicate execution'ları engeller
 *   5. Tenant/workspace isolation KURAL 1 ile zorunlu
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $parent_uuid
 * @property string|null $replay_of_uuid
 * @property string $aggregate_type
 * @property int $aggregate_id
 * @property string $capability
 * @property string|null $idempotency_key
 * @property int|null $tenant_id
 * @property int|null $workspace_id
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property string|null $trigger_type
 * @property string|null $replay_reason
 * @property string $execution_status
 * @property \Carbon\Carbon|null $started_at
 * @property string|null $finished_at
 * @property int|null $duration_ms
 * @property string|null $error_code
 * @property string|null $error_message
 * @property array|null $result_snapshot
 * @property array|null $input_snapshot
 * @property array|null $metadata
 * @property int|null $retry_count
 * @property int|null $max_retries
 * @property \Carbon\Carbon|null $next_retry_at
 * @property string|null $failure_classification
 * @property string|null $retry_policy
 * @property string|null $recovery_of_uuid
 * @property \Carbon\Carbon|null $recovered_at
 */
class WorkforceExecution extends BaseModel
{
    use HasCountryScope, HasFactory;

    protected $table = 'workforce_executions';

    // ── Status Constants ─────────────────────────────────────────────────────

    public const STATUS_REQUESTED  = 'REQUESTED';
    public const STATUS_RUNNING    = 'RUNNING';
    public const STATUS_COMPLETED  = 'COMPLETED';
    public const STATUS_FAILED    = 'FAILED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const VALID_STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_RUNNING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    // ── Trigger Type Constants ─────────────────────────────────────────────

    public const TRIGGER_MANUAL    = 'MANUAL';
    public const TRIGGER_REPLAY    = 'REPLAY';
    public const TRIGGER_RETRY    = 'RETRY';
    public const TRIGGER_SCHEDULED = 'SCHEDULED';
    public const TRIGGER_WEBHOOK  = 'WEBHOOK';
    public const TRIGGER_HERMES   = 'HERMES';

    public const VALID_TRIGGERS = [
        self::TRIGGER_MANUAL,
        self::TRIGGER_REPLAY,
        self::TRIGGER_RETRY,
        self::TRIGGER_SCHEDULED,
        self::TRIGGER_WEBHOOK,
        self::TRIGGER_HERMES,
    ];

    // ── Failure Classification Constants (Sprint 13B) ───────────────────────

    public const FAILURE_TRANSIENT  = 'TRANSIENT';  // Geçici — retry ile düzelebilir
    public const FAILURE_PERMANENT  = 'PERMANENT';  // Kalıcı — retry faydasız
    public const FAILURE_CONFIG     = 'CONFIG';     // Yapılandırma — manuel müdahale gerekli
    public const FAILURE_UNKNOWN    = 'UNKNOWN';     // Bilinmeyen — incelenmeli

    // ── Retry Policy Constants (Sprint 13B) ───────────────────────────────

    public const RETRY_EXPONENTIAL = 'EXPONENTIAL'; // 10s → 1m → 5m → 15m → 1h
    public const RETRY_LINEAR       = 'LINEAR';      // 30s → 1m → 2m → 5m
    public const RETRY_IMMEDIATE    = 'IMMEDIATE';   // Hemen (sadece TRANSIENT)

    // ── Actor Type Constants ─────────────────────────────────────────────────

    public const ACTOR_USER   = 'User';
    public const ACTOR_HERMES = 'Hermes';
    public const ACTOR_AGENT  = 'Agent';
    public const ACTOR_SYSTEM = 'System';

    // ── Fillable ─────────────────────────────────────────────────────────

    protected $fillable = [
        'uuid',
        'parent_uuid',
        'replay_of_uuid',
        'aggregate_type',
        'aggregate_id',
        'capability',
        'idempotency_key',
        'tenant_id',
        'workspace_id',
        'actor_type',
        'actor_id',
        'trigger_type',
        'replay_reason',
        'execution_status', // context7-ignore: runtime execution status (not domain status)
        'started_at',
        'finished_at',
        'duration_ms',
        'error_code',
        'error_message',
        'result_snapshot',
        'input_snapshot',
        'metadata',
        'retry_count',
        'max_retries',
        'next_retry_at',
        'failure_classification',
        'retry_policy',
        'recovery_of_uuid',
        'recovered_at',
    ];

    // ── Casts ────────────────────────────────────────────────────────────

    protected $casts = [
        'started_at'     => 'datetime',
        'finished_at'    => 'datetime',
        'result_snapshot' => 'array',
        'input_snapshot'  => 'array',
        'metadata'       => 'array',
        'duration_ms'    => 'integer',
        'aggregate_id'   => 'integer',
        'tenant_id'      => 'integer',
        'workspace_id'   => 'integer',
        'actor_id'       => 'integer',
        'retry_count'    => 'integer',
        'max_retries'    => 'integer',
        'next_retry_at'  => 'datetime',
        'recovered_at'   => 'datetime',
    ];

    // ── Booting ──────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (WorkforceExecution $exec) {
            if (empty($exec->uuid)) {
                $exec->uuid = (string) Str::uuid();
            }
            // context7-ignore
            if (empty($exec->execution_status)) {
                $exec->execution_status = self::STATUS_REQUESTED;
            }
        });
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeByStatus($query, string $status) // context7-ignore
    {
        return $query->where('execution_status', $status);
    }

    public function scopeByAggregate($query, string $type, int $id)
    {
        return $query->where('aggregate_type', $type)->where('aggregate_id', $id);
    }

    public function scopeRunning($query) // context7-ignore
    {
        return $query->where('execution_status', self::STATUS_RUNNING);
    }

    public function scopeFailed($query) // context7-ignore
    {
        return $query->where('execution_status', self::STATUS_FAILED);
    }

    public function scopeByIdempotencyKey($query, string $key)
    {
        return $query->where('idempotency_key', $key);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeRetryable($query)
    {
        return $query->where('execution_status', self::STATUS_FAILED)
            ->whereIn('failure_classification', [
                self::FAILURE_TRANSIENT,
                self::FAILURE_UNKNOWN,
            ])
            ->where(function ($q) {
                $q->whereRaw('retry_count < max_retries')
                  ->orWhereNull('max_retries');
            })
            ->where(function ($q) {
                $q->where('next_retry_at', '<=', now())
                  ->orWhereNull('next_retry_at');
            });
    }

    public function scopeRecoverable($query)
    {
        return $query->where('execution_status', self::STATUS_FAILED)
            ->whereIn('failure_classification', [
                self::FAILURE_TRANSIENT,
                self::FAILURE_UNKNOWN,
            ]);
    }

    // ── Relations ────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(PropertyWorkspace::class, 'workspace_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** Replay parent (retry/replay zinciri) */
    public function parentExecution(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_uuid', 'uuid');
    }

    /** Child executions (retry/replay chain) */
    public function childExecutions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_uuid', 'uuid');
    }

    /** Replay source — hangi execution yeniden çalıştırıldı */
    public function replaySource(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replay_of_uuid', 'uuid');
    }

    /** Replay'ler bu execution'dan çıkan replay zinciri */
    public function replays(): HasMany
    {
        return $this->hasMany(self::class, 'replay_of_uuid', 'uuid');
    }

    // ── State Machine ───────────────────────────────────────────────────

    /** @deprecated Use ExecutionRuntimeService instead */
    public function markRunning(): self
    {
        $this->execution_status = self::STATUS_RUNNING; // context7-ignore
        $this->started_at = now();
        $this->save();
        return $this;
    }

    /** @deprecated Use ExecutionRuntimeService instead */
    public function markCompleted(array $resultSnapshot = []): self
    {
        $this->execution_status = self::STATUS_COMPLETED; // context7-ignore
        $this->finished_at = now();
        $this->duration_ms = $this->started_at
            ? (int) $this->started_at->diffInMilliseconds(now())
            : null;
        $this->result_snapshot = $resultSnapshot;
        $this->save();
        return $this;
    }

    /** @deprecated Use ExecutionRuntimeService instead */
    public function markFailed(string $errorCode, string $errorMessage, array $context = []): self
    {
        $this->execution_status = self::STATUS_FAILED; // context7-ignore
        $this->finished_at = now();
        $this->duration_ms = $this->started_at
            ? (int) $this->started_at->diffInMilliseconds(now())
            : null;
        $this->error_code = $errorCode;
        $this->error_message = $errorMessage;
        $this->metadata = array_merge($this->metadata ?? [], $context);
        $this->save();
        return $this;
    }

    // ── Replay Helpers ─────────────────────────────────────────────────

    /**
     * Bu execution'ın bir replay olup olmadığını döner.
     */
    public function isReplay(): bool
    {
        return $this->trigger_type === self::TRIGGER_REPLAY;
    }

    /**
     * Bu execution'ın başarısız olup olmadığını döner.
     */
    public function isFailed(): bool // context7-ignore
    {
        return $this->execution_status === self::STATUS_FAILED;
    }

    /**
     * Replay zinciri derinliğini döner.
     */
    public function getReplayChainDepth(): int
    {
        $depth = 0;
        $current = $this->parentExecution;

        while ($current !== null) {
            $depth++;
            $current = $current->parentExecution;
            if ($depth > 100) {
                break; // Prevent infinite loop
            }
        }

        return $depth;
    }
}
