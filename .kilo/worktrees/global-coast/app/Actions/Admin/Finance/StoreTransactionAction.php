<?php

namespace App\Actions\Admin\Finance;

use App\Services\Finance\TransactionService;

class StoreTransactionAction
{
    public function __construct(private readonly TransactionService $transactionService) {}

    public function handle(array $data): array
    {
        return $this->transactionService->recordPayment($data);
    }
}
