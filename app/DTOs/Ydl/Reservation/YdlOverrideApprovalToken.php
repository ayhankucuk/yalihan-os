<?php

namespace App\DTOs\Ydl\Reservation;

use App\Services\Ydl\Platform\ApprovalToken;
use App\Services\Ydl\Platform\ApprovalTokenPolicy;
use App\Services\Ydl\Platform\ApprovalTokenPolicyInterface;

/**
 * YdlOverrideApprovalToken — Human approval token for conflict override.
 *
 * PILOT-002 Wave 3
 *
 * Token lifecycle delegated to ApprovalTokenPolicy (platform-level).
 * Domain-specific fields (conflictReservationId, startDate, endDate) stay here.
 *
 * @readonly
 */
final class YdlOverrideApprovalToken
{
    private static ?ApprovalTokenPolicyInterface $tokenPolicy = null;

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
        $tokenId = self::policy()->generateTokenId($eventId, $requestedAt);

        return new self(
            tokenId:               $tokenId,
            conflictReservationId: $conflictReservationId,
            ilanId:                $ilanId,
            tenantId:              $tenantId,
            eventId:               $eventId,
            ydlAuthority:          $ydlAuthority,
            authorityContext:      $authorityContext,
            startDate:             $startDate,
            endDate:               $endDate,
            recommendation:        $recommendation,
            requestedAt:           $requestedAt,
            expiresAt:             $expiresAt,
            requestedBy:           $requestedBy,
        );
    }

    public function isExpired(): bool
    {
        return self::policy()->isExpired($this->toPlatformToken());
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
            subjectId:         $this->conflictReservationId,
            tenantId:          $this->tenantId,
            authority:         $this->ydlAuthority,
            authorityContext:   $this->authorityContext,
            expiresAt:         $this->expiresAt,
            requestedAt:       $this->requestedAt,
            recommendation:    $this->recommendation,
            subjectType:       'override',
            requestedBy:       $this->requestedBy,
            extra: [
                'ilanId'               => $this->ilanId,
                'conflictReservationId' => $this->conflictReservationId,
                'startDate'            => $this->startDate,
                'endDate'              => $this->endDate,
            ],
        );
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
