<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * RuleDefinition Model
 *
 * Represents a single rule in the V3 resolution engine.
 */
class RuleDefinition extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $fillable = [
        'name',
        'rule_type',
        'rule_config',
        'priority',
        'version_id',
        'is_active',
        'tenant_id',
    ];

    protected $casts = [
        'rule_config' => 'array', // Auto JSON decode
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'priority' => 'integer',
    ];


    /**
     * Relationship: Belongs to a config version.
     */
    public function version()
    {
        return $this->belongsTo(PropertyConfigVersion::class, 'version_id');
    }
}
