<?php

namespace App\Infrastructure\ChannelManager\Booking;

use App\DTOs\ChannelManager\Booking\BookingAuthResult;
use App\Models\IlanTakvimSync;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BookingCredentialManager — Token lifecycle management.
 *
 * Sprint 4.10 — Booking.com Provider Wave 1
 *
 * Responsibilities:
 * - Store/retrieve tokens from IlanTakvimSync
 * - Determine if token is expired
 * - Trigger token exchange when needed
 * - Mask credentials in all log output
 *
 * TWO-LEGGED FLOW:
 *   Client ID + Client Secret ──▶ BookingAuthTransport.exchangeToken()
 *                                           │
 *                                           ▼
 *                                   BookingAuthResult (token + expiry)
 *                                           │
 *                                           ▼
 *                                   This manager stores in IlanTakvimSync
 */
class BookingCredentialManager implements BookingCredentialManagerInterface
{
    public function __construct(
        private readonly BookingAuthTransport $authTransport,
    ) {}

    /**
     * Get a valid (non-expired) access token for the given sync config.
     * If the stored token is expired or missing, exchanges new credentials.
     *
     * Uses DB::table to avoid Eloquent global scope issues.
     */
    public function getValidToken(int $ilanId, string $platform = 'booking_com'): array
    {
        $config = $this->loadSyncConfig($ilanId, $platform);

        // Check if we have a valid non-expired token
        if ($config !== null && !$this->isExpired($config)) {
            Log::debug('BookingCredentialManager: using cached token', [
                'ilan_id' => $ilanId,
                'platform' => $platform,
            ]);
            return [
                'access_token' => $config['token_access'],
                'expires_at'  => $config['token_expires_at'],
            ];
        }

        // Token missing or expired — exchange new credentials
        return $this->exchangeAndStore($ilanId, $platform, $config);
    }

    /**
     * Force refresh: discard stored token and re-exchange.
     * Use when 401 is received from the API.
     */
    public function forceRefresh(int $ilanId, string $platform = 'booking_com'): array
    {
        Log::info('BookingCredentialManager: force refresh triggered', [
            'ilan_id' => $ilanId,
            'platform' => $platform,
        ]);
        $config = $this->loadSyncConfig($ilanId, $platform);
        return $this->exchangeAndStore($ilanId, $platform, $config);
    }

    /**
     * Check if a stored token is expired.
     * A token is considered expired if token_expires_at is null or in the past.
     */
    public function isExpired(array $config): bool
    {
        if (empty($config['token_access']) || empty($config['token_expires_at'])) {
            return true;
        }
        return BookingAuthResult::isExpired(
            new \DateTimeImmutable($config['token_expires_at'])
        );
    }

    // ─── Private ────────────────────────────────────────────────────

    private function loadSyncConfig(int $ilanId, string $platform): ?array
    {
        $row = DB::table('ilan_takvim_sync')
            ->where('ilan_id', $ilanId)
            ->where('platform', $platform)
            ->where('is_sync_active', true)
            ->first(['token_access', 'token_refresh', 'token_expires_at']);

        if ($row === null) {
            return null;
        }

        return [
            'token_access'  => $row->token_access,
            'token_refresh' => $row->token_refresh,
            'token_expires_at' => $row->token_expires_at,
        ];
    }

    private function exchangeAndStore(int $ilanId, string $platform, ?array $existingConfig): array
    {
        // Retrieve credentials for token exchange
        // Client ID + Client Secret come from IlanTakvimSync credentials column
        // (platform-specific; Booking.com uses api_key/api_secret for the exchange)
        $credentials = $this->resolveCredentials($ilanId, $platform, $existingConfig);

        Log::info('BookingCredentialManager: exchanging token', [
            'ilan_id' => $ilanId,
            'platform' => $platform,
        ]);

        $result = $this->authTransport->exchangeToken(
            $credentials['client_id'],
            $credentials['client_secret'],
        );

        // Store in IlanTakvimSync
        $expiresAt = $result->expiresAt()->format('Y-m-d H:i:s');
        DB::table('ilan_takvim_sync')
            ->where('ilan_id', $ilanId)
            ->where('platform', $platform)
            ->update([
                'token_access'     => $result->accessToken,
                'token_refresh'    => $result->refreshToken,
                'token_expires_at' => $expiresAt,
            ]);

        Log::info('BookingCredentialManager: token stored', [
            'ilan_id'    => $ilanId,
            'platform'   => $platform,
            'expires_at' => $expiresAt,
        ]);

        return [
            'access_token' => $result->accessToken,
            'expires_at'  => $expiresAt,
        ];
    }

    /**
     * Resolve client_id and client_secret for token exchange.
     *
     * Wave 1: api_key / api_secret columns are used for machine-account credentials.
     * These will be migrated to dedicated token columns when Booking.com completes
     * their auth migration. At that point this method reads from the new columns.
     *
     * BW1-02: client_secret is NEVER logged — only masked in this class.
     */
    private function resolveCredentials(int $ilanId, string $platform, ?array $existingConfig): array
    {
        $row = DB::table('ilan_takvim_sync')
            ->where('ilan_id', $ilanId)
            ->where('platform', $platform)
            ->where('is_sync_active', true)
            ->first(['api_key', 'api_secret']);

        if ($row === null) {
            throw new BookingAuthException("No active sync config for ilan_id={$ilanId} platform={$platform}");
        }

        if (empty($row->api_key) || empty($row->api_secret)) {
            throw new BookingAuthException("Missing credentials for ilan_id={$ilanId} platform={$platform}");
        }

        return [
            'client_id'     => $row->api_key,
            'client_secret' => $row->api_secret,
        ];
    }
}
