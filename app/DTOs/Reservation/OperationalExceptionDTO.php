<?php

namespace App\DTOs\Reservation;

/**
 * OperationalExceptionDTO — Value Object representing a deterministic operational exception.
 *
 * WAVE 7 Phase A — Zero-mutation / Zero-side-effect DTO.
 */
class OperationalExceptionDTO
{
    public const SEVERITY_P0 = 'P0'; // Critical / Immediate action
    public const SEVERITY_P1 = 'P1'; // Warning / Attention required

    public const CODE_EXC_01 = 'IMMINENT_ARRIVAL_UNREADY';
    public const CODE_EXC_02 = 'MISSING_ACCESS_CREDENTIAL';
    public const CODE_EXC_03 = 'OVERDUE_CHECKIN';
    public const CODE_EXC_04 = 'OVERDUE_CHECKOUT';
    public const CODE_EXC_05 = 'UNSTARTED_TURNOVER';
    public const CODE_EXC_06 = 'BACK_TO_BACK_TURNOVER_RISK';

    public function __construct(
        public readonly string $code,
        public readonly string $severity,
        public readonly string $title,
        public readonly string $reason,
        public readonly int $reservationId,
        public readonly ?int $propertyId = null,
        public readonly ?array $metadata = []
    ) {}

    public function isP0(): bool
    {
        return $this->severity === self::SEVERITY_P0;
    }

    public function isP1(): bool
    {
        return $this->severity === self::SEVERITY_P1;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'severity' => $this->severity,
            'title' => $this->title,
            'reason' => $this->reason,
            'reservation_id' => $this->reservationId,
            'property_id' => $this->propertyId,
            'metadata' => $this->metadata,
        ];
    }
}
