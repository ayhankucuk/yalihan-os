<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisorPhoto extends BaseModel
{
    use HasCountryScope;

    protected $table = 'advisor_photos';

    protected $fillable = [
        'kisi_id',
        'path',
        'filename',
        'mime_type',
        'width',
        'height',
        'file_size',
        'quality_score',
        'quality_metrics',
        'analysis_details',
        'display_order',
        'featured',
        'improvement_suggestions',
        'visual_keywords',
        'analyzed_at',
        'featured_at',
    ];

    protected $casts = [
        'quality_metrics' => 'array',
        'analysis_details' => 'array',
        'improvement_suggestions' => 'array',
        'visual_keywords' => 'array',
        'featured' => 'boolean',
        'display_order' => 'integer',
        'analyzed_at' => 'datetime',
        'featured_at' => 'datetime',
    ];

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'kisi_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
}
