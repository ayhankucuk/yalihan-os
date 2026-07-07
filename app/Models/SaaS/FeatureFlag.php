<?php

declare(strict_types=1);

namespace App\Models\SaaS;

use App\Models\BaseModel;
use App\Models\Traits\HasCountryScope;

class FeatureFlag extends BaseModel
{
    use HasCountryScope;

    protected $table = 'feature_flags';

    protected $fillable = [
        'key',
        'aktiflik_durumu',
        'rules',
        'aciklama',
    ];

    protected $casts = [
        'aktiflik_durumu' => 'boolean',
        'rules' => 'array',
    ];
}
