<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IlanCalendarFeed — Token tabanlı outbound ICS feed modeli
 *
 * Table: ilan_calendar_feeds
 * FK: ilan_id → ilanlar
 *
 * B-006 P5D: Deprecated\IlanCalendarFeed ghost → App\Models\IlanCalendarFeed
 *
 * NOT: App\Models\PropertyCalendarFeed AYRI — Airbnb/Booking inbound iCal sync içindir.
 *      Bu model outbound ICS feed URL üretimi için kullanılır (IlanCalendarIcsService).
 */
class IlanCalendarFeed extends BaseModel
{
    protected $table = 'ilan_calendar_feeds';

    protected $fillable = [
        'ilan_id',
        'token',
        'aktiflik_durumu',
        'created_by_user_id',
        'revoked_at',
    ];

    protected $casts = [
        'aktiflik_durumu' => 'boolean',
        'revoked_at'      => 'datetime',
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Token'ın ilk 8 karakterini döndürür (log için güvenli)
     */
    public function tokenPrefix(): string
    {
        return substr($this->token ?? '', 0, 8) . '...';
    }
}
