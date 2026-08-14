<?php

namespace App\Services\Ydl\Platform;

/**
 * ApprovalTokenPolicyInterface — Platform-level approval token lifecycle.
 *
 * PILOT-001 + PILOT-002 common token lifecycle management.
 *
 * Extracted from domain orchestrators:
 *   - YdlPublishOrchestrator (PILOT-001)
 *   - YdlReservationOrchestrator (PILOT-002)
 *
 * Responsibilities:
 *   - TTL management (default: 24 hours)
 *   - Token ID generation
 *   - Expiry validation
 *   - Replay protection primitives
 *
 * This interface does NOT contain business logic.
 * Business logic (what the token means, what operation it authorizes)
 * stays in domain orchestrators.
 *
 * Domain-agnostic: all parameters are primitive strings/arrays.
 * No App\Models\... or App\Services\... imports.
 */
interface ApprovalTokenPolicyInterface
{
    /**
     * Default TTL in seconds — 24 hours, shared across all pilots.
     */
    public function defaultTtlSeconds(): int;

    /**
     * Compute expiry timestamp from request time + TTL.
     *
     * @param string $requestedAt ISO-8601 timestamp
     * @param int|null $ttlSeconds Override TTL (null = default)
     * @return string ISO-8601 expiry timestamp
     */
    public function computeExpiresAt(string $requestedAt, ?int $ttlSeconds = null): string;

    /**
     * Generate a deterministic token ID from event ID + request timestamp.
     *
     * @param string $eventId  Unique event identifier
     * @param string $requestedAt ISO-8601 timestamp
     * @return string 24-character hex token ID
     */
    public function generateTokenId(string $eventId, string $requestedAt): string;

    /**
     * Build an immutable approval token from raw parameters.
     *
     * @param array $params Token fields:
     *   - eventId:          string
     *   - subjectId:        int   (ilanId, reservationId, etc.)
     *   - tenantId:         int
     *   - authority:        string (STOP|LIMITED|FULL)
     *   - authorityContext: string (decision label for evidence)
     *   - recommendation:   array (opaque domain payload)
     *   - requestedAt:      string ISO-8601
     *   - ttlSeconds:       int|null (null = default)
     *   - subjectType:      string|null (publish|reservation|cancellation|override)
     *   - extra:            array|null (subject-specific fields)
     * @return ApprovalToken
     */
    public function buildToken(array $params): ApprovalToken;

    /**
     * Check if a token is expired.
     *
     * @param ApprovalToken $token
     * @return bool true if expired
     */
    public function isExpired(ApprovalToken $token): bool;

    /**
     * Validate token or throw if expired.
     *
     * @param ApprovalToken $token
     * @throws \DomainException if token is expired
     */
    public function validateOrFail(ApprovalToken $token): void;

    /**
     * Seconds remaining before token expires.
     *
     * @param ApprovalToken $token
     * @return int >= 0
     */
    public function remainingSeconds(ApprovalToken $token): int;
}
