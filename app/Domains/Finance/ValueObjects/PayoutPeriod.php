<?php

namespace App\Domains\Finance\ValueObjects;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * PayoutPeriod Value Object
 *
 * EX-002 Finance Agent — WAVE 1
 *
 * Bir ödeme dönemini (başlangıç ve bitiş tarihleri) temsil eder.
 * Idempotency key üretimi ve dönem doğrulaması için kullanılır. Immutable.
 */
final class PayoutPeriod
{
    public function __construct(
        private readonly Carbon $startDate,
        private readonly Carbon $endDate,
    ) {
        if ($endDate->lt($startDate)) {
            throw new InvalidArgumentException(
                "Period end date cannot be before start date: {$startDate->toDateString()} > {$endDate->toDateString()}"
            );
        }
    }

    // ─── Factory ─────────────────────────────────────────────────────────────

    public static function of(string $startDate, string $endDate): self
    {
        return new self(
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        );
    }

    /**
     * Belirtilen ay için dönem oluşturur (YYYY-MM).
     */
    public static function forMonth(string $yearMonth): self
    {
        $date = Carbon::createFromFormat('Y-m', $yearMonth);

        return new self(
            $date->copy()->startOfMonth(),
            $date->copy()->endOfMonth(),
        );
    }

    /**
     * Geçen ay için dönem oluşturur.
     */
    public static function lastMonth(): self
    {
        $lastMonth = Carbon::now()->subMonth();

        return new self(
            $lastMonth->copy()->startOfMonth(),
            $lastMonth->copy()->endOfMonth(),
        );
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    public function getStartDate(): Carbon
    {
        return $this->startDate->copy();
    }

    public function getEndDate(): Carbon
    {
        return $this->endDate->copy();
    }

    public function getStartDateString(): string
    {
        return $this->startDate->toDateString();
    }

    public function getEndDateString(): string
    {
        return $this->endDate->toDateString();
    }

    public function getDays(): int
    {
        return (int) $this->startDate->diffInDays($this->endDate);
    }

    // ─── Idempotency ─────────────────────────────────────────────────────────

    /**
     * Dönem + tenant + ilan için benzersiz idempotency key üretir.
     */
    public function toIdempotencyKey(int $tenantId, int $ilanId): string
    {
        return sprintf(
            'owner-payout-%d-%d-%s-%s',
            $tenantId,
            $ilanId,
            $this->startDate->format('Ymd'),
            $this->endDate->format('Ymd'),
        );
    }

    /**
     * Reconciliation idempotency key üretir.
     */
    public function toReconciliationKey(int $tenantId, int $importId, ?int $reservationId): string
    {
        return sprintf(
            'reconciliation-%d-%d-%s-%s',
            $tenantId,
            $importId,
            $reservationId ?? 'unmatched',
            $this->startDate->format('Ymd'),
        );
    }

    // ─── Comparison ──────────────────────────────────────────────────────────

    public function equals(self $other): bool
    {
        return $this->startDate->eq($other->startDate)
            && $this->endDate->eq($other->endDate);
    }

    public function contains(Carbon $date): bool
    {
        return $date->between($this->startDate, $this->endDate);
    }

    public function overlaps(self $other): bool
    {
        return $this->startDate->lte($other->endDate)
            && $this->endDate->gte($other->startDate);
    }

    // ─── Formatting ──────────────────────────────────────────────────────────

    public function format(): string
    {
        return $this->startDate->format('d.m.Y') . ' – ' . $this->endDate->format('d.m.Y');
    }

    public function toMonthLabel(): string
    {
        return $this->startDate->format('Y-m');
    }

    public function toArray(): array
    {
        return [
            'period_start' => $this->getStartDateString(),
            'period_end'   => $this->getEndDateString(),
        ];
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
