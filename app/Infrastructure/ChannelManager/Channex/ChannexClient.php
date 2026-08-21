<?php

namespace App\Infrastructure\ChannelManager\Channex;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ChannexClient — HTTP client for Channex API.
 *
 * CHANNEL_MANAGER_PROVIDER Wave 1 — ADR-006
 *
 * Low-level HTTP transport. No domain logic.
 * Credentials resolved externally (IlanTakvimSync) and passed in.
 *
 * SECURITY: NEVER log apiKey or any credential values.
 * Log only error codes, HTTP status, and correlation IDs.
 */
class ChannexClient
{
    private const BASE_URL = 'https://staging.channex.io/api/v1';
    private const TIMEOUT_SECONDS = 15;
    private const CONNECT_TIMEOUT_SECONDS = 5;

    /**
     * Push availability to Channex.
     *
     * @param string $apiKey        Channex API key (tenant-scoped) — NEVER logged
     * @param string $propertyId    Channex property UUID
     * @param string $correlationId Idempotency key
     * @param array  $payload       Channex-formatted availability payload
     *
     * @throws ChannexTransportException on network/HTTP error
     * @throws ChannexAuthenticationException on 401/403
     * @throws ChannexRateLimitException on 429
     */
    public function pushAvailability(
        string $apiKey,
        string $propertyId,
        string $correlationId,
        array  $payload,
    ): array {
        $response = $this->post(
            apiKey: $apiKey,
            path: "/properties/{$propertyId}/availability",
            data: $payload,
            correlationId: $correlationId,
        );

        return $response->json() ?? [];
    }

    /**
     * Pull availability from Channex.
     *
     * @param string $apiKey     Channex API key — NEVER logged
     * @param string $propertyId Channex property UUID
     * @param string $fromDate   YYYY-MM-DD inclusive
     * @param string $toDate     YYYY-MM-DD exclusive
     *
     * @return array Raw Channex response data
     */
    public function pullAvailability(
        string $apiKey,
        string $propertyId,
        string $fromDate,
        string $toDate,
    ): array {
        $response = $this->get(
            apiKey: $apiKey,
            path: "/properties/{$propertyId}/availability",
            query: ['date_from' => $fromDate, 'date_to' => $toDate],
        );

        return $response->json() ?? [];
    }

    /**
     * Retrieve a specific booking revision by ID from Channex API.
     *
     * @param string $apiKey     Channex API key — NEVER logged
     * @param string $revisionId Booking revision UUID
     * @return array
     */
    public function getBookingRevision(string $apiKey, string $revisionId): array
    {
        $response = $this->get(
            apiKey: $apiKey,
            path: "/booking_revisions/{$revisionId}",
        );

        return $response->json() ?? [];
    }

    /**
     * Retrieve the unacknowledged booking revisions feed.
     *
     * @param string $apiKey Channex API key — NEVER logged
     * @param array  $query  Optional query params (limit, etc.)
     * @return array
     */
    public function getBookingRevisionsFeed(string $apiKey, array $query = []): array
    {
        $response = $this->get(
            apiKey: $apiKey,
            path: '/booking_revisions/feed',
            query: $query,
        );

        return $response->json() ?? [];
    }

    /**
     * Explicitly acknowledge a booking revision in Channex API.
     *
     * @param string $apiKey        Channex API key — NEVER logged
     * @param string $revisionId    Booking revision UUID
     * @param string $correlationId Idempotency tracking key
     * @return bool
     *
     * @throws ChannexAcknowledgementException on failure
     */
    public function acknowledgeBookingRevision(
        string $apiKey,
        string $revisionId,
        string $correlationId = '',
    ): bool {
        if ($correlationId === '') {
            $correlationId = 'ack_' . $revisionId . '_' . time();
        }

        try {
            $response = $this->post(
                apiKey: $apiKey,
                path: "/booking_revisions/{$revisionId}/ack",
                data: [],
                correlationId: $correlationId,
            );

            return $response->successful();
        } catch (\Exception $e) {
            $status = ($e instanceof ChannexTransportException && $e->getPrevious() instanceof \Illuminate\Http\Client\RequestException)
                ? (int) $e->getPrevious()->response?->status()
                : 0;

            throw new ChannexAcknowledgementException(
                httpStatus: $status,
                isRetryable: true,
                message: "Channex ACK failed for revision {$revisionId}: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * Test connection / credential validation.
     *
     * @param string $apiKey Channex API key — NEVER logged
     *
     * @return bool True if credentials are valid
     *
     * @throws ChannexAuthenticationException on 401/403
     * @throws ChannexTransportException on network error
     */
    public function testConnection(string $apiKey): bool
    {
        try {
            $response = $this->get(
                apiKey: $apiKey,
                path: '/properties',
                query: ['per_page' => 1],
            );
            return $response->successful();
        } catch (ChannexAuthenticationException) {
            return false;
        }
    }

    // ─── Private HTTP helpers ────────────────────────────────────────

    private function post(string $apiKey, string $path, array $data, string $correlationId): Response
    {
        try {
            $response = Http::withToken($apiKey)
                ->withHeaders([
                    'X-Correlation-Id' => $correlationId,
                    'Content-Type'     => 'application/json',
                    'Accept'           => 'application/json',
                ])
                ->timeout(self::TIMEOUT_SECONDS)
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->post(self::BASE_URL . $path, $data);

            $this->handleHttpError($response, $path);

            return $response;

        } catch (ChannexAuthenticationException|ChannexRateLimitException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('ChannexClient: POST transport error', [
                'path'           => $path,
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
                // apiKey intentionally omitted
            ]);
            throw new ChannexTransportException(
                message: 'Channex transport error: ' . $e->getMessage(),
                retryable: true,
                previous: $e,
            );
        }
    }

    private function get(string $apiKey, string $path, array $query = []): Response
    {
        try {
            $response = Http::withToken($apiKey)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->timeout(self::TIMEOUT_SECONDS)
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->get(self::BASE_URL . $path, $query);

            $this->handleHttpError($response, $path);

            return $response;

        } catch (ChannexAuthenticationException|ChannexRateLimitException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('ChannexClient: GET transport error', [
                'path'  => $path,
                'error' => $e->getMessage(),
                // apiKey intentionally omitted
            ]);
            throw new ChannexTransportException(
                message: 'Channex transport error: ' . $e->getMessage(),
                retryable: true,
                previous: $e,
            );
        }
    }

    /**
     * Map HTTP error codes to typed exceptions.
     */
    private function handleHttpError(Response $response, string $path): void
    {
        if ($response->successful()) {
            return;
        }

        $status = $response->status();

        if (in_array($status, [401, 403])) {
            Log::warning('ChannexClient: Authentication failed', [
                'status' => $status,
                'path'   => $path,
                // apiKey intentionally omitted
            ]);
            throw new ChannexAuthenticationException(
                "Channex authentication failed (HTTP {$status})"
            );
        }

        if ($status === 429) {
            $retryAfter = (int) ($response->header('Retry-After') ?? 60);
            Log::warning('ChannexClient: Rate limited', [
                'retry_after' => $retryAfter,
                'path'        => $path,
            ]);
            throw new ChannexRateLimitException(
                message: "Channex rate limit exceeded",
                retryAfter: $retryAfter,
            );
        }

        Log::error('ChannexClient: Unexpected HTTP error', [
            'status' => $status,
            'path'   => $path,
        ]);
        throw new ChannexTransportException(
            message: "Channex HTTP error {$status}",
            retryable: $status >= 500,
        );
    }
}
