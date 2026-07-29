<?php

namespace App\Infrastructure\ChannelManager\Airbnb;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Infrastructure\ChannelManager\Airbnb\Exceptions\AirbnbAuthenticationException;
use App\Infrastructure\ChannelManager\Airbnb\Exceptions\AirbnbRateLimitException;
use App\Infrastructure\ChannelManager\Airbnb\Exceptions\AirbnbRejectedRequestException;
use App\Infrastructure\ChannelManager\Airbnb\Exceptions\AirbnbTransportException;
use App\Infrastructure\ChannelManager\Airbnb\DTOs\AirbnbAvailabilityRequest;
use App\Infrastructure\ChannelManager\Airbnb\DTOs\AirbnbAvailabilityResponse;

/**
 * AirbnbClient — HTTP transport for Airbnb API
 *
 * Sprint 13 E03: Airbnb Adapter
 *
 * Responsibilities:
 * - Sign and send requests to Airbnb API
 * - Handle transport errors, timeouts, retries
 * - Parse responses and map to AirbnbAvailabilityResponse
 * - NEVER log credentials or secrets
 *
 * This client is NOT connected to the real Airbnb production API.
 * Production connectivity status: BLOCKED (no API credentials).
 *
 * Architecture: This client targets Airbnb's Partner API v2 endpoint:
 * POST /v2/calendar_entries
 */
class AirbnbClient
{
    private const BASE_URL = 'https://api.airbnb.com/v2';
    private const TIMEOUT_SECONDS = 15;

    private ?string $accessToken;
    private string $clientId;
    private string $clientSecret;

    public function __construct(
        string $clientId,
        string $clientSecret,
        ?string $accessToken = null,
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->accessToken = $accessToken;
    }

    /**
     * Update availability on Airbnb
     *
     * @param AirbnbAvailabilityRequest $request
     * @param int $tenantId
     * @return AirbnbAvailabilityResponse
     * @throws AirbnbAuthenticationException
     * @throws AirbnbRateLimitException
     * @throws AirbnbRejectedRequestException
     * @throws AirbnbTransportException
     */
    public function updateAvailability(
        AirbnbAvailabilityRequest $request,
        int $tenantId,
    ): AirbnbAvailabilityResponse {
        $request->validate();

        $signer = new AirbnbRequestSigner($this->clientId, $this->clientSecret);

        try {
            $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . ($this->accessToken ?? ''),
                    'X-Airbnb-API-Key' => $this->clientId,
                    'X-Airbnb-Signature' => $signer->sign($request->toAirbnbPayload()),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->timeout(self::TIMEOUT_SECONDS)
                ->post(self::BASE_URL . '/calendar_entries', $request->toAirbnbPayload());

            return $this->parseResponse($response->status(), $response->json() ?? [], $tenantId);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('AirbnbClient: Connection error', [
                'tenant_id' => $tenantId,
                'listing_id' => $request->listingId,
                'error' => $e->getMessage(),
                // NOTE: Never log access token or secrets
            ]);

            throw new AirbnbTransportException(
                tenantId: $tenantId,
                retryable: true,
                message: 'Connection to Airbnb failed: ' . $e->getMessage(),
            );
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return $this->handleRequestException($e, $tenantId);
        }
    }

    /**
     * Test connection to Airbnb API
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . ($this->accessToken ?? ''),
                    'X-Airbnb-API-Key' => $this->clientId,
                    'Content-Type' => 'application/json',
                ])
                ->timeout(5)
                ->get(self::BASE_URL . '/ping');

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Parse HTTP response to AirbnbAvailabilityResponse
     */
    private function parseResponse(int $status, array $body, int $tenantId): AirbnbAvailabilityResponse
    {
        // Airbnb returns 2xx for success
        if ($status >= 200 && $status < 300) {
            return AirbnbAvailabilityResponse::fromAirbnbApi($body);
        }

        // Airbnb returns 401 for auth errors
        if ($status === 401) {
            throw new AirbnbAuthenticationException(
                tenantId: $tenantId,
                message: $body['error_description'] ?? 'Airbnb authentication failed',
            );
        }

        // Airbnb returns 429 for rate limits
        if ($status === 429) {
            $retryAfter = isset($body['retry_after']) ? (int) $body['retry_after'] : 60;

            throw new AirbnbRateLimitException(
                tenantId: $tenantId,
                retryAfterSeconds: $retryAfter,
            );
        }

        // Airbnb returns 422 for business validation errors
        if ($status === 422) {
            throw new AirbnbRejectedRequestException(
                tenantId: $tenantId,
                rejectionCode: $body['error_code'] ?? 'VALIDATION_ERROR',
                rejectionDetails: $body['errors'] ?? [],
                message: $body['error'] ?? 'Airbnb rejected the request',
            );
        }

        // Airbnb returns 5xx for server errors (retryable)
        if ($status >= 500) {
            throw new AirbnbTransportException(
                tenantId: $tenantId,
                retryable: true,
                message: "Airbnb server error: HTTP {$status}",
            );
        }

        // Generic transport error
        throw new AirbnbTransportException(
            tenantId: $tenantId,
            retryable: false,
            message: "Airbnb unexpected response: HTTP {$status}",
        );
    }

    /**
     * Handle HTTP request exception
     *
     * @throws AirbnbAuthenticationException|AirbnbRateLimitException|AirbnbRejectedRequestException|AirbnbTransportException
     */
    private function handleRequestException(
        \Illuminate\Http\Client\RequestException $e,
        int $tenantId,
    ): never {
        $response = $e->response;
        $status = $response?->status() ?? 0;
        $body = $response?->json() ?? [];

        $this->parseResponse($status, $body, $tenantId);

        // If parseResponse didn't throw, wrap remaining errors
        throw new AirbnbTransportException(
            tenantId: $tenantId,
            retryable: $e->getCode() === 0,
            message: 'Airbnb request failed: ' . $e->getMessage(),
        );
    }
}
