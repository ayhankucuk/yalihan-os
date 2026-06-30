<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CopilotActionLog extends BaseModel
{
    use HasCountryScope;

    protected $fillable = [
        'action_type',
        'user_id',
        'ilan_id',
        'main_category_id',
        'listing_type_id',
        'request_payload',
        'response_payload',
        'applied_fields',
        'diff_snapshot',
        'aksiyon_durumu',
        'confidence_score',
        'duration_ms',
        'rejection_reason',
        'applied_at',
        'undone_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'ilan_id' => 'integer',
        'main_category_id' => 'integer',
        'listing_type_id' => 'integer',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'applied_fields' => 'array',
        'diff_snapshot' => 'array',
        'confidence_score' => 'float',
        'duration_ms' => 'integer',
        'applied_at' => 'datetime',
        'undone_at' => 'datetime',
    ];

    // --- Scopes ---

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByActionType($query, string $type)
    {
        return $query->where('action_type', $type);
    }

    public function scopeApplied($query)
    {
        return $query->where('aksiyon_durumu', 'applied');
    }

    public function scopePending($query)
    {
        return $query->where('aksiyon_durumu', 'preview');
    }

    // --- Relationships ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class);
    }

    // --- State Methods ---

    public function isApplied(): bool
    {
        return $this->aksiyon_durumu === 'applied';
    }

    public function isUndone(): bool
    {
        return $this->aksiyon_durumu === 'undone';
    }

    public function markApplied(array $appliedFields): void
    {
        $this->update([
            'aksiyon_durumu' => 'applied',
            'applied_fields' => $appliedFields,
            'applied_at' => now(),
        ]);
    }

    public function markUndone(): void
    {
        $this->update([
            'aksiyon_durumu' => 'undone',
            'undone_at' => now(),
        ]);
    }

    public function markRejected(?string $reason = null): void
    {
        $this->update([
            'aksiyon_durumu' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }
}
