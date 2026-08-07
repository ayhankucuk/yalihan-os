<?php

namespace App\Domains\Finance\Services;

use App\Domains\Finance\Events\AirbnbPayoutImported;
use App\Domains\Finance\Models\AirbnbPayoutImport;
use App\Domains\Finance\ValueObjects\CommissionRate;
use App\Domains\Finance\ValueObjects\PayoutPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AirbnbPayoutImportService
 *
 * EX-002 Finance Agent — WAVE 2 (Application Service)
 *
 * Airbnb payout verisini sisteme import eden uygulama servisi.
 * Feature flag kontrolü, idempotency ve reconciliation tetikleme burada.
 */
class AirbnbPayoutImportService
{
    public function __construct(
        private readonly PayoutReconciliationService $reconciliationService,
        private readonly FinanceAgentFeatureFlags $featureFlags,
    ) {}

    /**
     * Yeni bir Airbnb payout import kaydı oluşturur ve reconciliation'ı tetikler.
     *
     * @param array{
     *   airbnb_payout_id: string,
     *   period_start: string,
     *   period_end: string,
     *   gross_amount: float,
     *   airbnb_fees: float,
     *   net_amount: float,
     *   currency: string,
     *   raw_payload: array|null,
     * } $data
     */
    public function import(int $tenantId, array $data, int $importedBy): AirbnbPayoutImport
    {
        if (!$this->featureFlags->isImportEnabled($tenantId)) {
            Log::info('FinanceAgent: Import disabled for tenant', ['tenant_id' => $tenantId]);
            throw new \RuntimeException("Finance Agent import is disabled for tenant {$tenantId}");
        }

        // Idempotency check — aynı airbnb_payout_id ile import varsa geri döndür
        $existing = AirbnbPayoutImport::where('airbnb_payout_id', $data['airbnb_payout_id'])
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->first();

        if ($existing) {
            Log::info('FinanceAgent: Duplicate import skipped', [
                'airbnb_payout_id' => $data['airbnb_payout_id'],
                'tenant_id'        => $tenantId,
                'existing_id'      => $existing->id,
            ]);
            return $existing;
        }

        // Import kaydını oluştur
        $import = DB::transaction(function () use ($tenantId, $data, $importedBy): AirbnbPayoutImport {
            return AirbnbPayoutImport::create([
                'tenant_id'        => $tenantId,
                'airbnb_payout_id' => $data['airbnb_payout_id'],
                'period_start'     => $data['period_start'],
                'period_end'       => $data['period_end'],
                'gross_amount'     => $data['gross_amount'],
                'airbnb_fees'      => $data['airbnb_fees'] ?? 0,
                'net_amount'       => $data['net_amount'],
                'currency'         => $data['currency'] ?? 'TRY',
                'raw_payload'      => $data['raw_payload'] ?? null,
                'import_status'    => AirbnbPayoutImport::STATUS_PENDING,
                'imported_by'      => $importedBy,
                'imported_at'      => now(),
            ]);
        });

        Log::info('FinanceAgent: Payout imported', [
            'import_id'        => $import->id,
            'tenant_id'        => $tenantId,
            'airbnb_payout_id' => $import->airbnb_payout_id,
            'net_amount'       => $import->net_amount,
            'currency'         => $import->currency,
        ]);

        AirbnbPayoutImported::dispatch(
            $import->id,
            $tenantId,
            $import->airbnb_payout_id,
            (float) $import->net_amount,
            $import->currency,
            $import->period_start->toDateString(),
            $import->period_end->toDateString(),
            $importedBy,
        );

        // Auto-reconcile etkinse hemen çalıştır
        if ($this->featureFlags->isAutoReconcileEnabled($tenantId)) {
            $rate = CommissionRate::of(
                $this->featureFlags->getDefaultCommissionRate($tenantId)
            );
            $this->reconciliationService->reconcile($import, $rate);
        }

        return $import;
    }

    /**
     * Mevcut bir import kaydını manuel reconcile eder.
     */
    public function reconcileManually(
        AirbnbPayoutImport $import,
        float $commissionRate,
    ): array {
        if (!$import->isPending() && !$import->isFailed()) {
            throw new \RuntimeException(
                "Import {$import->id} cannot be reconciled: status is {$import->import_status}"
            );
        }

        $rate = CommissionRate::of($commissionRate);

        return $this->reconciliationService->reconcile($import, $rate);
    }

    /**
     * Tenant için belirtilen dönemdeki tüm import'ları listeler.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function listForPeriod(int $tenantId, PayoutPeriod $period): \Illuminate\Database\Eloquent\Collection
    {
        return AirbnbPayoutImport::forTenant($tenantId)
            ->forPeriod($period->getStartDateString(), $period->getEndDateString())
            ->with('reconciliations')
            ->orderBy('id')
            ->get();
    }
}
