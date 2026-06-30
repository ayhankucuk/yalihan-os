<?php

namespace App\Services\Calendar;

/**
 * @sab-ignore-catch
 */

use Carbon\Carbon;

class CancellationPolicyService
{
    /**
     * Calculate refund according to cancellation policy and timing.
     *
     * Inputs (array keys):
     * - policy: 'flexible' | 'moderate' | 'strict'
     * - check_in: Carbon|string (ISO)  // Reservation start date/time
     * - cancel_at: Carbon|string (ISO) // Cancellation date/time
     * - total_price: float             // Full reservation price
     * - cleaning_fee: float            // Non-refundable portion if policy defines
     * - service_fee: float             // Platform/service fee (usually non-refundable)
     * - security_deposit: float        // Refundable deposit (refunded unless damage)
     * - paid_amount: float             // Amount already paid by guest
     *
     * Returns:
     * - refund_amount: float
     * - penalty_amount: float
     * - breakdown: array
     */
    public function calculateRefund(array $inputs): array
    {
        $policy = $inputs['policy'] ?? 'moderate';
        $checkIn = $this->toCarbon($inputs['check_in'] ?? null);
        $cancelAt = $this->toCarbon($inputs['cancel_at'] ?? Carbon::now());
        $total = (float)($inputs['total_price'] ?? 0);
        $cleaningFee = (float)($inputs['cleaning_fee'] ?? 0);
        $serviceFee = (float)($inputs['service_fee'] ?? 0);
        $deposit = (float)($inputs['security_deposit'] ?? 0);
        $paid = (float)($inputs['paid_amount'] ?? 0);

        // Validate
        if (!$checkIn) {
            return $this->result(0, $paid, [
                'error' => 'check_in_required',
            ]);
        }

        // Time differences
        $hoursBefore = $cancelAt->diffInHours($checkIn, false); // POSITIVE if cancel BEFORE check-in

        // Base refundable components
        $refundableBase = max(0, $total - $serviceFee - $cleaningFee);
        $refundableDeposit = $deposit; // assume refundable unless specific rules override

        // Policy matrices
        $refundRate = 0.0;
        switch ($policy) {
            case 'flexible':
                // >=7 days before: 100% (except service/cleaning non-refundable)
                // 1-6 days before: 50%
                // <24h before or after check-in: 0%
                if ($hoursBefore >= 7 * 24) {
                    $refundRate = 1.0;
                } elseif ($hoursBefore >= 24) {
                    $refundRate = 0.5;
                } else {
                    $refundRate = 0.0;
                }
                break;
            case 'moderate':
                // >=14 days before: 100%
                // 2-13 days before: 50%
                // <48h before or after check-in: 0%
                if ($hoursBefore >= 14 * 24) {
                    $refundRate = 1.0;
                } elseif ($hoursBefore >= 48) {
                    $refundRate = 0.5;
                } else {
                    $refundRate = 0.0;
                }
                break;
            case 'strict':
                // >=30 days before: 50%
                // <30 days before or after check-in: 0%
                if ($hoursBefore >= 30 * 24) {
                    $refundRate = 0.5;
                } else {
                    $refundRate = 0.0;
                }
                break;
            default:
                $refundRate = 0.0;
                break;
        }

        $refundableCore = round($refundableBase * $refundRate, 2);
        $refund = $refundableCore + $refundableDeposit;

        // Final refund cannot exceed paid amount
        $refund = min($refund, $paid);
        $penalty = max(0, $paid - $refund);

        return $this->result($refund, $penalty, [
            'policy' => $policy,
            'hours_before_check_in' => $hoursBefore,
            'refund_rate' => $refundRate,
            'components' => [
                'core_refund' => $refundableCore,
                'deposit_refund' => $refundableDeposit,
                'non_refundable' => round($serviceFee + $cleaningFee, 2),
            ],
        ]);
    }

    private function toCarbon($value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value);
            } catch (\Throwable $e) {
                return null;
            }
        }
        return null;
    }

    private function result(float $refund, float $penalty, array $breakdown): array
    {
        return [
            'refund_amount' => round($refund, 2),
            'penalty_amount' => round($penalty, 2),
            'breakdown' => $breakdown,
        ];
    }
}
