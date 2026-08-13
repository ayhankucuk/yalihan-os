<?php

namespace App\DTOs\Ydl\Reservation;

/**
 * YdlReservationApprovalToken — Human approval token for reservation operations.
 *
 * PILOT-002 Wave 1
 *
 * TTL-based token. Must be presented during executeReservation().
 * Expires after APPROVAL_TOKEN_TTL_SECONDS.
 *
 * @readonly
 */
final class YdlReservationApprovalToken
{
    public const DEFAULT_TTL_SECONDS = 86400; // 24 hours — matches PILOT-001

    public function __construct(
        public readonly string                $tokenId,
        public readonly int                   $ilanId,
        public readonly int                   $tenantId,
        public readonly string               $eventId,
        public readonly string               $ydlAuthority,
        public readonly string               $authorityContext,
        public readonly string               $startDate,
        public readonly string               $endDate,
        public readonly array                $recommendation,
        public readonly string               $requestedAt,
        public readonly string               $expiresAt,
        public readonly ?int                $requestedBy,
    ) {}

    public static function create(
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
            tokenId:            substr(hash('sha256', "{$eventId}|{$requestedAt}"), 0, 24),
            ilanId:            $ilanId,
            tenantId:          $tenantId,
            eventId:           $eventId,
            ydlAuthority:     $ydlAuthority,
            authorityContext:   $authorityContext,
            startDate:        $startDate,
            endDate:          $endDate,
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

    public function remainingSeconds(): int
    {
        $remaining = now()->parse($this->expiresAt)->diffInSeconds(now(), false);
        return max(0, -$remaining);
    }

    public function validateOrFail(): void
    {
        if ($this->isExpired()) {
            throw new \DomainException(
                "Approval token expired. Expired at: {$this->expiresAt}"
            );
        }
    }

    public function toArray(): array
    {
        return [
            'token_id'          => $this->tokenId,
            'ilan_id'          => $this->ilanId,
            'tenant_id'        => $this->tenantId,
            'event_id'         => $this->eventId,
            'ydl_authority'    => $this->ydlAuthority,
            'authority_context' => $this->authorityContext,
            'start_date'       => $this->startDate,
            'end_date'         => $this->endDate,
            'recommendation'   => $this->recommendation,
            'requested_at'     => $this->requestedAt,
            'expires_at'       => $this->expiresAt,
            'requested_by'     => $this->requestedBy,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            tokenId:            $data['token_id'],
            ilanId:            (int) $data['ilan_id'],
            tenantId:          (int) $data['tenant_id'],
            eventId:           $data['event_id'],
            ydlAuthority:     $data['ydl_authority'],
            authorityContext:   $data['authority_context'],
            startDate:        $data['start_date'],
            endDate:          $data['end_date'],
            recommendation:    $data['recommendation'],
            requestedAt:       $data['requested_at'],
            expiresAt:         $data['expires_at'],
            requestedBy:       isset($data['requested_by']) ? (int) $data['requested_by'] : null,
        );
    }
}
