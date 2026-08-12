<?php

namespace App\Infrastructure\ChannelManager\Booking;

use App\Infrastructure\ChannelManager\Booking\DTOs\BookingConnectionResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BookingConnectionProbeService — Non-destructive connectivity probe for Booking.com.
 *
 * Sprint 4.15 — G34 Connectivity Probe
 *
 * PROBE SEQUENCE (non-destructive):
 *   1. Resolve first active sync record for tenant
 *   2. Attempt token exchange (validates credentials)
 *   3. GET /reservations with narrow date window (read-only, no data written)
 *   4. Classify response → BookingConnectionResult
 *
 * INVARIANTS:
 *   - Never writes to Booking.com (no POST, PUT, PATCH, DELETE)
 *   - Never writes to YALIHAN database
 *   - Never logs credentials (tokens or secrets)
 *   - Idempotent — multiple probes produce same result
 */
class BookingConnectionProbeService
{
    public function __construct(
        private readonly BookingCredentialManagerInterface $credentialManager,
        private readonly BookingTransport                 $transport,
    ) {}

    /**
     * Probe Booking.com connectivity for a tenant.
     *
     * Uses the FIRST active sync record found for the tenant as the probe target.
     * If the tenant has no active sync records → NOT_REGISTERED.
     *
     * @param int $tenantId Tenant to probe
     *
     * @return BookingConnectionResult
     */
    public function probe(int $tenantId): BookingConnectionResult
    {
        $correlationId = 'conn-probe-' . uniqid();

        // Step 1: Find first active sync record for this tenant
        $syncRecord = $this->resolveSyncRecord($tenantId);

        if ($syncRecord === null) {
            return BookingConnectionResult::notRegistered($correlationId, $tenantId);
        }

        $ilanId = (int) $syncRecord['ilan_id'];

        // Step 2: Attempt token exchange (validates credentials are correct)
        try {
            $tokenData = $this->credentialManager->getValidToken($ilanId, 'booking_com');
        } catch (BookingAuthException $e) {
            Log::warning('BookingConnectionProbe: token exchange failed', [
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
            ]);
            return BookingConnectionResult::authFailed(
                $correlationId,
                'Token exchange failed: ' . $e->getMessage(),
            );
        }

        if (empty($tokenData['access_token'])) {
            return BookingConnectionResult::authFailed(
                $correlationId,
                'Token exchange returned empty access token',
            );
        }

        // Step 3: Read-only API probe — narrow date window (today ± 1 day)
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        $result = $this->transport->get(
            $ilanId,
            '/reservations',
            [
                'arrival_date' => $today,
                'departure_date' => $tomorrow,
            ],
        );

        // Step 4: Classify response
        return $this->classifyResponse($correlationId, $result, $tokenData);
    }

    /**
     * Probe with a specific property (ilanId).
     * Use when the caller knows the exact property to test.
     */
    public function probeProperty(int $tenantId, int $ilanId): BookingConnectionResult
    {
        $correlationId = 'conn-probe-prop-' . uniqid();

        // Step 1: Verify sync record exists and belongs to tenant
        $syncRecord = $this->resolveSyncRecordForProperty($tenantId, $ilanId);

        if ($syncRecord === null) {
            return BookingConnectionResult::notRegistered($correlationId, $tenantId);
        }

        // Step 2: Token exchange
        try {
            $tokenData = $this->credentialManager->getValidToken($ilanId, 'booking_com');
        } catch (BookingAuthException $e) {
            Log::warning('BookingConnectionProbe: token exchange failed (probeProperty)', [
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
            ]);
            return BookingConnectionResult::authFailed(
                $correlationId,
                'Token exchange failed: ' . $e->getMessage(),
            );
        }

        if (empty($tokenData['access_token'])) {
            return BookingConnectionResult::authFailed(
                $correlationId,
                'Token exchange returned empty access token',
            );
        }

        // Step 3: Read-only probe
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        $result = $this->transport->get(
            $ilanId,
            '/reservations',
            [
                'arrival_date' => $today,
                'departure_date' => $tomorrow,
            ],
        );

        return $this->classifyResponse($correlationId, $result, $tokenData);
    }

    // ─── Private ────────────────────────────────────────────────────────────

    /**
     * Find the first active booking_com sync record for a tenant.
     * Requires joining through ilanlar to verify tenant ownership.
     *
     * @return array|null {ilan_id, external_listing_id} or null
     */
    private function resolveSyncRecord(int $tenantId): ?array
    {
        $row = DB::table('ilan_takvim_sync')
            ->join('ilanlar', 'ilan_takvim_sync.ilan_id', '=', 'ilanlar.id')
            ->where('ilanlar.tenant_id', $tenantId)
            ->where('ilan_takvim_sync.platform', 'booking_com')
            ->where('ilan_takvim_sync.is_sync_active', true)
            ->where('ilan_takvim_sync.senkron_durumu', 'active')
            ->whereNotNull('ilan_takvim_sync.external_listing_id')
            ->where('ilan_takvim_sync.external_listing_id', '!=', '')
            ->orderBy('ilan_takvim_sync.id')
            ->first(['ilan_takvim_sync.ilan_id', 'ilan_takvim_sync.external_listing_id']);

        if ($row === null) {
            return null;
        }

        return [
            'ilan_id'              => $row->ilan_id,
            'external_listing_id'  => $row->external_listing_id,
        ];
    }

    /**
     * Find active booking_com sync for a specific property, verifying tenant ownership.
     */
    private function resolveSyncRecordForProperty(int $tenantId, int $ilanId): ?array
    {
        $row = DB::table('ilan_takvim_sync')
            ->join('ilanlar', 'ilan_takvim_sync.ilan_id', '=', 'ilanlar.id')
            ->where('ilan_takvim_sync.ilan_id', $ilanId)
            ->where('ilanlar.tenant_id', $tenantId)
            ->where('ilan_takvim_sync.platform', 'booking_com')
            ->where('ilan_takvim_sync.is_sync_active', true)
            ->where('ilan_takvim_sync.senkron_durumu', 'active')
            ->whereNotNull('ilan_takvim_sync.external_listing_id')
            ->where('ilan_takvim_sync.external_listing_id', '!=', '')
            ->first(['ilan_takvim_sync.ilan_id', 'ilan_takvim_sync.external_listing_id']);

        if ($row === null) {
            return null;
        }

        return [
            'ilan_id'              => $row->ilan_id,
            'external_listing_id'  => $row->external_listing_id,
        ];
    }

    /**
     * Classify transport result into BookingConnectionResult.
     */
    private function classifyResponse(
        string $correlationId,
        \App\DTOs\ChannelManager\ChannelTransportResult $result,
        array $tokenData,
    ): BookingConnectionResult {
        // Success is definitive — check first
        if ($result->success) {
            $metadata = array_merge($result->metadata ?? [], [
                'probed_at'   => now()->toIso8601String(),
                'expires_at'  => $tokenData['expires_at'] ?? null,
            ]);

            return BookingConnectionResult::connected($correlationId, $metadata);
        }

        // Failure path — classify error type
        $status = (int) ($result->errorCode !== '' ? $result->errorCode : 0);

        // Network / connection error (status = 0)
        if ($status === 0) {
            return BookingConnectionResult::connectionError(
                $correlationId,
                'Network error reaching Booking.com: ' . ($result->errorMessage ?? 'unknown'),
            );
        }

        // Auth failures (401, 403)
        if ($status === 401 || $status === 403) {
            return BookingConnectionResult::authFailed(
                $correlationId,
                'Authentication rejected by Booking.com (HTTP ' . $status . ')',
            );
        }

        // 5xx — Booking.com server error
        if ($status >= 500) {
            return BookingConnectionResult::providerError(
                $correlationId,
                'Booking.com server error (HTTP ' . $status . ')',
            );
        }

        // 4xx — client error
        return BookingConnectionResult::providerError(
            $correlationId,
            'Booking.com returned HTTP ' . $status . ': ' . ($result->errorMessage ?? 'unknown'),
        );
    }
}
