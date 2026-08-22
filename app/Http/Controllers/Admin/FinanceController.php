<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Finance\CommissionCalculator;
use App\Services\Finance\TransactionService;
use App\Services\Finance\BonusCalculator;
use App\Services\Finance\PayoutReadinessService;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function __construct(
        private readonly \App\Services\Finance\YalihanTreasury $treasury,
        private readonly PayoutReadinessService $payoutService
    ) {}

    public function dashboard()
    {
        $metrics = $this->treasury->getAdminDashboardMetrics();

        return view('admin.finance.dashboard', [
            'monthlyRevenue' => $metrics['monthly_revenue'],
            'pendingCommissions' => $metrics['pending_commissions'],
            'approvedUnpaid' => $metrics['approved_unpaid'],
            'unverifiedCount' => $metrics['unverified_count'],
            'unpaidBonuses' => $metrics['unpaid_bonuses']
        ]);
    }

    public function commissions(Request $request)
    {
        $commissions = $this->treasury->getPaginatedCommissions($request->all());
        return view('admin.finance.commissions.index', compact('commissions'));
    }

    public function approveCommission(Request $request, int $commissionId)
    {
        $success = $this->treasury->requestCommissionApproval($commissionId);
        return response()->json(['success' => $success, 'message' => $success ? 'Hakediş onaylandı' : 'Hata']);
    }

    public function payCommission(Request $request, int $commissionId)
    {
        $request->validate(['invoice_number' => 'nullable|string|max:100']);
        $success = $this->treasury->requestCommissionPayment($commissionId, $request->invoice_number);
        return response()->json(['success' => $success, 'message' => $success ? 'Hakediş ödendi' : 'Hata']);
    }

    public function transactions(Request $request)
    {
        $transactions = $this->treasury->getPaginatedTransactions($request->all());
        return view('admin.finance.transactions.index', compact('transactions'));
    }

    public function storeTransaction(Request $request)
    {
        $request->validate([
            'ilan_id' => 'required',
            'transaction_type' => 'required',
            'amount' => 'required|numeric',
            'currency' => 'required',
            'payment_method' => 'required',
            'payment_date' => 'required|date',
        ]);
        $result = $this->treasury->recordTransaction($request->all());
        return response()->json($result, $result['success'] ? 201 : 500);
    }

    public function verifyTransaction(Request $request, int $transactionId)
    {
        $success = $this->treasury->requestPaymentVerification($transactionId);
        return response()->json(['success' => $success, 'message' => $success ? 'Tahsilat doğrulandı' : 'Hata']);
    }

    public function bonuses(Request $request)
    {
        $bonuses = $this->treasury->getPaginatedBonuses($request->all());
        return view('admin.finance.bonuses.index', compact('bonuses'));
    }

    public function payBonus(Request $request, int $bonusId)
    {
        $success = $this->treasury->requestBonusPayment($bonusId);
        return response()->json(['success' => $success, 'message' => $success ? 'Prim ödendi' : 'Hata']);
    }

    public function calculateMonthlyBonuses(Request $request)
    {
        $request->validate(['target_month' => 'required|string|regex:/^\d{4}-\d{2}$/']);
        $results = $this->treasury->batchCalculateMonthlyBonuses($request->target_month);
        return response()->json(['success' => true, 'results' => $results]);
    }

    public function simulateCommission(Request $request)
    {
        return response()->json($this->treasury->simulateCommission((float)$request->sale_price, (float)$request->commission_rate));
    }

    public function simulateBonus(Request $request)
    {
        return response()->json($this->treasury->simulateBonus((float)$request->monthly_target, (float)$request->achieved_amount));
    }

    /**
     * C3.3: Payout Readiness — list all payout-ready reservations.
     */
    public function payoutReady(Request $request)
    {
        $tenantId = (int) ($request->user()?->tenant_id ?? 0);
        if ($tenantId === 0) {
            abort(403, 'Tenant context required');
        }

        $ready = $this->payoutService->getPayoutReadyReservations($tenantId);
        $byOwner = $this->payoutService->getPayoutReadyByOwner($tenantId);

        $totalOwnerEntitlement = array_sum(array_column($byOwner, 'total_entitlement'));

        return view('admin.finance.payout-ready', [
            'reservations' => $ready,
            'byOwner' => $byOwner,
            'totalEntitlement' => $totalOwnerEntitlement,
            'count' => count($ready),
        ]);
    }
}
