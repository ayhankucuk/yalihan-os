<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 🧬 AI Provider Selection Profile Model
 * Phase 9: Performance-based provider selection logic
 */
class AiSaglayiciProfili extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'ai_saglayici_profilleri';

    protected $fillable = [
        'kategori_id',
        'yayin_tipi_id',
        'saglayici',
        'ort_gecikme_ms',
        'ort_maliyet_usd',
        'kabul_orani',
        'ornek_sayisi',
    ];

    protected $casts = [
        'kategori_id' => 'integer',
        'yayin_tipi_id' => 'integer',
        'ort_gecikme_ms' => 'integer',
        'ort_maliyet_usd' => 'float',
        'kabul_orani' => 'float',
        'ornek_sayisi' => 'integer',
    ];
}
