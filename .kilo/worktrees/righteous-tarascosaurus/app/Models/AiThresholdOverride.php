<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiThresholdOverride extends BaseModel
{
    use HasCountryScope;

    protected $fillable = [
        'kategori_id',
        'yayin_tipi_id',
        'auto_apply_threshold',
        'suggest_threshold',
        'source',
        'run_id',
        'calculated_at'
    ];

    protected $casts = [
        'auto_apply_threshold' => 'float',
        'suggest_threshold' => 'float',
        'calculated_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AiOptimizationRun::class, 'run_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(IlanKategori::class, 'kategori_id');
    }
}
