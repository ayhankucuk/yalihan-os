<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ️ SAB SEALED
 * Buyer Match Log — Eşleşme skorlarını ve gerekçelerini tutar.
 *
 * Context7: Naming rules applied (Avoid forbidden terms).
 */
class BuyerMatchLog extends BaseModel
{
    use HasCountryScope;
    protected $table = 'buyer_match_logs';

    protected $fillable = [
        'ilan_id',
        'buyer_id',
        'talep_id',
        'match_score',
        'price_fit_score',
        'location_fit_score',
        'feature_fit_score',
        'intent_fit_score',
        'churn_risk_score',
        'action_score',
        'reason',
        'metadata',
        'locale',
    ];

    protected $casts = [
        'match_score' => 'decimal:2',
        'price_fit_score' => 'decimal:2',
        'location_fit_score' => 'decimal:2',
        'feature_fit_score' => 'decimal:2',
        'intent_fit_score' => 'decimal:2',
        'churn_risk_score' => 'decimal:2',
        'action_score' => 'decimal:2',
        'metadata' => 'array',
    ];

    // --- Rerationships ---

    /**
     * Eşleşen ilan.
     */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    /**
     * Eşleşen alıcı (kisi).
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'buyer_id');
    }

    /**
     * Eşleşen talep (varsa).
     */
    public function talep(): BelongsTo
    {
        return $this->belongsTo(Talep::class, 'talep_id');
    }
}
