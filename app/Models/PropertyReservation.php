<?php

namespace App\Models;

use App\Enums\ReservationState;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyReservation extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $fillable = [
        'tenant_id',
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
        'islem_tutari',
        'currency',
        'created_by_user_id',
        'cancelled_at',
        'confirmed_at',
        // Financial State fields (Money Core Sprint)
        'finansal_durum',
        'depozita_tutari',
        'depozita_durumu',
        'locked_nightly_rate',
        'booking_currency',
        'booking_fx_rate',
        'booking_country_code',
        'ulke_id',
        // ADR-007: Channel Manager Wave 2 — External reservation tracking
        'external_reservation_id',
        'external_channel',
        // PILOT-002 Wave 3 — Override audit trail
        'override_of_id',
        'override_authorized_by',
        'override_occurred_at',
        // CHECKOUT-D1: Operational lifecycle timestamps
        'checked_in_at',
        'checked_out_at',
        'completed_at',
        // CHECKIN_CHECKOUT Wave 2: Guest Arrival Readiness
        'checkin_window_opened_at',
        'arrival_time_estimated',
        'arrival_notes',
    ];

    protected $casts = [
        'tenant_id'        => 'integer',
        'ilan_id'          => 'integer',
        'cancelled_at'     => 'datetime',
        'confirmed_at'     => 'datetime',
        'islem_tutari'     => 'decimal:2',
        'depozito_tutari'  => 'decimal:2',
        'booking_fx_rate'  => 'float',
        'ulke_id'          => 'integer',
        'reservation_state' => ReservationState::class,
        'override_of_id'         => 'integer',
        'override_authorized_by'  => 'integer',
        'override_occurred_at'    => 'datetime',
        // CHECKOUT-D1: Operational lifecycle timestamps
        'checked_in_at'    => 'datetime',
        'checked_out_at'   => 'datetime',
        'completed_at'     => 'datetime',
        // CHECKIN_CHECKOUT Wave 2
        'checkin_window_opened_at' => 'datetime',
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'property_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
