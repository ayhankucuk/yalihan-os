<?php

namespace App\DTOs\Reservation;

/**
 * ValidityResult — Result of a check-in validity check.
 *
 * CHECKIN_CHECKOUT Wave 2
 */
final readonly class ValidityResult
{
    private function __construct(
        public bool $canCheckIn,
        public ?string $blockedReason,
        public ?string $blockedCode,
    ) {}

    /**
     * Reservation is ready for check-in.
     */
    public static function ready(): self
    {
        return new self(
            canCheckIn: true,
            blockedReason: null,
            blockedCode: null,
        );
    }

    /**
     * Reservation cannot check in — blocked by a condition.
     */
    public static function blocked(string $code, string $reason): self
    {
        return new self(
            canCheckIn: false,
            blockedReason: $reason,
            blockedCode: $code,
        );
    }

    /**
     * @return array{canCheckIn: bool, blockedReason: string|null, blockedCode: string|null}
     */
    public function toArray(): array
    {
        return [
            'canCheckIn' => $this->canCheckIn,
            'blockedReason' => $this->blockedReason,
            'blockedCode' => $this->blockedCode,
        ];
    }
}
