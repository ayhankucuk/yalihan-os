<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ChannelFeeBearer;
use App\Enums\ChannelFeeSource;
use App\Enums\ManagementModel;
use App\Models\Ilan;
use App\Models\PropertyReservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyReservation>
 */
class PropertyReservationFactory extends Factory
{
    protected $model = PropertyReservation::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-60 days', '+60 days');
        $end = (clone $start)->modify('+3 days');

        return [
            'tenant_id' => 1,
            'property_id' => null, // must be set via forProperty() or forIlan()
            'ulke_id' => 1,
            'start_date' => $start,
            'end_date' => $end,
            'nights' => 3,
            'guest_name' => $this->faker->name(),
            'guest_phone' => $this->faker->phoneNumber(),
            'guest_email' => $this->faker->safeEmail(),
            'guest_count' => $this->faker->numberBetween(1, 6),
            'reservation_state' => 'pending',
            'finansal_durum' => 'pending',
            'total_amount' => $this->faker->randomFloat(2, 1000, 10000),
            'currency' => 'TRY',
            // C3.1
            'management_model_snapshot' => ManagementModel::FULL_MANAGEMENT,
            'commission_rate_snapshot' => 0.15,
            // C4.1 defaults
            'channel_fee_amount' => null,
            'channel_fee_currency' => 'TRY',
            'channel_fee_rate' => null,
            'channel_fee_source' => ChannelFeeSource::UNKNOWN,
            'channel_fee_bearer' => ChannelFeeBearer::OWNER_BORNE,
            'channel_fee_captured_at' => null,
            'channel_fee_is_verified' => false,
        ];
    }

    public function forIlan(Ilan $ilan): self
    {
        return $this->state(fn (array $attrs) => [
            'property_id' => $ilan->id,
            'tenant_id' => $ilan->tenant_id,
            'ulke_id' => $ilan->ulke_id ?? 1,
        ]);
    }

    public function forProperty(int $propertyId, int $tenantId = 1): self
    {
        return $this->state(fn (array $attrs) => [
            'property_id' => $propertyId,
            'tenant_id' => $tenantId,
            'ulke_id' => 1,
        ]);
    }

    public function forTenant(int $tenantId): self
    {
        return $this->state(fn (array $attrs) => [
            'tenant_id' => $tenantId,
            'ulke_id' => 1,
        ]);
    }

    public function confirmed(): self
    {
        return $this->state(fn (array $attrs) => [
            'reservation_state' => 'confirmed',
            'finansal_durum' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }
}
