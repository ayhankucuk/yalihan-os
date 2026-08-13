<?php

namespace App\Contracts\Ydl\Reservation;

/**
 * ConflictOverrideContract — Override authorization for reservation conflicts.
 *
 * PILOT-002 Wave 3
 *
 * Canonical override authorization: determines WHO can override a reservation conflict
 * and under what conditions. This contract does NOT execute the override —
 * it only authorizes or rejects the decision.
 */
interface ConflictOverrideContract
{
    /**
     * Check if a user can override a conflict for a specific property.
     *
     * @param int $userId
     * @param int $propertyId
     * @param string $ydlAuthority FULL / LIMITED / STOP
     * @param int $conflictReservationId
     * @return bool true = authorized, false = not authorized
     */
    public function canOverride(
        int    $userId,
        int    $propertyId,
        string $ydlAuthority,
        int    $conflictReservationId,
    ): bool;

    /**
     * Get the override scopes for a user (which properties they can authorize override for).
     *
     * @return int[] property IDs the user can authorize overrides for
     */
    public function getOverrideScopes(int $userId): array;

    /**
     * Check if override is allowed for the given authority level.
     *
     * @param string $ydlAuthority FULL / LIMITED / STOP
     * @return bool true = override is architecturally permitted for this authority
     */
    public function isOverrideAllowed(string $ydlAuthority): bool;
}
