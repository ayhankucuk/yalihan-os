<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * Governance Incident Model
 *
 * Persists security violations, registry bypasses, and signature failures.
 * Part of the Sprint 13 Zero-Trust protocol.
 */
class GovernanceIncident extends BaseModel
{
    use HasCountryScope;

    protected $fillable = [
        'olay_tipi',
        'kaynak',
        'snapshot_id',
        'imza_hash',
        'risk_seviyesi',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    /**
     * Enforce Immutability.
     * Incident logs cannot be updated or deleted.
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            return false;
        });

        static::deleting(function ($model) {
            return false;
        });
    }
}
