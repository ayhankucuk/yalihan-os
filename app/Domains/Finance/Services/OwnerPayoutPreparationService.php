<?php

namespace App\Domains\Finance\Services;

use App\Domains\Finance\Events\OwnerPayoutPrepared;
use App\Domains\Finance\Models\OwnerPayout;
use App\Domains\Finance\Models\PayoutReconciliation;
use App\Domains\Finance\ValueObjects\Money;
use App\Domains\Finance\ValueObjects\PayoutPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OwnerPayoutPreparationService
 *
 * EX-002 Finance Agent — WAVE 2 (Application Service)
 *
 * Onaylanmış PayoutReconciliation kayıtlarından ev sahibi ödemelerini hazırlar.
 * Bir dönem ve ilan için tüm reconciliation'ları toplar, OwnerPayout oluşturur.
 */
class OwnerPayoutPreparationService
{
    /**
     * Belirli bir dönem ve ilan için owner payout hazırlar.
     */
    public function prepare(
        int $tenantId,
        int $ilanId,
        PayoutPeriod $period,
        int $preparedBy,
    ): OwnerPayout {
        // Idempotency check
        $idempotencyKey = $period->toIdempotencyKey($tenantId, $ilanId);

        $existing = OwnerPayout::where('idempotency_key', $idempotencyKey)
            ->orderBy('id')
            ->first();

        if ($existing) {
            Log::info('FinanceAgent: Owner payout already exists', [
                'idempotency_key' => $idempotencyKey,
                'existing_id'     => $existing->id,
            ]);
            return $existing;
        }

        // İlgili reconciliation kayıtlarını çek — sadece approved olanlar
        $reconciliations = PayoutReconciliation::forTenant($tenantId)
            ->where('ilan_id', $ilanId)
            ->where('reconciliation_status', PayoutReconciliation::STATUS_APPROVED)
            ->whereHas('payoutImport', function ($q) use ($period): void {
                $q->where('period_start', '>=', $period->getStartDateString())
                  ->where('period_end', '<=', $period->getEndDateString());
            })
            ->get();

        if ($reconciliations->isEmpty()) {
            throw new \RuntimeException(
                "No approved reconciliations found for ilan {$ilanId} in period {$period->format()}"
            );
        }

        // Toplamları hesapla
        $currency = $reconciliations->first()->currency;

        $totalGross      = Money::zero($currency);
        $totalCommission = Money::zero($currency);

        foreach ($reconciliations as $rec) {
            $grossAmount = Money::of((float) $rec->reservation_amount, $rec->currency);
            $commission  = Money::of((float) $rec->yalihan_commission_amount, $rec->currency);

            $totalGross      = $totalGross->add($grossAmount);
            $totalCommission = $totalCommission->add($commission);
        }

        $totalNet = $totalGross->subtract($totalCommission);

        // Ev sahibi kisi_id'sini bul — ilan'dan
        $ilan = \App\Models\Ilan::where('id', $ilanId)
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->first();

        if (!$ilan) {
            throw new \RuntimeException("Ilan {$ilanId} not found for tenant {$tenantId}");
        }

        $ownerKisiId = $ilan->kisi_id ?? null;

        if (!$ownerKisiId) {
            throw new \RuntimeException("Ilan {$ilanId} has no owner (kisi_id is null)");
        }

        // OwnerPayout oluştur
        $ownerPayout = DB::transaction(function () use (
            $tenantId,
            $ownerKisiId,
            $ilanId,
            $idempotencyKey,
            $period,
            $totalGross,
            $totalCommission,
            $totalNet,
            $currency,
            $reconciliations,
            $preparedBy,
        ): OwnerPayout {
            return OwnerPayout::create([
                'tenant_id'                  => $tenantId,
                'owner_kisi_id'              => $ownerKisiId,
                'ilan_id'                    => $ilanId,
                'idempotency_key'            => $idempotencyKey,
                'period_start'               => $period->getStartDateString(),
                'period_end'                 => $period->getEndDateString(),
                'gross_rental_income'        => $totalGross->getAmount(),
                'total_yalihan_commission'   => $totalCommission->getAmount(),
                'net_owner_payout'           => $totalNet->getAmount(),
                'currency'                   => $currency,
                'reconciliation_count'       => $reconciliations->count(),
                'payout_status'              => OwnerPayout::STATUS_DRAFT,
                'prepared_by'                => $preparedBy,
                'prepared_at'                => now(),
            ]);
        });

        Log::info('FinanceAgent: Owner payout prepared', [
            'payout_id'            => $ownerPayout->id,
            'tenant_id'            => $tenantId,
            'ilan_id'              => $ilanId,
            'owner_kisi_id'        => $ownerKisiId,
            'reconciliation_count' => $ownerPayout->reconciliation_count,
            'net_payout'           => $ownerPayout->net_owner_payout,
            'currency'             => $ownerPayout->currency,
        ]);

        OwnerPayoutPrepared::dispatch(
            $ownerPayout->id,
            $tenantId,
            $ownerKisiId,
            $ilanId,
            (float) $ownerPayout->net_owner_payout,
            $ownerPayout->currency,
            $period->getStartDateString(),
            $period->getEndDateString(),
            $ownerPayout->reconciliation_count,
            $preparedBy,
        );

        return $ownerPayout;
    }

    /**
     * Tenant için belirli dönemdeki tüm owner payout'ları listeler.
     */
    public function listForPeriod(int $tenantId, PayoutPeriod $period): \Illuminate\Database\Eloquent\Collection
    {
        return OwnerPayout::forTenant($tenantId)
            ->forPeriod($period->getStartDateString(), $period->getEndDateString())
            ->with('reconciliations')
            ->orderBy('id')
            ->get();
    }

    /**
     * Bir payout'u onaylar.
     */
    public function approve(OwnerPayout $payout, int $approvedBy): void
    {
        if (!$payout->isPendingApproval() && !$payout->isDraft()) {
            throw new \RuntimeException(
                "Owner payout {$payout->id} cannot be approved: status is {$payout->payout_status}"
            );
        }

        $payout->approve($approvedBy);

        Log::info('FinanceAgent: Owner payout approved', [
            'payout_id'   => $payout->id,
            'approved_by' => $approvedBy,
        ]);
    }

    /**
     * Bir payout'u ödendi olarak işaretle.
     */
    public function markAsPaid(OwnerPayout $payout, int $paidBy, string $paymentReference): void
    {
        if (!$payout->isApproved()) {
            throw new \RuntimeException(
                "Owner payout {$payout->id} must be approved before marking as paid"
            );
        }

        $payout->markAsPaid($paidBy, $paymentReference);

        Log::info('FinanceAgent: Owner payout marked as paid', [
            'payout_id'         => $payout->id,
            'paid_by'           => $paidBy,
            'payment_reference' => $paymentReference,
        ]);
    }
}
