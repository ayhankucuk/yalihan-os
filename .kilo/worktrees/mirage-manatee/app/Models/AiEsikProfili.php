<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 🎯 AI Adaptive Threshold Profile Model
 * Phase 9: Dynamically adjusted confidence thresholds
 */
class AiEsikProfili extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'ai_esik_profilleri';

    protected $fillable = [
        'kategori_id',
        'yayin_tipi_id',
        'saglayici',
        'min_ornek_sayisi',
        'auto_apply_esigi',
        'suggest_esigi',
    ];

    protected $casts = [
        'kategori_id' => 'integer',
        'yayin_tipi_id' => 'integer',
        'min_ornek_sayisi' => 'integer',
        'auto_apply_esigi' => 'float',
        'suggest_esigi' => 'float',
    ];

    /**
     * Relationship to category
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(IlanKategori::class, 'kategori_id');
    }
}
