<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use Illuminate\Notifications\DatabaseNotification as BaseNotification;

class Notification extends BaseNotification
{
    use HasFactory, HasUuids;

    /**
     * Context7 Mühürlü $fillable
     * Sadece veritabanındaki kolonlar
     * @sealed 2025-12-31
     */
    protected $fillable = [
        'type', // context7-ignore
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
        'status', // Context7: renamed from s...s
    ];

    /**
     * Context7 Mühürlü $casts
     * @sealed 2025-12-31
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the notifiable entity that the notification belongs to.
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    // ...

    public function scopeByStatus($query, $durum)
    {
        return $query->where('status', $durum);
    }
}
