<?php

namespace App\Services\Ydl\Platform;

/**
 * ApprovalToken — Platform-level immutable approval token.
 *
 * Generic token struct used by all YDL approval flows:
 *   - PILOT-001: Property publish
 *   - PILOT-002: Reservation create / cancel / override
 *
 * Domain-specific fields (ilanId, reservationId, dates, etc.)
 * live in the domain token DTOs that wrap this token.
 *
 * This value object carries only platform-level primitives.
 *
 * @readonly
 */
final class ApprovalToken
{
    private ?\DateTimeImmutable $validatedAt = null;

    public function __construct(
        public readonly string $tokenId,
        public readonly string $eventId,
        public readonly int $subjectId,
        public readonly int $tenantId,
        public readonly string $authority,
        public readonly string $authorityContext,
        public readonly string $expiresAt,
        public readonly string $requestedAt,
        public readonly array $recommendation,
        public readonly ?string $subjectType = null,
        public readonly ?int $requestedBy = null,
        public readonly array $extra = [],
    ) {}

    /**
     * Create from an array (e.g. from buildToken output or deserialization).
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tokenId:            $data['tokenId'] ?? $data['token_id'] ?? '',
            eventId:            $data['eventId'] ?? $data['event_id'] ?? '',
            subjectId:          (int) ($data['subjectId'] ?? $data['subject_id'] ?? 0),
            tenantId:           (int) ($data['tenantId'] ?? $data['tenant_id'] ?? 0),
            authority:          $data['authority'] ?? $data['ydlAuthority'] ?? '',
            authorityContext:    $data['authorityContext'] ?? $data['authority_context'] ?? '',
            expiresAt:          $data['expiresAt'] ?? $data['expires_at'] ?? '',
            requestedAt:        $data['requestedAt'] ?? $data['requested_at'] ?? '',
            recommendation:     $data['recommendation'] ?? [],
            subjectType:        $data['subjectType'] ?? $data['subject_type'] ?? null,
            requestedBy:        isset($data['requestedBy']) ? (int) $data['requestedBy']
                                        : (isset($data['requested_by']) ? (int) $data['requested_by'] : null),
            extra:              $data['extra'] ?? [],
        );
    }

    /**
     * Convert to plain array (for serialization).
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'tokenId'            => $this->tokenId,
            'eventId'            => $this->eventId,
            'subjectId'          => $this->subjectId,
            'tenantId'           => $this->tenantId,
            'authority'          => $this->authority,
            'authorityContext'    => $this->authorityContext,
            'expiresAt'          => $this->expiresAt,
            'requestedAt'        => $this->requestedAt,
            'recommendation'     => $this->recommendation,
            'subjectType'        => $this->subjectType,
            'requestedBy'        => $this->requestedBy,
            'extra'              => $this->extra,
        ];
    }

    /**
     * Mark token as validated (single-use replay protection).
     */
    public function markValidated(): void
    {
        $this->validatedAt = new \DateTimeImmutable();
    }

    public function isValidated(): bool
    {
        return $this->validatedAt !== null;
    }
}
