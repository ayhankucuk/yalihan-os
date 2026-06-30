<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Finance\CommissionCalculator;
use App\Services\Finance\BonusCalculator;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private readonly \App\Services\Finance\YalihanTreasury $treasury
    ) {}

    public function index()
    {
        $metrics = $this->treasury->getAgentWalletMetrics((int)auth()->id());

        return view('admin.wallet.index', [
            'monthlyEarnings' => $metrics['monthly_earnings'],
            'pendingCommissions' => $metrics['pending_commissions'],
            'totalEarnings' => $metrics['total_earnings'],
            'thisMonthSales' => $metrics['this_month_sales'],
            'salesCount' => $metrics['sales_count'],
            'monthlyTarget' => $metrics['monthly_target'],
            'achievementPercentage' => $metrics['achievement_percentage'],
            'projectedBonus' => $metrics['projected_bonus'],
            'unpaidBonuses' => $metrics['unpaid_bonuses']
        ]);
    }

    public function commissions(Request $request)
    {
        $commissions = $this->treasury->getCommissionsList((int)auth()->id(), $request->payment_state);

        if ($request->wantsJson()) {
            return response()->json(['commissions' => $commissions]);
        }
        return view('admin.wallet.commissions', compact('commissions'));
    }

    public function bonuses(Request $request)
    {
        $bonuses = $this->treasury->getAgentBonuses((int)auth()->id());

        if ($request->wantsJson()) {
            return response()->json(['bonuses' => $bonuses]);
        }
        return view('admin.wallet.bonuses', compact('bonuses'));
    }

    public function performance(Request $request)
    {
        // 🧠 Performance calculation is a Capability delegated to the Treasury
        $result = $this->treasury->calculateAgentPerformance(
            (int)auth()->id(), 
            $request->get('month', now()->format('Y-m'))
        );

        return response()->json($result);
    }
}
