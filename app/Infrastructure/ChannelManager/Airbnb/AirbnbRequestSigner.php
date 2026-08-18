<?php

namespace App\Infrastructure\ChannelManager\Airbnb;

/**
 * AirbnbRequestSigner — HMAC-SHA256 request signer
 *
 * Sprint 13 E03: Airbnb Adapter
 *
 * Signs outbound requests to Airbnb API using HMAC-SHA256.
 * The signature ensures request integrity and authenticity.
 *
 * NOTE: This signer implements the Airbnb Partner API v2 signature scheme.
 * Actual implementation depends on Airbnb API documentation.
 */
class AirbnbRequestSigner
{
    private string $clientId;
    private string $clientSecret;

    public function __construct(string $clientId, string $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    /**
     * Sign a request payload
     *
     * @param array $payload The JSON payload being sent
     * @param string|null $timestamp Unix timestamp (defaults to now)
     * @return string Base64-encoded HMAC-SHA256 signature
     */
    public function sign(array $payload, ?string $timestamp = null): string
    {
        $timestamp = $timestamp ?? (string) time();

        // Build signature string: timestamp + method + path + JSON payload
        $signatureBase = implode("\n", [
            $timestamp,
            'POST',
            '/v2/calendar_entries',
            json_encode($payload, JSON_THROW_ON_ERROR),
        ]);

        return base64_encode(
            hash_hmac('sha256', $signatureBase, $this->clientSecret, true)
        );
    }

    /**
     * Verify a signature (for testing/debugging)
     *
     * @param string $signature The signature to verify
     * @param array $payload Original payload
     * @param string $timestamp Timestamp used in signing
     * @return bool
     */
    public function verify(string $signature, array $payload, string $timestamp): bool
    {
        $expected = $this->sign($payload, $timestamp);
        return hash_equals($expected, $signature);
    }

    /**
     * Get the client ID (safe to log)
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }
}
