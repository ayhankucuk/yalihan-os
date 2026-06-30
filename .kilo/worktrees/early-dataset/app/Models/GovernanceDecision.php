<?php

namespace App\Models;

use App\Enums\FindingDecision;
use App\Enums\FindingSeverity;

/**
 * GovernanceDecision Model — SAB2/SAB3/SAB8 Decision Engine
 *
 * Stores findings queued for operator review, with full
 * audit trail of approval/rejection/auto-application.
 * SAB3: Explainability, rollback snapshots, overrides, timeline, confidence.
 * SAB8: Action result tracking, impact scoring, feedback loop.
 */
class GovernanceDecision extends BaseModel
{
    protected $fillable = [
        'finding_id',
        'source',
        'domain',
        'severity',
        'title',
        'reason',
        'target',
        'recommended_action',
        'risk',
        'decision',
        'karar_durumu',
        'karar_veren_id',
        'karar_tarihi',
        'karar_notu',
        'proposal_filename',
        'meta',
        // SAB3 fields
        'explanation',
        'signals',
        'confidence',
        'timeline',
        'rollback_snapshot',
        'override_decision',
        'override_reason',
        'override_by',
        'override_at',
        // SAB8 fields
        'action_result',
        'impact_score',
        'action_completed_at',
        'feedback_note',
        // Zero Trust Forensics
        'prev_hash',
        'current_hash',
    ];

    protected $casts = [
        'meta' => 'array',
        'explanation' => 'array',
        'signals' => 'array',
        'timeline' => 'array',
        'rollback_snapshot' => 'array',
        'confidence' => 'float',
        'severity' => FindingSeverity::class,
        'decision' => FindingDecision::class,
        'karar_tarihi' => 'datetime',
        'override_at' => 'datetime',
        // SAB8 casts
        'action_result' => 'array',
        'impact_score' => 'integer',
        'action_completed_at' => 'datetime',
    ];

    // ── Relationships ──

    public function kararVeren()
    {
        return $this->belongsTo(User::class, 'karar_veren_id');
    }

    public function rollbacks()
    {
        return $this->hasMany(GovernanceRollback::class, 'decision_id');
    }

    public function overrideByUser()
    {
        return $this->belongsTo(User::class, 'override_by');
    }

    // ── Scopes ──

    public function scopePending($query)
    {
        return $query->where('karar_durumu', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('karar_durumu', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('karar_durumu', 'rejected');
    }

    public function scopeAutoApplied($query)
    {
        return $query->where('karar_durumu', 'auto_applied');
    }

    public function scopeFailed($query)
    {
        return $query->where('karar_durumu', 'failed');
    }

    public function scopeOverridden($query)
    {
        return $query->whereNotNull('override_decision');
    }

    public function scopeRolledBack($query)
    {
        return $query->where('karar_durumu', 'rolled_back');
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    // SAB8: Action result scopes

    public function scopeCompleted($query)
    {
        return $query->whereIn('karar_durumu', ['approved', 'auto_applied'])
            ->whereNotNull('action_completed_at');
    }

    public function scopeSuccessful($query)
    {
        return $query->whereNotNull('action_result')
            ->whereRaw("JSON_EXTRACT(action_result, '$.success') = true");
    }

    public function scopeActionFailed($query)
    {
        return $query->where(function ($q) {
            $q->where('karar_durumu', 'failed')
              ->orWhere(function ($q2) {
                  $q2->whereNotNull('action_result')
                     ->whereRaw("JSON_EXTRACT(action_result, '$.success') = false");
              });
        });
    }

    public function scopeBySeverity($query, FindingSeverity $severity)
    {
        return $query->where('severity', $severity->value);
    }

    // ── Actions ──

    public function approve(int $userId, ?string $note = null): self
    {
        $this->addTimelineEvent('approved', $userId, $note);

        $this->update([
            'karar_durumu' => 'approved',
            'karar_veren_id' => $userId,
            'karar_tarihi' => now(),
            'karar_notu' => $note,
        ]);

        return $this;
    }

    public function reject(int $userId, ?string $note = null): self
    {
        $this->addTimelineEvent('rejected', $userId, $note);

        $this->update([
            'karar_durumu' => 'rejected',
            'karar_veren_id' => $userId,
            'karar_tarihi' => now(),
            'karar_notu' => $note,
        ]);

        return $this;
    }

    public function markAutoApplied(?string $proposalFilename = null): self
    {
        $this->addTimelineEvent('auto_applied', null, "Proposal: {$proposalFilename}");

        $this->update([
            'karar_durumu' => 'auto_applied',
            'karar_tarihi' => now(),
            'proposal_filename' => $proposalFilename,
        ]);

        return $this;
    }

    public function markProposalCreated(string $filename): self
    {
        $this->addTimelineEvent('proposal_created', null, "File: {$filename}");

        $this->update([
            'proposal_filename' => $filename,
        ]);

        return $this;
    }

    public function markFailed(string $reason): self
    {
        $this->addTimelineEvent('failed', null, $reason);

        $this->update([
            'karar_durumu' => 'failed',
            'karar_notu' => $reason,
        ]);

        return $this;
    }

    public function markRolledBack(int $userId, string $reason): self
    {
        $this->addTimelineEvent('rolled_back', $userId, $reason);

        $this->update([
            'karar_durumu' => 'rolled_back',
        ]);

        return $this;
    }

    public function applyOverride(string $overrideDecision, string $reason, int $userId): self
    {
        $this->addTimelineEvent('override_applied', $userId, "{$overrideDecision}: {$reason}");

        $this->update([
            'override_decision' => $overrideDecision,
            'override_reason' => $reason,
            'override_by' => $userId,
            'override_at' => now(),
        ]);

        return $this;
    }

    // ── Timeline ──

    public function addTimelineEvent(string $event, ?int $userId = null, ?string $detail = null): void
    {
        $timeline = $this->timeline ?? [];

        $timeline[] = [
            'event' => $event,
            'user_id' => $userId,
            'detail' => $detail,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->timeline = $timeline;
        $this->saveQuietly();
    }

    // ── Helpers ──

    public function isRollbackable(): bool
    {
        return in_array($this->karar_durumu, ['approved', 'auto_applied'])
            && $this->rollback_snapshot !== null;
    }

    public function isOverridden(): bool
    {
        return $this->override_decision !== null;
    }

    public function hasLowConfidence(): bool
    {
        return $this->confidence !== null && $this->confidence < 0.5;
    }

    // ── SAB8: Action Result Tracking ──

    /**
     * Record the result of an applied action.
     */
    public function recordResult(bool $success, array $changedFields = [], ?string $summary = null, ?int $impactScore = null): self
    {
        $this->addTimelineEvent($success ? 'action_succeeded' : 'action_failed', null, $summary);

        $this->update([
            'action_result' => [
                'success' => $success,
                'changed_fields' => $changedFields,
                'result_summary' => $summary,
            ],
            'impact_score' => $impactScore,
            'action_completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Add operator feedback after seeing result.
     */
    public function addFeedback(string $note, ?int $userId = null): self
    {
        $this->addTimelineEvent('feedback_added', $userId ?? auth()->id(), $note);
        $this->update(['feedback_note' => $note]);

        return $this;
    }

    /**
     * Check if this decision has a recorded result.
     */
    public function hasResult(): bool
    {
        return $this->action_result !== null;
    }

    /**
     * Check if the action was successful.
     */
    public function wasSuccessful(): bool
    {
        return $this->hasResult() && ($this->action_result['success'] ?? false);
    }

    /**
     * Get status label for UI display.
     */
    public function getStatusLabel(): string
    {
        return match ($this->karar_durumu) {
            'pending' => 'Bekliyor',
            'approved' => $this->hasResult() ? ($this->wasSuccessful() ? 'Uygulandı' : 'Başarısız') : 'Onaylandı',
            'rejected' => 'Reddedildi',
            'auto_applied' => $this->hasResult() ? ($this->wasSuccessful() ? 'Oto-Başarılı' : 'Oto-Başarısız') : 'Oto-Uygulandı',
            'failed' => 'Başarısız',
            'rolled_back' => 'Geri Alındı',
            'blocked' => 'Engellendi',
            default => ucfirst($this->karar_durumu),
        };
    }

    // ── Zero Trust Forensics: Hash Chain ──

    /**
     * Bu kararın hash zinciri bütünlüğünü doğrular.
     *
     * @param string|null $expectedPrevHash Bir önceki kararın current_hash değeri (ilk karar için null)
     */
    public function verifyHash(?string $expectedPrevHash): bool
    {
        // Genesis bloğu için genesis hash'i kullan
        $resolvedPrev = $expectedPrevHash ?? 'GENESIS_BLOCK_HASH';

        $calculatedHash = hash('sha256', json_encode([
            'id'           => $this->id,
            'yayin_durumu' => $this->karar_durumu,
            'prev_hash'    => $resolvedPrev,
            'created_at'   => $this->created_at?->toDateTimeString(),
        ]));

        return $this->prev_hash === $resolvedPrev
            && $this->current_hash === $calculatedHash;
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColor(): string
    {
        return match ($this->karar_durumu) {
            'pending' => 'yellow',
            'approved' => $this->wasSuccessful() ? 'green' : 'orange',
            'auto_applied' => $this->wasSuccessful() ? 'emerald' : 'orange',
            'rejected' => 'gray',
            'failed' => 'red',
            'rolled_back' => 'purple',
            'blocked' => 'red',
            default => 'gray',
        };
    }
}
