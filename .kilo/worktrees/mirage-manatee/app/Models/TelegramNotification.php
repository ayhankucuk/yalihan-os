<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramNotification extends BaseModel
{
    use HasCountryScope;

    protected $fillable = [
        'ulke_id',
        'user_id',
        'lead_id',
        'mesaj_tipi',
        'mesaj_icerigi',
        'gonderim_durumu',
        'hata_mesaji',
        'deneme_sayisi',
        'gonderim_zamani',
    ];

    protected $casts = [
        'ulke_id'         => 'integer',
        'user_id'         => 'integer',
        'lead_id'         => 'integer',
        'gonderim_durumu' => 'integer',
        'deneme_sayisi'   => 'integer',
        'gonderim_zamani' => 'datetime',
    ];

    /**
     * Alıcı kullanıcı.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * İlgili lead.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
