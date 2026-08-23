<?php

declare(strict_types=1);

namespace Database\Factories\Settlement;

use App\Enums\PayoutStatus;
use App\Enums\PayoutType;
use App\Enums\SettlementStatus;
use App\Enums\VccStatus;
use App\Models\Settlement\ProviderSettlement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderSettlement>
 */
class ProviderSettlementFactory extends Factory
{
    protected $model = ProviderSettlement::class;

    public function definition(): array
    {
        $grossAmount = $this->faker->randomFloat(4, 500, 5000);
        $channelFee = $grossAmount * 0.15;
        $netAmount = $grossAmount - $channelFee;

        return [
            'tenant_id' => 1,
            'provider' => $this->faker->randomElement(['booking_com', 'airbnb', 'expedia']),
            'external_settlement_id' => 'PS-' . $this->faker->unique()->uuid(),
            'external_reservation_id' => 'RES-' . $this->faker->numberBetween(100000, 999999),
            'reservation_id' => null,
            'gross_amount' => $grossAmount,
            'channel_fee_amount' => $channelFee,
            'net_amount' => $netAmount,
            'currency' => 'TRY',
            'payout_type' => PayoutType::NET->value,
            'payout_status' => PayoutStatus::PENDING->value,
            'bank_transfer_reference' => null,
            'payout_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'value_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            // VCC fields (C5.1-D01 Recovery: Booking.com wire contract)
            'vcc_status' => null,
            'vcc_reference' => null,
            'vcc_charged_amount' => null,
            'vcc_charge_date' => null,
            'vcc_currency' => null,
            'raw_payload' => ['source' => 'api', 'version' => '2.0'],
            'raw_source' => 'api',
            'settlement_status' => SettlementStatus::PENDING->value,
            'allocated_to_id' => null,
            'idempotency_key' => null,
        ];
    }

    public function forReservation(int $reservationId): self
    {
        return $this->state(fn (array $attrs) => ['reservation_id' => $reservationId]);
    }

    public function reconciled(): self
    {
        return $this->state(fn (array $attrs) => [
            'settlement_status' => SettlementStatus::RECONCILED->value,
        ]);
    }

    public function paid(): self
    {
        return $this->state(fn (array $attrs) => [
            'payout_status' => PayoutStatus::PAID->value,
        ]);
    }
}
