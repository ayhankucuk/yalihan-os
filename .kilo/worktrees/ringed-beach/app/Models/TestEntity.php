<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * 🛡️ TestEntity
 * Purely for Governance Lifecycle Verification & CLI Demonstration.
 */
class TestEntity extends BaseModel
{
    use HasCountryScope;

    protected $table = 'test_entities';

    protected $fillable = [
        'name',
        'payload',
        'published_payload',
        'governance_state',
        'ulke_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'published_payload' => 'array',
    ];
}
