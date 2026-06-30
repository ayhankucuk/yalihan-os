<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Services\AI\AiWalletService;
use App\Models\AI\AiTransaction;
use Illuminate\Http\Request;

class AiUsageController extends AdminController
{
    protected AiWalletService $walletService;

    public function __construct(AiWalletService $wallet)
    {
        parent::__construct();
        $this->walletService = $wallet;
        $this->middleware('can:manage-settings');
    }

    /**
     * AI Usage & Billing Dashboard
     */
    public function index(Request $request)
    {
        $tenantId = config('ai.defaults.tenant_id', 1);
        $balance = $this->walletService->getBalance($tenantId);

        // Fetch recent transactions
        $transactions = AiTransaction::where('tenant_id', $tenantId)
            ->latest()
            ->paginate(20);

        $stats = AiTransaction::where('tenant_id', $tenantId)
            ->where('amount', '<', 0)
            ->get(['reason', 'amount'])
            ->groupBy('reason')
            ->map(function ($items, $reason) {
                return [
                    'reason' => $reason,
                    'total_spend' => $items->sum(function ($item) {
                        return abs((float) $item->amount);
                    }),
                    'count' => $items->count(),
                ];
            })
            ->values();

        return view('admin.ai.usage', compact('balance', 'transactions', 'stats'));
    }

    /**
     * Mock Credit Top-up
     */
    public function topUp(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:100|max:10000',
        ]);

        $tenantId = config('ai.defaults.tenant_id', 1);

        $this->walletService->addCredits($tenantId, $request->amount, 'credit_purchase', 'Kredi Yükleme (Mock)');

        return back()->with('success', $request->amount . ' AI kredisi hesabınıza yüklendi.');
    }
}
