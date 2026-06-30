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
        'ilan_id',
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
        'depozito_tutari',
        'depozito_durumu',
        'locked_nightly_rate',
        'booking_currency',
        'booking_fx_rate',
        'booking_country_code',
        'ulke_id',
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
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
