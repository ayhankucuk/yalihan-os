<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase I: AI Feature Coverage Telemetry
 * Updated in Phase 10 for ROI & A/B Experiments
 */
class AiFeatureUsage extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $fillable = [
        'ilan_id',
        'kategori_id',
        'yayin_tipi_id',
        'feature_slug',
        'confidence',
        'source_tipi',
        'aksiyon',
        'neden',
        'neden_detay',
        'explainability_v2_json',
        'istek_id',
        'deney_id',
        'deney_varyasyon_anahtari',
        'etkilesim_suresi_ms',
        'latency_ms',
        'cache_hit',
        'provider',
        'tahmini_tasarruf_sn',
        'maliyet_usd'
    ];

    protected $casts = [
        'ilan_id' => 'integer',
        'kategori_id' => 'integer',
        'yayin_tipi_id' => 'integer',
        'confidence' => 'float',
        'neden_detay' => 'array',
        'explainability_v2_json' => 'array',
        'deney_id' => 'integer',
        'etkilesim_suresi_ms' => 'integer',
        'tahmini_tasarruf_sn' => 'float',
        'maliyet_usd' => 'float'
    ];

    /**
     * Get related experiment
     */
    public function experiment(): BelongsTo
    {
        return $this->belongsTo(AiExperiment::class, 'deney_id');
    }

    /**
     * Content types enum
     */
    public const TYPE_TITLE = 'ilan_ai_title';
    public const TYPE_DESCRIPTION = 'ilan_ai_description';
    public const TYPE_QUALITY_CHECK = 'ilan_quality_check';
    public const TYPE_PUBLISH_DECISION = 'ilan_publish_decision';
}
