<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnalyticsMetric extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $fillable = [
        'metric_type',
        'metric_name',
        'metric_data',
        'metric_value',
        'source',
        'severity',
        'recorded_at',
    ];

    protected $casts = [
        'metric_data' => 'array',
        'metric_value' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    // Scopes for easy querying
    public function scopeByType($query, $type)
    {
        return $query->where('metric_type', $type);
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }
}
