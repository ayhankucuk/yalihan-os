<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * DEPRECATED — Use PropertyReservation instead.
 *
 * IlanReservation was a parallel model pointing to property_reservations table.
 * It had the same table but a different fillable set and missing tenant_id.
 *
 * SSOT consolidation (Sprint 5.2 — 2026-07-06):
 * property_reservations table → PropertyReservation is the sole authoritative model.
 *
 * @deprecated 2026-07-06 — SSOT: use App\Models\PropertyReservation
 * @see PropertyReservation
 */
class IlanReservation extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'property_reservations';

    /**
     * SSOT Rule: All writes must go through PropertyReservation.
     * IlanReservation exists only for backward compatibility with existing code.
     * New code must NOT use this model.
     *
     * @deprecated 2026-07-06
     */
    protected $fillable = [
        'property_id',
        'start_date',
        'end_date',
        'nights',
        'guest_name',
        'guest_phone',
        'guest_email',
        'guest_count',
        'notes',
        'reservation_state',
        'finansal_durum',
        'depozito_tutari',
        'depozito_durumu',
        'locked_nightly_rate',
        'booking_currency',
        'booking_fx_rate',
        'booking_country_code',
        'total_amount',
        'currency',
        'created_by_user_id',
        'ulke_id',
        'cancelled_at',
        'confirmed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'cancelled_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'nights' => 'integer',
        'guest_count' => 'integer',
        'depozito_tutari' => 'float',
        'locked_nightly_rate' => 'float',
        'booking_fx_rate' => 'float',
        'total_amount' => 'float',
    ];

    /**
     * @deprecated 2026-07-06 — Use PropertyReservation::ilan()
     */
    public function ilan()
    {
        return $this->belongsTo(Ilan::class, 'property_id');
    }

    /**
     * @deprecated 2026-07-06 — Use PropertyReservation::creator()
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('reservation_state', ['confirmed', 'pending'])
                     ->whereNull('cancelled_at');
    }

    public function scopeCancelled($query)
    {
        return $query->whereNotNull('cancelled_at');
    }

    public function scopeForIlan($query, int $ilanId)
    {
        return $query->where('property_id', $ilanId);
    }
}
