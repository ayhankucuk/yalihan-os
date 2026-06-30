<?php

declare(strict_types=1);

namespace App\Models\SaaS;

use App\Models\BaseModel;

class FeatureFlag extends BaseModel
{
    protected $table = 'feature_flags';

    protected $fillable = [
        'key',
        'is_enabled',
        'rules',
        'description',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'rules' => 'array',
    ];
}
