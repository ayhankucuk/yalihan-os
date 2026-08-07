<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Finance\Models\AirbnbPayoutImport;
use App\Domains\Finance\Models\OwnerPayout;
use App\Domains\Finance\Services\AirbnbPayoutImportService;
use App\Domains\Finance\Services\FinanceAgentFeatureFlags;
use App\Domains\Finance\Services\OwnerPayoutPreparationService;
use App\Domains\Finance\ValueObjects\PayoutPeriod;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * FinanceAgentController
 *
 * EX-002 Finance Agent — WAVE 4
 *
 * Thin Controller: validate + delegate only.
 * Tüm iş mantığı domain service katmanındadır.
 */
class FinanceAgentController extends Controller
{
    public function __construct(
        private readonly AirbnbPayoutImportService    $importService,
        private readonly OwnerPayoutPreparationService $payoutService,
        private readonly FinanceAgentFeatureFlags     $featureFlags,
    ) {}

    // ─── Payout Imports ──────────────────────────────────────────────────────

    public function importsIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $period = $request->filled('period')
            ? PayoutPeriod::forMonth($request->input('period'))
            : PayoutPeriod::lastMonth();

        $imports = $this->importService->listForPeriod($tenantId, $period);

        return view('admin.finance-agent.imports.index', [
            'imports' => $imports,
            'period'  => $period,
        ]);
    }

    public function importStore(Request $request)
    {
        $validated = $request->validate([
            'airbnb_payout_id' => 'required|string|max:255',
            'period_start'     => 'required|date',
            'period_end'       => 'required|date|after_or_equal:period_start',
            'gross_amount'     => 'required|numeric|min:0',
            'airbnb_fees'      => 'nullable|numeric|min:0',
            'net_amount'       => 'required|numeric|min:0',
            'currency'         => 'nullable|string|size:3',
        ]);

        $tenantId = auth()->user()->tenant_id;

        $import = $this->importService->import(
            $tenantId,
            $validated,
            auth()->id(),
        );

        return redirect()
            ->route('admin.finance-agent.imports.show', $import->id)
            ->with('success', 'Payout import edildi: #' . $import->id);
    }

    public function importShow(int $importId)
    {
        $tenantId = auth()->user()->tenant_id;

        $import = AirbnbPayoutImport::forTenant($tenantId)
            ->with('reconciliations')
            ->where('id', $importId)
            ->orderBy('id')
            ->firstOrFail();

        return view('admin.finance-agent.imports.show', [
            'import' => $import,
        ]);
    }

    public function importReconcile(Request $request, int $importId)
    {
        $validated = $request->validate([
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        $tenantId = auth()->user()->tenant_id;

        $import = AirbnbPayoutImport::forTenant($tenantId)
            ->where('id', $importId)
            ->orderBy('id')
            ->firstOrFail();

        $stats = $this->importService->reconcileManually(
            $import,
            (float) $validated['commission_rate'],
        );

        return redirect()
            ->route('admin.finance-agent.imports.show', $importId)
            ->with('success', "Reconciliation tamamlandı. Eşleşen: {$stats['reconciled']}, Eşleşmeyen: {$stats['unmatched']}");
    }

    // ─── Owner Payouts ───────────────────────────────────────────────────────

    public function payoutsIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $period = $request->filled('period')
            ? PayoutPeriod::forMonth($request->input('period'))
            : PayoutPeriod::lastMonth();

        $payouts = $this->payoutService->listForPeriod($tenantId, $period);

        return view('admin.finance-agent.payouts.index', [
            'payouts' => $payouts,
            'period'  => $period,
        ]);
    }

    public function payoutApprove(Request $request, int $payoutId)
    {
        $tenantId = auth()->user()->tenant_id;

        $payout = OwnerPayout::forTenant($tenantId)
            ->where('id', $payoutId)
            ->orderBy('id')
            ->firstOrFail();

        $this->payoutService->approve($payout, auth()->id());

        return redirect()
            ->route('admin.finance-agent.payouts.index')
            ->with('success', "Ödeme onaylandı: #{$payoutId}");
    }

    public function payoutMarkPaid(Request $request, int $payoutId)
    {
        $validated = $request->validate([
            'payment_reference' => 'required|string|max:255',
        ]);

        $tenantId = auth()->user()->tenant_id;

        $payout = OwnerPayout::forTenant($tenantId)
            ->where('id', $payoutId)
            ->orderBy('id')
            ->firstOrFail();

        $this->payoutService->markAsPaid(
            $payout,
            auth()->id(),
            $validated['payment_reference'],
        );

        return redirect()
            ->route('admin.finance-agent.payouts.index')
            ->with('success', "Ödeme gerçekleştirildi: #{$payoutId}");
    }
}
