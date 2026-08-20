<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PropertyReadiness — Guest Arrival Readiness aggregate model.
 *
 * CHECKIN_CHECKOUT Wave 2
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $reservation_id
 * @property int $ilan_id
 * @property bool $property_clean
 * @property bool $access_credential_ready
 * @property bool $guest_contact_ready
 * @property bool $amenity_check_complete
 * @property bool $welcome_kit_prepared
 * @property bool $is_ready
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PropertyReadiness extends BaseModel
{
    use HasFactory;
    use BelongsToTenant;

    protected $table = 'property_readiness';

    protected $fillable = [
        'tenant_id',
        'reservation_id',
        'ilan_id',
        'property_clean',
        'access_credential_ready',
        'guest_contact_ready',
        'amenity_check_complete',
        'welcome_kit_prepared',
        'is_ready',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'reservation_id' => 'integer',
        'ilan_id' => 'integer',
        'property_clean' => 'boolean',
        'access_credential_ready' => 'boolean',
        'guest_contact_ready' => 'boolean',
        'amenity_check_complete' => 'boolean',
        'welcome_kit_prepared' => 'boolean',
        'is_ready' => 'boolean',
    ];

    /**
     * Dimensions that must all be true for is_ready = true.
     */
    public const REQUIRED_DIMENSIONS = [
        'property_clean',
        'access_credential_ready',
        'guest_contact_ready',
    ];

    /**
     * All dimensions including optional ones.
     */
    public const ALL_DIMENSIONS = [
        'property_clean',
        'access_credential_ready',
        'guest_contact_ready',
        'amenity_check_complete',
        'welcome_kit_prepared',
    ];

    /**
     * Relation: parent reservation.
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(PropertyReservation::class, 'reservation_id');
    }

    /**
     * Relation: parent property (Ilan).
     */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    /**
     * Compute is_ready from dimension fields.
     * Returns true only if all REQUIRED_DIMENSIONS are true.
     */
    public function computeIsReady(): bool
    {
        foreach (self::REQUIRED_DIMENSIONS as $dimension) {
            if (! (bool) $this->{$dimension}) {
                return false;
            }
        }
        return true;
    }

    /**
     * Sync the stored is_ready with the computed value.
     * Returns whether the value changed.
     */
    public function syncIsReady(): bool
    {
        $computed = $this->computeIsReady();
        if ($this->is_ready !== $computed) {
            $this->is_ready = $computed;
            $this->saveQuietly(); // No events
            return true;
        }
        return false;
    }

    /**
     * Get list of dimensions that are still pending.
     *
     * @return string[]
     */
    public function getPendingDimensions(): array
    {
        $pending = [];
        foreach (self::REQUIRED_DIMENSIONS as $dimension) {
            if (! (bool) $this->{$dimension}) {
                $pending[] = $dimension;
            }
        }
        return $pending;
    }

    /**
     * Override save to always recompute is_ready before persisting.
     * This ensures the computed aggregate is always in sync.
     */
    public function save(array $options = []): bool
    {
        $this->is_ready = $this->computeIsReady();
        return parent::save($options);
    }

    /**
     * Mark a specific dimension as complete, recompute is_ready, and persist.
     */
    public function markDimensionComplete(string $dimension): void
    {
        if (!in_array($dimension, self::ALL_DIMENSIONS, true)) {
            throw new \InvalidArgumentException("Unknown dimension: {$dimension}");
        }
        $this->{$dimension} = true;
        $this->is_ready = $this->computeIsReady();
        parent::save();
    }
}
