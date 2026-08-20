<?php

namespace App\Models;

class OptimizerSuggestion extends BaseModel
{
    protected $table = 'optimizer_suggestions';

    protected $casts = [
        'confidence' => 'float',
        'evidence' => 'array',
        'applied_at' => 'datetime',
    ];

    // ─── Relations ──────────────────────────────────────────

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ─── Scopes ─────────────────────────────────────────────

    protected static function statusColumn(): string
    {
        return \Illuminate\Support\Facades\Schema::hasColumn('optimizer_suggestions', 'oneri_durumu')
            ? 'oneri_durumu'
            : 'status';
    }

    public function scopePending($query)
    {
        return $query->where(static::statusColumn(), 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where(static::statusColumn(), 'approved');
    }

    public function scopeApplied($query)
    {
        return $query->where(static::statusColumn(), 'applied');
    }

    public function scopeRejected($query)
    {
        return $query->where(static::statusColumn(), 'rejected');
    }

    public function scopeHighConfidence($query, float $threshold = 0.8)
    {
        return $query->where('confidence', '>=', $threshold);
    }

    // ─── Methods ────────────────────────────────────────────

    public function approve(int $userId): void
    {
        $this->update([
            static::statusColumn() => 'approved',
            'approved_by' => $userId,
        ]);
    }

    public function reject(int $userId): void
    {
        $this->update([
            static::statusColumn() => 'rejected',
            'approved_by' => $userId,
        ]);
    }

    public function markApplied(): void
    {
        $this->update([
            'oneri_durumu' => 'applied',
            'applied_at' => now(),
        ]);
    }

    public function isPending(): bool
    {
        return $this->oneri_durumu === 'pending';
    }
}
