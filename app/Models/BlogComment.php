<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 🛡️ SAB SEALED: BlogComment
 *
 * @property int    $id
 * @property int    $post_id
 * @property int|null $user_id
 * @property string $content
 * @property string $yayin_durumu  — pending|approved|rejected|spam
 * @property int|null $approved_by
 * @property string|null $rejected_reason
 */
class BlogComment extends BaseModel
{
    use HasCountryScope;

    protected $table = 'blog_comments';

    protected $fillable = [
        'post_id',
        'user_id',
        'content',
        'yayin_durumu',
        'guest_name',
        'guest_email',
        'approved_by',
        'rejected_reason',
        'rejected_at',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // ─── Relations ────────────────────────────────────────────────

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ─── Actions ──────────────────────────────────────────────────

    public function approve(int $adminId): bool
    {
        return $this->update([
            'yayin_durumu' => 'approved',
            'approved_by'  => $adminId,
            'approved_at'  => now(),
        ]);
    }

    public function reject(int $adminId, ?string $reason = null): bool
    {
        return $this->update([
            'yayin_durumu'    => 'rejected',
            'approved_by'     => $adminId,
            'rejected_reason' => $reason,
            'rejected_at'     => now(),
        ]);
    }

    public function markAsSpam(int $adminId, ?string $reason = null): bool
    {
        return $this->update([
            'yayin_durumu'    => 'spam',
            'approved_by'     => $adminId,
            'rejected_reason' => $reason,
            'rejected_at'     => now(),
        ]);
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('yayin_durumu', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('yayin_durumu', 'pending');
    }
}
