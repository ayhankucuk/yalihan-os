<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Lead Score Model
 */
class AILeadScore extends BaseModel
{
    use HasCountryScope;

    protected $table = 'ai_lead_scores';

    protected $fillable = [
        'lead_id',
        'skor_degeri',
        'skor_etiketi',
        'skor_nedeni',
        'sinyaller',
        'win_probability',
        'hesaplama_tarihi',
        'model_versiyonu',
    ];

    protected $casts = [
        'skor_degeri' => 'integer',
        'sinyaller' => 'array',
        'hesaplama_tarihi' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
