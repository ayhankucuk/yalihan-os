<?php

namespace App\Actions\Admin\Finance;

use App\Services\Finance\CommissionCalculator;

class ApproveCommissionAction
{
    public function __construct(private readonly CommissionCalculator $commissionCalc) {}

    public function handle(int $commissionId): bool
    {
        return $this->commissionCalc->approveCommission($commissionId);
    }
}
