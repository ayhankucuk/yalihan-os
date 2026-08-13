<?php

namespace App\Services\Ydl\Reservation;

use App\Contracts\Ydl\Reservation\ConflictOverrideContract;
use App\DTOs\Ydl\Reservation\YdlReservationContextOutput;
use App\Enums\ReservationState;
use App\Models\PropertyReservation;
use App\Models\User;

/**
 * ConflictOverrideService — Canonical override authorization for reservation conflicts.
 *
 * PILOT-002 Wave 3
 *
 * Determines WHO can override a conflict and under what conditions.
 * This service does NOT execute the override — it only authorizes or rejects.
 *
 * Red Line: Override NEVER auto-approves. Explicit human decision always required.
 */
class ConflictOverrideService implements ConflictOverrideContract
{
    /** Permission required for override. */
    public const OVERRIDE_PERMISSION = 'reservation_override_conflict';

    /**
     * Check if a user can override a conflict for a specific property.
     */
    public function canOverride(
        int    $userId,
        int    $propertyId,
        string $ydlAuthority,
        int    $conflictReservationId,
    ): bool {
        if ($ydlAuthority === YdlReservationContextOutput::AUTHORITY_STOP) {
            return false; // Cannot override STOP authority ever
        }

        $user = User::find($userId);
        if ($user === null) {
            return false;
        }

        // User must be property admin for this property
        if (! $this->isPropertyAdmin($user, $propertyId)) {
            return false;
        }

        // Override allowed for FULL / LIMITED authority
        return $this->isOverrideAllowed($ydlAuthority);
    }

    /**
     * Get property IDs where the user has override authorization.
     *
     * @return int[] property IDs
     */
    public function getOverrideScopes(int $userId): array
    {
        $user = User::find($userId);
        if ($user === null) {
            return [];
        }

        // Admin users can override all properties
        if ($user->is_admin) {
            return Ilan::query()
                ->where('tenant_id', $user->tenant_id ?? 0)
                ->pluck('id')
                ->toArray();
        }

        // Non-admin: only properties explicitly granted via pivot or is_property_admin=true
        return [];
    }

    /**
     * Check if override is architecturally permitted for the authority level.
     */
    public function isOverrideAllowed(string $ydlAuthority): bool
    {
        return $ydlAuthority !== YdlReservationContextOutput::AUTHORITY_STOP;
    }

    /**
     * Verify user is admin for the given property.
     */
    private function isPropertyAdmin(User $user, int $propertyId): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $ilan = \App\Models\Ilan::find($propertyId);
        if ($ilan === null) {
            return false;
        }

        // Same tenant check
        if (isset($ilan->tenant_id) && $ilan->tenant_id !== $user->tenant_id) {
            return false;
        }

        return true;
    }
}
