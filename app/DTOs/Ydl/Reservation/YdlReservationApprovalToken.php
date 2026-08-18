<?php

namespace App\DTOs\Ydl\Reservation;

use App\Services\Ydl\Platform\ApprovalToken;
use App\Services\Ydl\Platform\ApprovalTokenPolicy;
use App\Services\Ydl\Platform\ApprovalTokenPolicyInterface;

/**
 * YdlReservationApprovalToken — Human approval token for reservation operations.
 *
 * PILOT-002 Wave 1
 *
 * TTL-based token. Must be presented during executeReservation().
 * Expires after ApprovalTokenPolicy::DEFAULT_TTL_SECONDS.
 *
 * Token lifecycle delegated to ApprovalTokenPolicy (platform-level).
 * Domain-specific fields (ilanId, startDate, endDate) stay here.
 *
 * @readonly
 */
final class YdlReservationApprovalToken
{
    public const DEFAULT_TTL_SECONDS = 86400; // kept for BW compat

    private static ?ApprovalTokenPolicyInterface $tokenPolicy = null;

    public function __construct(
        public readonly string $tokenId,
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

    public static function setTokenPolicy(ApprovalTokenPolicyInterface $policy): void
    {
        self::$tokenPolicy = $policy;
    }

    public static function resetTokenPolicy(): void
    {
        self::$tokenPolicy = null;
    }

    private static function policy(): ApprovalTokenPolicyInterface
    {
        return self::$tokenPolicy ?? new ApprovalTokenPolicy();
    }

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
        $tokenId = self::policy()->generateTokenId($eventId, $requestedAt);

        return new self(
            tokenId:            $tokenId,
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
        return self::policy()->isExpired($this->toPlatformToken());
    }

    public function remainingSeconds(): int
    {
        return self::policy()->remainingSeconds($this->toPlatformToken());
    }

    public function validateOrFail(): void
    {
        self::policy()->validateOrFail($this->toPlatformToken());
    }

    /**
     * Convert to platform-level ApprovalToken for lifecycle operations.
     */
    public function toPlatformToken(): ApprovalToken
    {
        return new ApprovalToken(
            tokenId:           $this->tokenId,
            eventId:           $this->eventId,
            subjectId:         $this->ilanId,
            tenantId:          $this->tenantId,
            authority:         $this->ydlAuthority,
            authorityContext:   $this->authorityContext,
            expiresAt:         $this->expiresAt,
            requestedAt:       $this->requestedAt,
            recommendation:    $this->recommendation,
            subjectType:       'reservation',
            requestedBy:       $this->requestedBy,
            extra: [
                'startDate' => $this->startDate,
                'endDate'   => $this->endDate,
            ],
        );
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
            'requested_at'    => $this->requestedAt,
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
