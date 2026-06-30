<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * KisiAktivite Model
 *
 * Context7 Standardı: kisi_aktiviteler table
 * Replaces: MusteriAktivite (deprecated)
 */
class KisiAktivite extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'kisi_etkilesimler';

    protected $fillable = [
        'kisi_id',
        'kullanici_id',
        'tip',
        'notlar',
        'etkilesim_tarihi',
        'aktiflik_durumu',
        'display_order',
    ];

    protected $casts = [
        'etkilesim_tarihi' => 'datetime',
        'aktiflik_durumu' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Bu aktivitenin sahibi kişi
     */
    public function kisi(): BelongsTo
    {
        return $this->belongsTo(Kisi::class);
    }

    /**
     * Bu aktiviteyi oluşturan kullanıcı
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kullanici_id');
    }

    /**
     * İlişkili ilan
     */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'iliskili_ilan_id');
    }
}
