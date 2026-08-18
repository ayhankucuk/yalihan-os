<?php

namespace App\Infrastructure\ChannelManager\Booking;

use App\DTOs\ChannelManager\ChannelTransportResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * BookingTransport — Authenticated HTTP client for Booking.com Connectivity API.
 *
 * Sprint 4.10 — Booking.com Provider Wave 1
 * ADR-009 §3: thin HTTP wrapper over BookingCredentialManager
 *
 * SECURE: Never logs access tokens.
 * RETRY: 401 triggers single retry with refreshed token.
 */
class BookingTransport
{
    private const TIMEOUT = 30; // seconds

    public function __construct(
        private readonly BookingCredentialManagerInterface $credentialManager,
        private readonly ?string $baseUrl = null,
    ) {}

    /**
     * Make an authenticated GET request to the Booking.com API.
     *
     * @param int    $ilanId       Property/ilan ID for token resolution
     * @param string $path         API path (e.g. '/hotels/{id}/reservations')
     * @param array  $queryParams  Query string parameters
     *
     * @return ChannelTransportResult
     */
    public function get(int $ilanId, string $path, array $queryParams = []): ChannelTransportResult
    {
        $result = $this->request('GET', $ilanId, $path, $queryParams);
        return $this->toTransportResult($result);
    }

    /**
     * Make an authenticated POST request.
     *
     * @return ChannelTransportResult
     */
    public function post(int $ilanId, string $path, array $body = []): ChannelTransportResult
    {
        $result = $this->request('POST', $ilanId, $path, [], $body);
        return $this->toTransportResult($result);
    }

    // ─── Private ────────────────────────────────────────────────────

    /**
     * Execute HTTP request with token auth + 401 retry.
     *
     * Returns raw Guzzle response array: ['status' => int, 'body' => array|null, 'error' => string|null]
     */
    private function request(
        string $method,
        int    $ilanId,
        string $path,
        array  $queryParams = [],
        array  $body = [],
    ): array {
        $url = $this->buildUrl($path, $queryParams);

        // Get valid token (exchanges if expired)
        $tokenData = $this->credentialManager->getValidToken($ilanId);

        // First attempt
        $attempt = 1;
        $response = $this->sendWithToken($method, $url, $tokenData['access_token'], $body);

        // 401 → force token refresh → retry once
        if ($response['status'] === 401 && $attempt === 1) {
            Log::info('BookingTransport: received 401, refreshing token', [
                'ilan_id' => $ilanId,
                'path'    => $path,
            ]);
            $tokenData = $this->credentialManager->forceRefresh($ilanId);
            $response = $this->sendWithToken($method, $url, $tokenData['access_token'], $body);
            $attempt = 2;
        }

        return $response;
    }

    private function sendWithToken(
        string $method,
        string $url,
        string $token,
        array  $body,
    ): array {
        $start = microtime(true);

        try {
            $http = Http::timeout(self::TIMEOUT)
                ->withToken($token)
                ->acceptJson();

            $response = match ($method) {
                'GET'  => $http->get($url),
                'POST' => $http->post($url, $body),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            $duration = round((microtime(true) - $start) * 1000, 2);

            Log::info('BookingTransport: API response', [
                'method'   => $method,
                'url'      => $this->maskPath($url),
                'status'   => $response->status(),
                'duration_ms' => $duration,
            ]);

            return [
                'status' => $response->status(),
                'body'   => $response->successful() ? $response->json() : null,
                'error'  => $response->clientError() ? ($response->json()['message'] ?? 'Client error') : null,
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('BookingTransport: connection error', [
                'url'   => $this->maskPath($url),
                'error' => $e->getMessage(),
            ]);
            return [
                'status' => 0,
                'body'   => null,
                'error'  => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    private function toTransportResult(array $response): ChannelTransportResult
    {
        $status = $response['status'];

        if ($status >= 200 && $status < 300) {
            return ChannelTransportResult::success(
                providerReference: (string) $status,
                metadata: $response['body'] ?? [],
            );
        }

        // Classify retryable
        $retryable = match (true) {
            $status === 0                          => true,  // network error
            $status === 429                         => true,  // rate limit
            $status >= 500                         => true,  // server error
            $status === 401, $status === 403       => false, // auth failure
            default                               => false, // client error
        };

        return ChannelTransportResult::failure(
            errorCode: (string) $status,
            errorMessage: $response['error'] ?? "HTTP {$status}",
            retryable: $retryable,
            metadata: $response['body'] ?? [],
        );
    }

    private function buildUrl(string $path, array $queryParams): string
    {
        $base = $this->baseUrl ?? config('services.booking.api_url', 'https://supply-xml.booking.com');
        $url = rtrim($base, '/') . '/' . ltrim($path, '/');
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }
        return $url;
    }

    private function maskPath(string $url): string
    {
        // Remove token from query string for logging
        return preg_replace('/[?&]access_token=[^&]+/', '[TOKEN]', $url) ?? $url;
    }
}
