<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 💡 AI Learning Signal Model
 * Phase 9: Feedback Loop & Training Data Collection
 */
class AiOgrenmeSinyali extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'ai_ogrenme_sinyalleri';

    // No updated_at in table, only created_at
    const UPDATED_AT = null;

    protected $fillable = [
        'ai_feature_usage_id',
        'kategori_id',
        'yayin_tipi_id',
        'feature_slug',
        'confidence',
        'karar_tipi',
        'skor',
        'context_hash',
        'sinyaller_json',
    ];

    protected $casts = [
        'kategori_id' => 'integer',
        'yayin_tipi_id' => 'integer',
        'confidence' => 'float',
        'skor' => 'integer',
        'sinyaller_json' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relationship to original feature usage log
     */
    public function featureUsage(): BelongsTo
    {
        return $this->belongsTo(AiFeatureUsage::class, 'ai_feature_usage_id');
    }

    /**
     * Relationship to category
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(IlanKategori::class, 'kategori_id');
    }
}
