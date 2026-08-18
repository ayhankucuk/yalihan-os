<?php

namespace App\Services\Reservation;

use App\Models\AccessCredential;
use App\Models\Ilan;
use App\Models\PropertyReservation;
use App\Traits\GuardsAgentWrites;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * AccessCredentialService — Wave 2 access credential lifecycle management.
 *
 * CHECKIN_CHECKOUT Wave 2
 *
 * Responsibilities:
 * - Store/retrieve encrypted access credentials
 * - Enforce credential safety invariants (INV-W2-S1, INV-W2-S2, INV-W2-S3)
 * - Handle credential expiration (INV-W2-S3)
 *
 * Uses GuardsAgentWrites: YES
 * Safety: credential_value NEVER appears in logs — masked at ALL call sites
 * Tenant isolation: All queries scoped by tenant_id
 */
class AccessCredentialService
{
    use GuardsAgentWrites;

    /**
     * Credential type constants.
     */
    public const TYPE_KEY = 'key';
    public const TYPE_CODE = 'code';
    public const TYPE_LOCKBOX = 'lockbox';
    public const TYPE_SMART_LOCK = 'smart_lock';

    public const VALID_TYPES = [
        self::TYPE_KEY,
        self::TYPE_CODE,
        self::TYPE_LOCKBOX,
        self::TYPE_SMART_LOCK,
    ];

    // ─── Public API ────────────────────────────────────────────────────────

    /**
     * Get the active credential for a property.
     * Returns null if none exists or if the only credential is expired.
     *
     * @throws \RuntimeException if tenant mismatch detected
     */
    public function getActiveCredential(Ilan $ilan): ?AccessCredential
    {
        $this->blockAgentWrite(__FUNCTION__);

        // Tenant isolation: verify ilan tenant
        $this->enforceTenantMatch($ilan->tenant_id, 'getActiveCredential');

        $credential = AccessCredential::query()
            ->where('ilan_id', $ilan->id)
            ->where('is_active', true)
            ->orderBy('id', 'desc')
            ->first();

        if ($credential === null) {
            return null;
        }

        // Filter out expired credentials at the service layer (belt-and-suspenders)
        if ($credential->isExpired()) {
            Log::debug('AccessCredentialService: active credential is expired', [
                'credential_id' => $credential->id,
                'ilan_id' => $ilan->id,
                'masked_value' => $credential->getMaskedValue(),
            ]);
            return null;
        }

        Log::debug('AccessCredentialService: active credential found', [
            'credential_id' => $credential->id,
            'ilan_id' => $ilan->id,
            'masked_value' => $credential->getMaskedValue(),
            'type' => $credential->credential_type,
            'expires_at' => $credential->expires_at?->toIso8601String(),
        ]);

        return $credential;
    }

    /**
     * Issue a new credential for a reservation.
     * Sets expires_at = reservation end_date + 24 hours.
     *
     * Credential value is stored encrypted.
     *
     * @throws \RuntimeException if tenant mismatch detected
     * @throws \InvalidArgumentException if credential_type is invalid
     */
    public function issueCredential(
        PropertyReservation $reservation,
        Ilan $ilan,
        string $plainValue,
        string $credentialType,
        ?string $plainLocation = null,
    ): AccessCredential {
        $this->blockAgentWrite(__FUNCTION__);

        // Validate type
        if (!in_array($credentialType, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException(
                "Invalid credential_type: {$credentialType}. Valid: " . implode(', ', self::VALID_TYPES)
            );
        }

        // Tenant isolation: reservation and ilan must match
        $this->enforceReservationIlanTenantMatch($reservation, $ilan, 'issueCredential');

        // Compute expiration: end_date + 24 hours
        $endDate = Carbon::parse($reservation->end_date);
        $expiresAt = $endDate->copy()->addDay()->startOfDay();

        // Build the model — credential_value is set via setCredentialValue() before save
        // because the DB column is NOT NULL and the value must be encrypted first.
        /** @var AccessCredential $credential */
        $credential = new AccessCredential([
            'tenant_id' => $reservation->tenant_id,
            'ilan_id' => $ilan->id,
            'credential_type' => $credentialType,
            'is_active' => true,
            'requires_reset' => false,
            'expires_at' => $expiresAt,
        ]);

        // Encrypt and set sensitive values before the first save
        $credential->setCredentialValue($plainValue);
        if ($plainLocation !== null) {
            $credential->setCredentialLocation($plainLocation);
        }
        $credential->save();

        // INV-W2-S1: Log with masked value only
        Log::info('AccessCredentialService: credential issued', [
            'credential_id' => $credential->id,
            'reservation_id' => $reservation->id,
            'ilan_id' => $ilan->id,
            'tenant_id' => $reservation->tenant_id,
            'masked_value' => $credential->getMaskedValue(),
            'type' => $credentialType,
            'expires_at' => $expiresAt->toDateString(),
        ]);

        return $credential;
    }

    /**
     * Mark a credential as requiring reset (post-checkout).
     *
     * @throws \RuntimeException if tenant mismatch detected
     */
    public function markRequiresReset(AccessCredential $credential): void
    {
        $this->blockAgentWrite(__FUNCTION__);

        $this->enforceTenantMatch($credential->tenant_id, 'markRequiresReset');

        $wasActive = $credential->is_active;
        $credential->requires_reset = true;
        $credential->is_active = false;
        $credential->last_reset_at = Carbon::now();
        $credential->save();

        Log::info('AccessCredentialService: credential marked for reset', [
            'credential_id' => $credential->id,
            'ilan_id' => $credential->ilan_id,
            'masked_value' => $credential->getMaskedValue(),
            'was_active' => $wasActive,
        ]);
    }

    /**
     * Cleanup expired credentials — mark as inactive.
     * Returns the count of credentials cleaned up.
     *
     * This is called by ResetAccessCredentialJob daily.
     */
    public function cleanupExpiredCredentials(): int
    {
        $this->blockAgentWrite(__FUNCTION__);

        $count = AccessCredential::query()
            ->where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->update([
                'is_active' => false,
                'requires_reset' => true,
            ]);

        if ($count > 0) {
            Log::info('AccessCredentialService: expired credentials cleaned up', [
                'count' => $count,
            ]);
        }

        return $count;
    }

    /**
     * Deactivate all credentials for an ilan (used when property goes offline).
     *
     * @throws \RuntimeException if tenant mismatch detected
     */
    public function deactivateAllForIlan(Ilan $ilan): int
    {
        $this->blockAgentWrite(__FUNCTION__);

        $this->enforceTenantMatch($ilan->tenant_id, 'deactivateAllForIlan');

        $count = AccessCredential::query()
            ->where('ilan_id', $ilan->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        if ($count > 0) {
            Log::info('AccessCredentialService: all credentials deactivated for ilan', [
                'ilan_id' => $ilan->id,
                'count' => $count,
                // masked_value NOT logged — would be too many entries
            ]);
        }

        return $count;
    }

    // ─── Private helpers ────────────────────────────────────────────────────

    /**
     * Enforce tenant match — throws RuntimeException on mismatch.
     *
     * @throws \RuntimeException
     */
    private function enforceTenantMatch(int $expectedTenantId, string $context): void
    {
        $tenantService = app(\App\Services\SaaS\TenantContextService::class);
        if (!$tenantService->hasTenant()) {
            // No tenant context — skip enforcement (e.g. seeders, console commands)
            return;
        }

        $currentTenantId = $tenantService->getTenant()->id;
        if ($currentTenantId !== $expectedTenantId) {
            Log::error('AccessCredentialService: tenant mismatch blocked', [
                'context' => $context,
                'expected_tenant_id' => $expectedTenantId,
                'current_tenant_id' => $currentTenantId,
                // NO credential data logged
            ]);
            throw new \RuntimeException(
                "Tenant isolation violation in AccessCredentialService::{$context}: " .
                "expected {$expectedTenantId}, got {$currentTenantId}"
            );
        }
    }

    /**
     * Enforce reservation.tenant_id == ilan.tenant_id.
     *
     * @throws \RuntimeException
     */
    private function enforceReservationIlanTenantMatch(
        PropertyReservation $reservation,
        Ilan $ilan,
        string $context
    ): void {
        if ($reservation->tenant_id !== $ilan->tenant_id) {
            Log::error('AccessCredentialService: reservation/ilan tenant mismatch blocked', [
                'context' => $context,
                'reservation_id' => $reservation->id,
                'reservation_tenant_id' => $reservation->tenant_id,
                'ilan_id' => $ilan->id,
                'ilan_tenant_id' => $ilan->tenant_id,
                // NO credential data logged
            ]);
            throw new \RuntimeException(
                "Tenant isolation violation in AccessCredentialService::{$context}: " .
                "reservation tenant {$reservation->tenant_id} != ilan tenant {$ilan->tenant_id}"
            );
        }
    }
}
