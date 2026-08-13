<?php

namespace App\DTOs\Ydl\Reservation;

/**
 * YdlCancellationApprovalToken — Human approval token for cancellation.
 *
 * PILOT-002 Wave 2
 *
 * @readonly
 */
final class YdlCancellationApprovalToken
{
    public const DEFAULT_TTL_SECONDS = 86400;

    public function __construct(
        public readonly string                $tokenId,
        public readonly int                   $reservationId,
        public readonly int                   $ilanId,
        public readonly int                   $tenantId,
        public readonly string               $eventId,
        public readonly string               $ydlAuthority,
        public readonly string               $authorityContext,
        public readonly string               $reservationState,
        public readonly array                $recommendation,
        public readonly string               $requestedAt,
        public readonly string               $expiresAt,
        public readonly ?int                $requestedBy,
    ) {}

    public static function create(
        int    $reservationId,
        int    $ilanId,
        int    $tenantId,
        string $eventId,
        string $ydlAuthority,
        string $authorityContext,
        string $reservationState,
        array  $recommendation,
        string $requestedAt,
        string $expiresAt,
        ?int   $requestedBy = null,
    ): self {
        return new self(
            tokenId:            substr(hash('sha256', "cancel|{$eventId}|{$requestedAt}"), 0, 24),
            reservationId:   $reservationId,
            ilanId:           $ilanId,
            tenantId:         $tenantId,
            eventId:          $eventId,
            ydlAuthority:    $ydlAuthority,
            authorityContext:  $authorityContext,
            reservationState:  $reservationState,
            recommendation:   $recommendation,
            requestedAt:      $requestedAt,
            expiresAt:        $expiresAt,
            requestedBy:      $requestedBy,
        );
    }

    public function isExpired(): bool
    {
        return now()->parse($this->expiresAt)->isPast();
    }

    public function validateOrFail(): void
    {
        if ($this->isExpired()) {
            throw new \DomainException("Cancellation approval token expired.");
        }
    }

    public function toArray(): array
    {
        return [
            'token_id'          => $this->tokenId,
            'reservation_id'  => $this->reservationId,
            'ilan_id'          => $this->ilanId,
            'tenant_id'        => $this->tenantId,
            'event_id'         => $this->eventId,
            'ydl_authority'    => $this->ydlAuthority,
            'authority_context' => $this->authorityContext,
            'reservation_state' => $this->reservationState,
            'recommendation'   => $this->recommendation,
            'requested_at'     => $this->requestedAt,
            'expires_at'       => $this->expiresAt,
            'requested_by'     => $this->requestedBy,
        ];
    }
}
