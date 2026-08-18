<?php

namespace App\Services\Ydl\Platform;

/**
 * ApprovalTokenPolicy — Platform-level approval token lifecycle.
 *
 * PILOT-001 + PILOT-002 common token lifecycle management.
 *
 * Extracted from domain orchestrators:
 *   - YdlPublishOrchestrator (PILOT-001)
 *   - YdlReservationOrchestrator (PILOT-002)
 *
 * Responsibilities:
 *   - TTL management (value provided by domain — platform owns the mechanism)
 *   - Token ID generation (deterministic, replay-resistant)
 *   - Expiry validation
 *   - Token struct factory
 *
 * This class does NOT contain business logic.
 * Business logic (what the token means, what operation it authorizes)
 * stays in domain orchestrators and domain token DTOs.
 *
 * TTL ownership:
 *   - Platform: mechanism (calculation, validation)
 *   - Domain: TTL value — passed at construction or per-call
 *
 * Domain-agnostic: no imports from App\Models\... or App\Services\...
 * except ApprovalToken / ApprovalTokenPolicyInterface themselves.
 * All parameters are primitive strings/arrays.
 *
 * Composition: injected into domain orchestrators, not inherited.
 * Domain orchestrators call ApprovalTokenPolicy for lifecycle decisions,
 * then apply their own business logic.
 */
class ApprovalTokenPolicy implements ApprovalTokenPolicyInterface
{
    /** Technical default TTL — domain should provide its own value. */
    public const DEFAULT_TTL_SECONDS = 86400;

    /**
     * @param int $ttlSeconds  Domain-defined TTL in seconds.
     *                          Use DEFAULT_TTL_SECONDS as fallback only.
     */
    public function __construct(
        private readonly int $ttlSeconds = self::DEFAULT_TTL_SECONDS,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function defaultTtlSeconds(): int
    {
        return $this->ttlSeconds;
    }

    /**
     * @inheritDoc
     */
    public function computeExpiresAt(string $requestedAt, ?int $ttlSeconds = null): string
    {
        $ttl = $ttlSeconds ?? $this->ttlSeconds;
        return (new \DateTimeImmutable($requestedAt))
            ->modify("+{$ttl} seconds")
            ->format(\DateTimeInterface::ATOM);
    }

    /**
     * @inheritDoc
     */
    public function generateTokenId(string $eventId, string $requestedAt): string
    {
        return substr(hash('sha256', "{$eventId}|{$requestedAt}"), 0, 24);
    }

    /**
     * @inheritDoc
     */
    public function buildToken(array $params): ApprovalToken
    {
        $requestedAt = $params['requestedAt'] ?? now()->toIso8601String();
        $ttl = $params['ttlSeconds'] ?? $this->ttlSeconds;
        $eventId = $params['eventId'] ?? '';
        $tokenId = $this->generateTokenId($eventId, $requestedAt);
        $expiresAt = $this->computeExpiresAt($requestedAt, $ttl);

        return new ApprovalToken(
            tokenId:           $tokenId,
            eventId:           $eventId,
            subjectId:         (int) ($params['subjectId'] ?? 0),
            tenantId:          (int) ($params['tenantId'] ?? 0),
            authority:         $params['authority'] ?? '',
            authorityContext:   $params['authorityContext'] ?? '',
            expiresAt:         $expiresAt,
            requestedAt:       $requestedAt,
            recommendation:    $params['recommendation'] ?? [],
            subjectType:       $params['subjectType'] ?? null,
            requestedBy:       isset($params['requestedBy']) ? (int) $params['requestedBy'] : null,
            extra:             $params['extra'] ?? [],
        );
    }

    /**
     * @inheritDoc
     */
    public function isExpired(ApprovalToken $token): bool
    {
        return (new \DateTimeImmutable($token->expiresAt)) < new \DateTimeImmutable();
    }

    /**
     * @inheritDoc
     */
    public function validateOrFail(ApprovalToken $token): void
    {
        if ($this->isExpired($token)) {
            throw new \DomainException(
                "Approval token expired at {$token->expiresAt}. " .
                "Request a new approval token."
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function remainingSeconds(ApprovalToken $token): int
    {
        $diff = (new \DateTimeImmutable($token->expiresAt))
            ->diffInSeconds(new \DateTimeImmutable(), false);
        return max(0, -$diff);
    }
}
