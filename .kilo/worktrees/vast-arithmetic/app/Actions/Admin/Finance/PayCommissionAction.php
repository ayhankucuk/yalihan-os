<?php

namespace App\Actions\Admin\Finance;

use App\Services\Finance\CommissionCalculator;

class PayCommissionAction
{
    public function __construct(private readonly CommissionCalculator $commissionCalc) {}

    public function handle(int $commissionId, ?string $invoiceNumber): bool
    {
        return $this->commissionCalc->markAsPaid($commissionId, $invoiceNumber);
    }
}
