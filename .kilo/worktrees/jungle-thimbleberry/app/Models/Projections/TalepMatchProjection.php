<?php

namespace App\Models\Projections;

use App\Models\BaseModel;
use App\Models\Talep;
use App\Models\Kisi;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 🔎 READ MODEL
 * Talep Match Projection — Talep bazlı eşleşme özelliklerini tutar.
 */
class TalepMatchProjection extends BaseModel
{
    use HasCountryScope;
    protected $table = 'talep_match_projection';

    protected $fillable = [
        'talep_id',
        'buyer_id',
        'city',
        'district',
        'min_price',
        'max_price',
        'room_count',
        'features',
        'property_type',
        'purchase_intent_level',
    ];

    protected $casts = [
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'features' => 'array',
        'purchase_intent_level' => 'integer',
    ];

    /**
     * Talep.
     */
    public function talep(): BelongsTo
    {
        return $this->belongsTo(Talep::class, 'talep_id');
    }

    /**
     * Alıcı.
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'buyer_id');
    }
}
