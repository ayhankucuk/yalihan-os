<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class GovernanceRollback extends BaseModel
{
    use HasFactory;

    protected $table = 'governance_rollbacks';

    protected $fillable = [
        'decision_id',
        'proposal_filename',
        'before_snapshot',
        'after_snapshot',
        'rollback_reason',
        'rolled_back_by',
        'rollback_durumu',
    ];

    protected $casts = [
        'before_snapshot' => 'array',
        'after_snapshot' => 'array',
    ];

    // ─── Relations ─────────────────────────────────────────────

    public function decision()
    {
        return $this->belongsTo(GovernanceDecision::class, 'decision_id');
    }

    public function rolledBackByUser()
    {
        return $this->belongsTo(User::class, 'rolled_back_by');
    }

    // ─── Scopes ────────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('rollback_durumu', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('rollback_durumu', 'failed');
    }

    public function scopeForDecision($query, int $decisionId)
    {
        return $query->where('decision_id', $decisionId);
    }

    // ─── Methods ───────────────────────────────────────────────

    public function isCompleted(): bool
    {
        return $this->rollback_durumu === 'completed';
    }

    public function markFailed(?string $reason = null): void
    {
        $this->update([
            'rollback_durumu' => 'failed',
            'rollback_reason' => $reason ?? $this->rollback_reason,
        ]);
    }
}
