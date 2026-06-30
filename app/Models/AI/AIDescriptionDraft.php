<?php

namespace App\Models;

use App\Enums\AIDescriptionStatus;
use App\Traits\HasCountryScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Description Draft
 *
 * Pipeline: Context Builder → LLM → Draft → Owner Review → Accept → Persist
 *
 * AI NEVER writes directly to ilan.aciklama.
 * AI produces Draft only.
 * Owner reviews and decides.
 *
 * @property int $id
 * @property int $ilan_id
 * @property int|null $user_id
 * @property string $draft_content
 * @property string|null $original_content
 * @property string $durum
 * @property string|null $provider
 * @property string|null $model
 * @property array|null $metadata
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property Carbon|null $applied_at
 * @property int|null $rejected_by
 * @property Carbon|null $rejected_at
 * @property string|null $rejection_note
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AIDescriptionDraft extends BaseModel
{
    use HasCountryScope; // SAB: Country isolation required
    use HasFactory;

    protected $table = 'ai_description_drafts';

    protected $fillable = [
        'ilan_id',
        'user_id',
        'draft_content',
        'original_content',
        'durum',
        'provider',
        'model',
        'metadata',
        'approved_by',
        'approved_at',
        'applied_at',
        'rejected_by',
        'rejected_at',
        'rejection_note',
    ];

    protected $casts = [
        'metadata' => 'array',
        'approved_at' => 'datetime',
        'applied_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approved_by_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejected_by_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // ========================================================================
    // STATUS HELPERS
    // ========================================================================

    /**
     * Get status enum
     */
    public function getDurumEnumAttribute(): ?AIDescriptionStatus
    {
        return AIDescriptionStatus::tryFrom($this->durum);
    }

    /**
     * Is pending review?
     */
    public function isPendingReview(): bool
    {
        return $this->durum === AIDescriptionStatus::TASLAK->value;
    }

    /**
     * Is approved?
     */
    public function isApproved(): bool
    {
        return $this->durum === AIDescriptionStatus::ONAYLI->value;
    }

    /**
     * Is applied to ilan?
     */
    public function isApplied(): bool
    {
        return $this->durum === AIDescriptionStatus::UYGULANDI->value;
    }

    /**
     * Is rejected?
     */
    public function isRejected(): bool
    {
        return $this->durum === AIDescriptionStatus::REDDEDILDI->value;
    }

    // ========================================================================
    // ACTIONS
    // ========================================================================

    /**
     * Approve the draft
     */
    public function approve(int $userId): bool
    {
        if (! $this->isPendingReview()) {
            return false;
        }

        $this->update([
            'durum' => AIDescriptionStatus::ONAYLI->value,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        return true;
    }

    /**
     * Reject the draft
     */
    public function reject(int $userId, ?string $note = null): bool
    {
        if (! $this->isPendingReview()) {
            return false;
        }

        $this->update([
            'durum' => AIDescriptionStatus::REDDEDILDI->value,
            'rejected_by' => $userId,
            'rejected_at' => now(),
            'rejection_note' => $note,
        ]);

        return true;
    }

    /**
     * Apply approved draft to ilan.aciklama
     */
    public function apply(): bool
    {
        if (! $this->isApproved()) {
            return false;
        }

        $this->ilan->updateQuietly([
            'aciklama' => $this->draft_content,
        ]);

        $this->update([
            'durum' => AIDescriptionStatus::UYGULANDI->value,
            'applied_at' => now(),
        ]);

        return true;
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    /**
     * Active drafts (taslak)
     */
    public function scopePending($query)
    {
        return $query->where('durum', AIDescriptionStatus::TASLAK->value);
    }

    /**
     * Approved drafts
     */
    public function scopeApproved($query)
    {
        return $query->where('durum', AIDescriptionStatus::ONAYLI->value);
    }

    /**
     * Latest draft for an ilan
     */
    public function scopeLatestForIlan($query, int $ilanId)
    {
        return $query->where('ilan_id', $ilanId)
            ->orderBy('id', 'desc'); // context7-ignore
    }

    /**
     * With user info
     */
    public function scopeWithUser($query)
    {
        return $query->with(['user', 'approved_by_user', 'rejected_by_user']);
    }
}
