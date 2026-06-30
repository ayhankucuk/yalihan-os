<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiProviderProfile extends BaseModel
{
    use HasCountryScope;

    protected $fillable = [
        'provider',
        'window',
        'kategori_id',
        'accept_rate',
        'avg_latency_ms',
        'avg_cost_usd',
        'error_rate',
        'cache_hit_rate',
        'sample_size',
        'computed_score',
        'computed_at'
    ];

    protected $casts = [
        'accept_rate' => 'float',
        'avg_latency_ms' => 'integer',
        'avg_cost_usd' => 'float',
        'error_rate' => 'float',
        'cache_hit_rate' => 'float',
        'sample_size' => 'integer',
        'computed_score' => 'float',
        'computed_at' => 'datetime',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(IlanKategori::class, 'kategori_id');
    }
}
