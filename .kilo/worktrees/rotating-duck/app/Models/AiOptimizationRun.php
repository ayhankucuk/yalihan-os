<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

class AiOptimizationRun extends BaseModel
{
    use HasCountryScope;

    protected $fillable = [
        'window',
        'changed_count',
        'diff_json',
        'executed_by',
        'started_at',
        'ended_at'
    ];

    protected $casts = [
        'diff_json' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function overrides()
    {
        return $this->hasMany(AiThresholdOverride::class, 'run_id');
    }
}
