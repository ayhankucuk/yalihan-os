<?php

namespace App\Http\Controllers\Api\Advisor;

use App\Http\Controllers\Controller;
use App\Services\FinancialLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function __construct(
        private readonly FinancialLedgerService $ledgerService
    ) {}

    /**
     * GET /api/advisor/ledger/balance/{accountId}
     *
     * Returns the CQRS projection-based balance for a ledger account.
     * Reads from ledger_balances projection table (NOT raw entries).
     */
    public function balance(int $accountId, Request $request): JsonResponse
    {
        $account = $this->ledgerService->findAccount($accountId);
 
        if (!$account) {
            return response()->json([
                'durum' => 'error',
                'code' => 'ACCOUNT_NOT_FOUND',
                'message' => 'Hesap bulunamadı.',
            ], 404);
        }
 
        $currency = $request->query('currency', 'TRY');
 
        $projection = $this->ledgerService->getProjection($accountId, $currency);
 
        return response()->json([
            'durum' => 'success',
            'data' => $this->ledgerService->formatBalanceProjection($account, $currency, $projection),
        ]);
    }
 
    /**
     * GET /api/advisor/ledger/accounts
     *
     * Lists all ledger accounts with their current balances from projection.
     */
    public function accounts(): JsonResponse
    {
        return response()->json([
            'durum' => 'success',
            'data' => $this->ledgerService->getAccountsSummary(),
        ]);
    }
}
