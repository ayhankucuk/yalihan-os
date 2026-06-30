<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AdminNotification Model
 *
 * Danışman bazlı admin bildirimleri.
 * Önceki Deprecated\AdminNotification ghost'unun kanonik karşılığı.
 *
 * @property int         $id
 * @property int         $user_id
 * @property string      $channel   reservation|calendar|system
 * @property string      $event
 * @property string      $title
 * @property string      $message
 * @property array|null  $payload
 * @property bool        $is_read
 */
class AdminNotification extends BaseModel
{
    use HasCountryScope;

    protected $table = 'admin_notifications';

    protected $fillable = [
        'user_id',
        'channel',
        'event',
        'title',
        'message',
        'payload',
        'is_read',
    ];

    protected $casts = [
        'payload' => 'array',
        'is_read' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // İlişkiler
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------------------------
    // Scope'lar
    // -------------------------------------------------------------------------

    /** Belirli kullanıcıya ait bildirimler */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /** Okunmamış bildirimler */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /** Okunmuş bildirimler */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /** Kanal bazlı filtrele */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    // -------------------------------------------------------------------------
    // Yardımcılar
    // -------------------------------------------------------------------------

    /** Bildirimi okundu olarak işaretle */
    public function markAsRead(): bool
    {
        return $this->update(['is_read' => true]);
    }
}
