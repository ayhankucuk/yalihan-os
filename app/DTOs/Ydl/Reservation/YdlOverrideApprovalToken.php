<?php

namespace App\DTOs\Ydl\Reservation;

/**
 * YdlOverrideApprovalToken — Human approval token for conflict override.
 *
 * PILOT-002 Wave 3
 *
 * @readonly
 */
final class YdlOverrideApprovalToken
{
    public const DEFAULT_TTL_SECONDS = 86400;

    public function __construct(
        public readonly string $tokenId,
        public readonly int    $conflictReservationId,
        public readonly int    $ilanId,
        public readonly int    $tenantId,
        public readonly string $eventId,
        public readonly string $ydlAuthority,
        public readonly string $authorityContext,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly array  $recommendation,
        public readonly string $requestedAt,
        public readonly string $expiresAt,
        public readonly ?int  $requestedBy,
    ) {}

    public static function create(
        int    $conflictReservationId,
        int    $ilanId,
        int    $tenantId,
        string $eventId,
        string $ydlAuthority,
        string $authorityContext,
        string $startDate,
        string $endDate,
        array  $recommendation,
        string $requestedAt,
        string $expiresAt,
        ?int   $requestedBy = null,
    ): self {
        return new self(
            tokenId:           substr(hash('sha256', "override|{$eventId}|{$requestedAt}"), 0, 24),
            conflictReservationId: $conflictReservationId,
            ilanId:              $ilanId,
            tenantId:            $tenantId,
            eventId:           $eventId,
            ydlAuthority:      $ydlAuthority,
            authorityContext:    $authorityContext,
            startDate:         $startDate,
            endDate:           $endDate,
            recommendation:    $recommendation,
            requestedAt:       $requestedAt,
            expiresAt:         $expiresAt,
            requestedBy:       $requestedBy,
        );
    }

    public function isExpired(): bool
    {
        return now()->parse($this->expiresAt)->isPast();
    }

    public function validateOrFail(): void
    {
        if ($this->isExpired()) {
            throw new \DomainException('Override approval token expired.');
        }
    }

    public function toArray(): array
    {
        return [
            'token_id'           => $this->tokenId,
            'conflict_reservation_id' => $this->conflictReservationId,
            'ilan_id'              => $this->ilanId,
            'tenant_id'            => $this->tenantId,
            'event_id'           => $this->eventId,
            'ydl_authority'      => $this->ydlAuthority,
            'authority_context'    => $this->authorityContext,
            'start_date'         => $this->startDate,
            'end_date'           => $this->endDate,
            'recommendation'     => $this->recommendation,
            'requested_at'       => $this->requestedAt,
            'expires_at'         => $this->expiresAt,
            'requested_by'       => $this->requestedBy,
        ];
    }
}
