<?php

namespace App\Actions\Admin\Finance;

use App\Services\Finance\TransactionService;

class VerifyTransactionAction
{
    public function __construct(private readonly TransactionService $transactionService) {}

    public function handle(int $transactionId): bool
    {
        return $this->transactionService->verifyPayment($transactionId);
    }
}
