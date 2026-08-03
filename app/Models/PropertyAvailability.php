<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * PropertyAvailability — Granular daily availability projection model (SSOT)
 *
 * Context7 & SAB Compliant
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
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'property_id' => 'integer',
        'date' => 'date:Y-m-d',
        'is_available' => 'boolean',
        'priority_tier' => 'integer',
        'price' => 'decimal:2',
        'ulke_id' => 'integer',
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
}
