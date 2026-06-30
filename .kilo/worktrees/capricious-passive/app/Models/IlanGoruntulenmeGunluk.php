<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IlanGoruntulenmeGunluk Model
 *
 * PHSAE 19.3: Visibility Metrics
 * Tracks daily impressions for listings.
 */
class IlanGoruntulenmeGunluk extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'ilan_goruntulenme_gunluk';

    protected $fillable = [
        'ilan_id',
        'tarih',
        'cihaz',
        'adet',
    ];

    protected $casts = [
        'tarih' => 'date',
        'adet' => 'integer',
    ];

    /**
     * İlan ilişkisi
     */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }
}
