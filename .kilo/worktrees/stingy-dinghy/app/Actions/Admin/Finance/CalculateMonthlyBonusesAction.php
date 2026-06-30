<?php

namespace App\Actions\Admin\Finance;

use App\Services\Finance\YalihanTreasury;

class CalculateMonthlyBonusesAction
{
    public function __construct(private readonly YalihanTreasury $treasury) {}

    public function handle(string $targetMonth): array
    {
        return $this->treasury->batchCalculateMonthlyBonuses($targetMonth);
    }
}
