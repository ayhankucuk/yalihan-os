<?php

namespace App\Infrastructure\ChannelManager\Booking;

/**
 * BookingCredentialManagerInterface — Contract for token lifecycle.
 *
 * Sprint 4.10 — Booking.com Provider Wave 1
 *
 * Used for testability — BookingTransport depends on this interface.
 */
interface BookingCredentialManagerInterface
{
    /**
     * Get a valid (non-expired) access token for the given sync config.
     * Exchanges new token if expired or missing.
     *
     * @return array{access_token: string, expires_at: string}
     */
    public function getValidToken(int $ilanId, string $platform = 'booking'): array;

    /**
     * Force refresh: discard stored token and re-exchange.
     * Use after receiving 401 from the API.
     */
    public function forceRefresh(int $ilanId, string $platform = 'booking'): array;

    /**
     * Check if a stored token config is expired.
     */
    public function isExpired(array $config): bool;
}
