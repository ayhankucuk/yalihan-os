<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * KisiEtkilesim Model (Person Interaction)
 */
class KisiEtkilesim extends BaseModel
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
     * İlişkiler
     */
    public function kisi(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'kisi_id');
    }

    public function kullanici(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kullanici_id');
    }

    /**
     * Scope: Aktif etkileşimleri getir
     */
    public function scopeAktif($query)
    {
        return $query->where('aktiflik_durumu', true);
    }
}
