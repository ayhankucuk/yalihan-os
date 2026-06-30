<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ️ SAB SEALED
 * Buyer Match Snapshot — İlan bazlı eşleşme özetlerini tutar.
 */
class BuyerMatchSnapshot extends BaseModel
{
    use HasCountryScope;
    protected $table = 'buyer_match_snapshots';

    protected $fillable = [
        'ilan_id',
        'total_candidates',
        'top_match_score',
        'top_buyer_id',
        'metadata',
    ];

    protected $casts = [
        'total_candidates' => 'integer',
        'top_match_score' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * İlan.
     */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    /**
     * En yüksek skorlu alıcı.
     */
    public function topBuyer(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'top_buyer_id');
    }
}
