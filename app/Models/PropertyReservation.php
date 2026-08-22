<?php

namespace App\Models;

use App\Enums\ReservationState;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\TakimYonetimi\Models\Gorev;

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
        'total_amount',  // B1: test/production schema compatibility
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
        // C3.1: Management Agreement Snapshot (immutable at booking time)
        'management_model_snapshot',
        'commission_rate_snapshot',
    ];

    protected $casts = [
        'tenant_id'        => 'integer',
        'ilan_id'          => 'integer',
        'cancelled_at'     => 'datetime',
        'confirmed_at'     => 'datetime',
        'islem_tutari'     => 'decimal:2',
        'total_amount'     => 'decimal:2',  // B1: test/production schema compatibility
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
        // C3.1: Management Agreement Snapshot
        'management_model_snapshot' => \App\Enums\ManagementModel::class,
        'commission_rate_snapshot' => 'float',          // DECIMAL(5,4) → float (fraction, e.g. 0.1500)
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'property_id')->withoutGlobalScopes();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Wave 6: Guest arrival readiness aggregate relation
     */
    public function readiness(): HasOne
    {
        return $this->hasOne(PropertyReadiness::class, 'reservation_id')->withoutGlobalScopes();
    }

    /**
     * Wave 6: All operational tasks associated with this reservation
     */
    public function operationalTasks(): HasMany
    {
        return $this->hasMany(Gorev::class, 'reservation_id');
    }

    /**
     * Wave 6: Pre-arrival preparation task
     */
    public function prepTask(): HasOne
    {
        return $this->hasOne(Gorev::class, 'reservation_id')->where('gorev_tipi', 'hazirlik');
    }

    /**
     * Wave 6: Post-checkout turnover cleaning task
     */
    public function turnoverTask(): HasOne
    {
        return $this->hasOne(Gorev::class, 'reservation_id')->where('gorev_tipi', 'temizlik');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // C3.1: Management Agreement Snapshot Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Whether this reservation has a C3.1 management snapshot.
     */
    public function hasManagementSnapshot(): bool
    {
        return $this->management_model_snapshot !== null
            && $this->commission_rate_snapshot !== null;
    }

    /**
     * Get the snapshot model as enum instance.
     */
    public function getSnapshotModelEnum(): ?\App\Enums\ManagementModel
    {
        $val = $this->management_model_snapshot;
        if ($val === null) {
            return null;
        }
        if ($val instanceof \App\Enums\ManagementModel) {
            return $val;
        }
        return \App\Enums\ManagementModel::tryFrom((string) $val);
    }

    /**
     * Compute owner entitlement from snapshot.
     * gross_booking_amount × (1 − commission_rate_snapshot)
     *
     * Returns null if no snapshot exists (legacy reservation).
     */
    public function computeOwnerEntitlement(?float $grossAmount = null): ?float
    {
        if (!$this->hasManagementSnapshot()) {
            return null;
        }

        $gross = $grossAmount ?? (float) $this->islem_tutari;
        $rate = (float) $this->commission_rate_snapshot;

        return $gross * (1 - $rate);
    }

    /**
     * Compute Yalihan commission from snapshot.
     * gross_booking_amount × commission_rate_snapshot
     *
     * Returns null if no snapshot exists.
     */
    public function computeYalihanCommission(?float $grossAmount = null): ?float
    {
        if (!$this->hasManagementSnapshot()) {
            return null;
        }

        $gross = $grossAmount ?? (float) $this->islem_tutari;
        $rate = (float) $this->commission_rate_snapshot;

        return $gross * $rate;
    }
}
