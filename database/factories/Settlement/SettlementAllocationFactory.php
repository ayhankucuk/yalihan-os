<?php

declare(strict_types=1);

namespace Database\Factories\Settlement;

use App\Enums\AllocationStatus;
use App\Models\Settlement\SettlementAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SettlementAllocation>
 */
class SettlementAllocationFactory extends Factory
{
    protected $model = SettlementAllocation::class;

    public function definition(): array
    {
        $grossAmount = $this->faker->randomFloat(4, 500, 5000);
        $channelFee = $grossAmount * 0.15;

        return [
            'tenant_id' => 1,
            'provider_settlement_id' => null,
            'reservation_id' => null,
            'reconciliation_execution_id' => null,
            'gross_amount' => $grossAmount,
            'channel_fee_amount' => $channelFee,
            'net_amount' => $grossAmount - $channelFee,
            'currency' => 'TRY',
            'allocation_status' => AllocationStatus::PENDING->value,
        ];
    }
}
