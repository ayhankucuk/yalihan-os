<?php

namespace App\Actions\Admin\Finance;

use App\Services\Finance\BonusCalculator;

class PayBonusAction
{
    public function __construct(private readonly BonusCalculator $bonusCalc) {}

    public function handle(int $bonusId): bool
    {
        return $this->bonusCalc->markAsPaid($bonusId);
    }
}
