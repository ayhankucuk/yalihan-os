<?php

namespace App\Domain\Shared\ValueObjects;

use Carbon\Carbon;
use DomainException;

class DateRange
{
    private Carbon $startsAt;
    private Carbon $endsAt;

    public function __construct(string|Carbon $startsAt, string|Carbon $endsAt)
    {
        $this->startsAt = Carbon::parse($startsAt)->startOfDay();
        $this->endsAt = Carbon::parse($endsAt)->startOfDay();

        if ($this->endsAt->lessThanOrEqualTo($this->startsAt)) {
            throw new DomainException('End date must be strictly after start date.');
        }
    }

    public function getStartsAt(): Carbon
    {
        return $this->startsAt;
    }

    public function getEndsAt(): Carbon
    {
        return $this->endsAt;
    }

    public function getStartsAtString(): string
    {
        return $this->startsAt->toDateTimeString();
    }

    public function getEndsAtString(): string
    {
        return $this->endsAt->toDateTimeString();
    }

    public function getNights(): int
    {
        return (int) $this->startsAt->diffInDays($this->endsAt);
    }

    /**
     * Checks if this half-open interval [start, end) intersects with another DateRange.
     * Formula: existing.startsAt < requested.endsAt AND existing.endsAt > requested.startsAt
     */
    public function intersectsWith(DateRange $other): bool
    {
        return $this->startsAt->lessThan($other->getEndsAt()) &&
               $this->endsAt->greaterThan($other->getStartsAt());
    }
}
