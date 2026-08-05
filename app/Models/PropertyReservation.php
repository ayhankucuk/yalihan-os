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

    // ========================================================================
    // RESERVATION_CORE Phase 1: State Transition Methods
    // ========================================================================

    /**
     * Valid state transitions matrix
     */
    private static function validTransitions(): array
    {
        return [
            ReservationState::PENDING->value => [
                ReservationState::CONFIRMED->value,
                ReservationState::CANCELLED->value,
            ],
            ReservationState::CONFIRMED->value => [
                ReservationState::CANCELLED->value,
                ReservationState::COMPLETED->value,
                ReservationState::NO_SHOW->value,
            ],
            // Terminal states: no transitions allowed
            ReservationState::CANCELLED->value => [],
            ReservationState::COMPLETED->value => [],
            ReservationState::NO_SHOW->value => [],
        ];
    }

    /**
     * Check if transition is valid
     */
    public function canTransitionTo(ReservationState $newState): bool
    {
        $transitions = self::validTransitions();
        $current = $this->reservation_state->value;

        return in_array($newState->value, $transitions[$current] ?? []);
    }

    /**
     * Confirm reservation
     *
     * @throws \InvalidArgumentException
     */
    public function confirm(): self
    {
        return $this->transitionTo(ReservationState::CONFIRMED);
    }

    /**
     * Cancel reservation
     *
     * @throws \InvalidArgumentException
     */
    public function cancel(): self
    {
        $this->cancelled_at = now();
        return $this->transitionTo(ReservationState::CANCELLED);
    }

    /**
     * Mark as completed (checkout)
     *
     * @throws \InvalidArgumentException
     */
    public function complete(): self
    {
        return $this->transitionTo(ReservationState::COMPLETED);
    }

    /**
     * Mark as no-show
     *
     * @throws \InvalidArgumentException
     */
    public function markNoShow(): self
    {
        return $this->transitionTo(ReservationState::NO_SHOW);
    }

    /**
     * Transition to new state
     *
     * @throws \InvalidArgumentException
     */
    public function transitionTo(ReservationState $newState): self
    {
        if (!$this->canTransitionTo($newState)) {
            throw new \InvalidArgumentException(
                "Invalid state transition from '{$this->reservation_state->value}' to '{$newState->value}'"
            );
        }

        $this->reservation_state = $newState;

        if ($newState === ReservationState::CONFIRMED) {
            $this->confirmed_at = now();
        }

        if ($newState === ReservationState::CANCELLED) {
            $this->cancelled_at = now();
        }

        return $this;
    }

    /**
     * Check if reservation is active (not cancelled/completed/no_show)
     */
    public function isActive(): bool
    {
        return in_array($this->reservation_state, [
            ReservationState::PENDING,
            ReservationState::CONFIRMED,
        ]);
    }

    /**
     * Check if reservation is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->reservation_state === ReservationState::CANCELLED;
    }

    /**
     * Check if reservation is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->reservation_state === ReservationState::CONFIRMED;
    }
}
