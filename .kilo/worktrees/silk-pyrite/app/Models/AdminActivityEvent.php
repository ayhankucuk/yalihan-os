<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AdminActivityEvent Model
 *
 * Telegram ↔ Admin UI activity feed — read-only log.
 * Önceki Deprecated\AdminActivityEvent ghost'unun kanonik karşılığı.
 *
 * @property int         $id
 * @property string      $entity_type  reservation|calendar|...
 * @property int         $entity_id
 * @property string      $action       create|confirm|cancel|close_calendar|...
 * @property string      $source       admin|telegram|system
 * @property string|null $summary
 * @property array|null  $context
 * @property int|null    $user_id
 * @property int|null    $telegram_user_id
 */
class AdminActivityEvent extends BaseModel
{
    use HasCountryScope;

    protected $table = 'admin_activity_events';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'action',
        'source',
        'summary',
        'context',
        'user_id',
        'telegram_user_id',
    ];

    protected $casts = [
        'context'          => 'array',
        'entity_id'        => 'integer',
        'telegram_user_id' => 'integer',
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

    /** Entity tipine göre filtrele */
    public function scopeForEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /** Entity ID'ye göre filtrele */
    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)
                     ->where('entity_id', $entityId);
    }

    /** Kaynak (source) filtrele */
    public function scopeForSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /** Belirli kullanıcıya ait event'ler */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
