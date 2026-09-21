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

    // ─── Lease Contract ────────────────────────────────────────────────
    //
    // A RUNNING record is not proof that a worker is alive. It is proof
    // that a worker successfully claimed the record at `started_at`. The
    // claim is only considered VALID until `started_at + LEASE_DURATION`.
    // Beyond that window, the claim is considered STALE and any worker
    // may atomically re-claim it via `reclaimIfStale()`.
    //
    // Derivation (see AsyncHandlerDispatchJob for authority):
    //   - $timeout = 300s (max legitimate single-handler wall-clock)
    //   - LEASE_DURATION = 2 * $timeout = 600s (100% safety margin)
    //   - $uniqueFor  = 1800s (queue unique-lock TTL) > LEASE_DURATION,
    //     so lease expiry is reachable within the unique-lock window.
    //
    // This constant is deliberately colocated with the state constants
    // because it is a first-class property of the execution state
    // machine, not a queue-layer concern.
    public const LEASE_DURATION_SECONDS = 600;

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

    /**
     * Atomically transition this record from PENDING to RUNNING.
     *
     * Uses a conditional UPDATE (WHERE id = ? AND status = 'pending')
     * so exactly one caller can win the claim under concurrent contention.
     *
     * @return int Rows affected: 1 on successful claim, 0 if the record
     *             was already claimed, completed, failed, or skipped.
     */
    public function claimForRun(): int
    {
        $now = now();

        $affected = static::query()
            ->where('id', $this->id)
            ->where('status', self::STATUS_PENDING)
            ->update([
                'status'     => self::STATUS_RUNNING,
                'started_at' => $now,
            ]);

        if ($affected === 1) {
            $this->status = self::STATUS_RUNNING;
            $this->started_at = $now;
        }

        return $affected;
    }

    /**
     * Atomically re-claim a STALE RUNNING record.
     *
     * A RUNNING record whose `started_at` is older than LEASE_DURATION_SECONDS
     * is treated as abandoned (owner crashed, worker OOM-killed, network
     * partition, etc.). This method attempts to refresh the lease by
     * setting `started_at = now()` under a single conditional UPDATE:
     *
     *   UPDATE ... SET started_at = NOW()
     *   WHERE id = ?
     *     AND status = 'running'
     *     AND started_at <= NOW() - LEASE_DURATION_SECONDS
     *
     * Because the compare-and-set is atomic on the row, exactly one caller
     * can win the reclaim under concurrent recovery contention. Losers
     * observe affected = 0 and back off.
     *
     * IMPORTANT: This does NOT change `status`. The record stays RUNNING;
     * only its lease anchor moves forward. This preserves the existing
     * duration-measurement contract (markCompleted / markFailed compute
     * duration from `started_at`) — after a reclaim, that duration reflects
     * the RECOVERED handler run, not the abandoned one.
     *
     * @return int Rows affected: 1 on successful reclaim, 0 if the lease
     *             was still live, another worker reclaimed first, or the
     *             record transitioned to a terminal state in the interim.
     */
    public function reclaimIfStale(): int
    {
        $now    = now();
        $cutoff = $now->copy()->subSeconds(self::LEASE_DURATION_SECONDS);

        $affected = static::query()
            ->where('id', $this->id)
            ->where('status', self::STATUS_RUNNING)
            ->where('started_at', '<=', $cutoff)
            ->update([
                'started_at' => $now,
            ]);

        if ($affected === 1) {
            $this->started_at = $now;
        }

        return $affected;
    }

    /**
     * Compute remaining lease seconds for a RUNNING record.
     *
     * Returns 0 if the lease has already expired or `started_at` is null.
     * Never returns a negative value — the return can be used directly as
     * a queue release() delay without further bounds checking.
     */
    public function remainingLeaseSeconds(): int
    {
        if ($this->started_at === null) {
            return 0;
        }

        // Direct integer arithmetic on Unix timestamps to avoid Carbon
        // 2.x/3.x signed-diff behavior differences.
        $expiresAtTs = $this->started_at->copy()
            ->addSeconds(self::LEASE_DURATION_SECONDS)
            ->getTimestamp();
        $remaining   = $expiresAtTs - now()->getTimestamp();

        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Whether the current in-memory `started_at` anchor indicates an
     * expired lease. Snapshot check only — production code paths must
     * still guard concurrent race conditions via `reclaimIfStale()`.
     */
    public function isLeaseExpired(): bool
    {
        if ($this->started_at === null) {
            return true;
        }

        return $this->started_at->copy()
            ->addSeconds(self::LEASE_DURATION_SECONDS)
            ->lte(now());
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
