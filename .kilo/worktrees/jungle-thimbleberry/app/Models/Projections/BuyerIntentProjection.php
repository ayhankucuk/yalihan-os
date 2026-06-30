<?php

namespace App\Models\Projections;

use App\Models\BaseModel;
use App\Models\Kisi;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 🔎 READ MODEL
 * Buyer Intent Projection — Alıcı niyetini ve tercihlerini tutar.
 */
class BuyerIntentProjection extends BaseModel
{
    use HasCountryScope;
    protected $table = 'buyer_intent_projection';

    protected $fillable = [
        'buyer_id',
        'locale',
        'preferred_city',
        'preferred_district',
        'min_budget',
        'max_budget',
        'property_types',
        'room_preferences',
        'feature_preferences',
        'urgency_level',
        'recent_activity_score',
        'last_contact_at',
    ];

    protected $casts = [
        'min_budget' => 'decimal:2',
        'max_budget' => 'decimal:2',
        'property_types' => 'array',
        'room_preferences' => 'array',
        'feature_preferences' => 'array',
        'urgency_level' => 'integer',
        'recent_activity_score' => 'decimal:2',
        'last_contact_at' => 'datetime',
    ];

    /**
     * Alıcı (kisi).
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'buyer_id');
    }
}
