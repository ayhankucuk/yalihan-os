<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * PropertyAvailability — Granular daily availability projection model (SSOT)
 *
 * Sprint 22 E01 — Enhanced with availability_version, origin, projection metadata, conflict_reason
 */
class PropertyAvailability extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'property_availabilities';

    protected $fillable = [
        'tenant_id',
        'property_id',
        'date',
        'is_available',
        'block_reason',
        'priority_tier',
        'idempotency_key',
        'source_system',
        'external_ref',
        'reservation_id',
        'price',
        'ulke_id',
        // Sprint 22 E01 Enhancement fields
        'availability_version',
        'origin',
        'projection_generated_at',
        'projection_source',
        'conflict_reason',
    ];

    protected $casts = [
        'tenant_id'               => 'integer',
        'property_id'             => 'integer',
        'date'                    => 'date:Y-m-d',
        'is_available'            => 'boolean',
        'priority_tier'           => 'integer',
        'price'                   => 'decimal:2',
        'ulke_id'                 => 'integer',
        'availability_version'    => 'integer',
        'projection_generated_at' => 'datetime',
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'property_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(PropertyReservation::class, 'reservation_id');
    }

    /**
     * Scope: Filter by date range [startDate, endDate)
     */
    public function scopeForDateRange($query, string $startDate, string $endDate)
    {
        return $query->where('date', '>=', $startDate)
                     ->where('date', '<', $endDate);
    }

    /**
     * Scope: Active blocks (is_available = false)
     */
    public function scopeBlocked($query)
    {
        return $query->where('is_available', false);
    }

    /**
     * Scope: Filter by tenant (always enforce tenant isolation)
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Filter by origin source
     */
    public function scopeFromOrigin($query, string $origin)
    {
        return $query->where('origin', $origin);
    }

    /**
     * Scope: Records from external sync sources (not internal)
     */
    public function scopeExternalBlocks($query)
    {
        return $query->where('source_system', '!=', 'internal')
                     ->where('is_available', false);
    }
}
