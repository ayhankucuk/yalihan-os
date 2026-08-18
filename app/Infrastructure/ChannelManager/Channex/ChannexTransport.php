<?php

namespace App\Infrastructure\ChannelManager\Channex;

use App\Contracts\ChannelManager\ChannelTransportContract;
use App\DTOs\ChannelManager\ChannelTransportResult;
use App\Infrastructure\ChannelManager\Channex\Exceptions\ChannexAuthenticationException;
use App\Infrastructure\ChannelManager\Channex\Exceptions\ChannexRateLimitException;
use App\Infrastructure\ChannelManager\Channex\Exceptions\ChannexTransportException;
use Illuminate\Support\Facades\Log;

/**
 * ChannexTransport — ChannelTransportContract implementation for Channex.
 *
 * CHANNEL_MANAGER_CHANNEX_WAVE1 — Implementation
 *
 * TRANSPORT-ONLY: This class has NO domain logic.
 * - Maps availability data to Channex API format
 * - Delegates HTTP operations to ChannexClient
 * - Translates provider errors to ChannelTransportResult
 *
 * SECURE: Never logs API keys or credentials.
 *
 * @implements ChannelTransportContract
 */
class ChannexTransport implements ChannelTransportContract
{
    private const MAX_AVAILABILITY_DATES_PER_REQUEST = 100;

    public function __construct(
        private readonly mixed $client = null,
    ) {}

    /**
     * Push availability to Channex.
     *
     * Maps canonical availability format to Channex API payload.
     * Idempotent via correlationId (Channex X-Correlation-Id header).
     *
     * @inheritDoc
     */
    public function pushAvailability(
        int    $tenantId,
        string $externalListingId,
        string $correlationId,
        array  $availabilityData,
    ): ChannelTransportResult {
        $client = $this->resolveClient();

        // Map to Channex format
        $mapper = new ChannexAvailabilityMapper();
        $payload = $mapper->toChannexAvailabilityPayload($availabilityData);

        if (empty($payload['data'])) {
            Log::info('ChannexTransport: No availability data to push', [
                'tenant_id'      => $tenantId,
                'listing_id'     => $this->maskId($externalListingId),
                'correlation_id' => $correlationId,
            ]);
            return ChannelTransportResult::success(
                providerReference: 'no-op',
                metadata: ['skipped' => true, 'date_count' => 0],
            );
        }

        try {
            $response = $client->pushAvailability(
                apiKey: $this->resolveApiKey($tenantId),
                propertyId: $externalListingId,
                correlationId: $correlationId,
                payload: $payload,
            );

            $processedCount = $response['meta']['processed'] ?? count($payload['data']);
            $providerRef = $response['data'][0]['id'] ?? $correlationId;

            Log::info('ChannexTransport: Push success', [
                'tenant_id'      => $tenantId,
                'listing_id'     => $this->maskId($externalListingId),
                'correlation_id' => $correlationId,
                'processed'     => $processedCount,
            ]);

            return ChannelTransportResult::success(
                providerReference: (string) $providerRef,
                metadata: [
                    'processed_count' => $processedCount,
                    'channex_job_id'  => $response['meta']['job_id'] ?? null,
                ],
            );

        } catch (ChannexAuthenticationException $e) {
            Log::warning('ChannexTransport: Authentication failed', [
                'tenant_id'      => $tenantId,
                'listing_id'     => $this->maskId($externalListingId),
                'correlation_id' => $correlationId,
                // apiKey intentionally omitted
            ]);
            return ChannelTransportResult::failure(
                errorCode: 'AUTH_FAILED',
                errorMessage: 'Channex authentication failed',
                retryable: false,
                metadata: ['original_error' => $e->getMessage()],
            );

        } catch (ChannexRateLimitException $e) {
            Log::warning('ChannexTransport: Rate limited', [
                'tenant_id'      => $tenantId,
                'listing_id'     => $this->maskId($externalListingId),
                'correlation_id' => $correlationId,
                'retry_after'   => $e->retryAfter,
            ]);
            return ChannelTransportResult::failure(
                errorCode: 'RATE_LIMIT',
                errorMessage: 'Channex rate limit exceeded',
                retryable: true,
                metadata: ['retry_after' => $e->retryAfter],
            );

        } catch (ChannexTransportException $e) {
            Log::error('ChannexTransport: Transport error', [
                'tenant_id'      => $tenantId,
                'listing_id'     => $this->maskId($externalListingId),
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
                'retryable'      => $e->retryable,
            ]);
            return ChannelTransportResult::failure(
                errorCode: 'TRANSPORT_ERROR',
                errorMessage: $e->getMessage(),
                retryable: $e->retryable,
                metadata: ['original_error' => $e->getMessage()],
            );

        } catch (\Throwable $e) {
            Log::error('ChannexTransport: Unexpected error', [
                'tenant_id'      => $tenantId,
                'listing_id'     => $this->maskId($externalListingId),
                'correlation_id' => $correlationId,
                'error_type'     => get_class($e),
                'error'          => $e->getMessage(),
            ]);
            // Non-retryable: unknown errors should not trigger retries
            return ChannelTransportResult::failure(
                errorCode: 'UNKNOWN_ERROR',
                errorMessage: 'Unexpected transport error: ' . $e->getMessage(),
                retryable: false,
                metadata: ['error_type' => get_class($e)],
            );
        }
    }

    /**
     * Pull availability from Channex.
     *
     * Returns raw Channex data. Caller maps to canonical format.
     *
     * @inheritDoc
     */
    public function pullAvailability(
        int    $tenantId,
        string $externalListingId,
        string $correlationId,
        string $fromDate,
        string $toDate,
    ): ChannelTransportResult {
        $client = $this->resolveClient();

        try {
            $response = $client->pullAvailability(
                apiKey: $this->resolveApiKey($tenantId),
                propertyId: $externalListingId,
                fromDate: $fromDate,
                toDate: $toDate,
            );

            $events = $this->extractAvailabilityEvents($response);

            Log::info('ChannexTransport: Pull success', [
                'tenant_id'      => $tenantId,
                'listing_id'     => $this->maskId($externalListingId),
                'correlation_id' => $correlationId,
                'event_count'   => count($events),
            ]);

            return ChannelTransportResult::success(
                providerReference: $correlationId,
                metadata: [
                    'events' => $events,
                    'raw_response' => $response,
                ],
            );

        } catch (ChannexAuthenticationException $e) {
            return ChannelTransportResult::failure(
                errorCode: 'AUTH_FAILED',
                errorMessage: 'Channex authentication failed',
                retryable: false,
            );

        } catch (ChannexRateLimitException $e) {
            return ChannelTransportResult::failure(
                errorCode: 'RATE_LIMIT',
                errorMessage: 'Channex rate limit exceeded',
                retryable: true,
                metadata: ['retry_after' => $e->retryAfter],
            );

        } catch (ChannexTransportException $e) {
            return ChannelTransportResult::failure(
                errorCode: 'TRANSPORT_ERROR',
                errorMessage: $e->getMessage(),
                retryable: $e->retryable,
            );

        } catch (\Throwable $e) {
            Log::error('ChannexTransport: Unexpected pull error', [
                'tenant_id'      => $tenantId,
                'listing_id'     => $this->maskId($externalListingId),
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
            ]);
            return ChannelTransportResult::failure(
                errorCode: 'UNKNOWN_ERROR',
                errorMessage: 'Unexpected transport error',
                retryable: false,
            );
        }
    }

    /**
     * Test connection to Channex.
     *
     * @inheritDoc
     */
    public function testConnection(int $tenantId): ChannelTransportResult
    {
        $client = $this->resolveClient();

        try {
            $isValid = $client->testConnection(
                apiKey: $this->resolveApiKey($tenantId),
            );

            if ($isValid) {
                return ChannelTransportResult::success(
                    providerReference: 'connection_ok',
                    metadata: ['tested_at' => now()->toIso8601String()],
                );
            }

            return ChannelTransportResult::failure(
                errorCode: 'AUTH_FAILED',
                errorMessage: 'Channex credentials invalid',
                retryable: false,
            );

        } catch (ChannexAuthenticationException $e) {
            return ChannelTransportResult::failure(
                errorCode: 'AUTH_FAILED',
                errorMessage: 'Channex authentication failed',
                retryable: false,
            );

        } catch (\Throwable $e) {
            return ChannelTransportResult::failure(
                errorCode: 'TRANSPORT_ERROR',
                errorMessage: 'Cannot connect to Channex',
                retryable: true,
                metadata: ['error' => $e->getMessage()],
            );
        }
    }

    /**
     * Resolve API client.
     *
     * Allows injection of mock client for testing.
     */
    private function resolveClient(): mixed
    {
        return $this->client ?? new ChannexClient();
    }

    /**
     * Resolve API key for tenant.
     *
     * Implementation note: In production, this resolves from IlanTakvimSync.
     * For sandbox/testing, uses env config.
     *
     * @throws \RuntimeException if no API key found
     */
    private function resolveApiKey(int $tenantId): string
    {
        // Production: resolve from IlanTakvimSync
        // $mapping = IlanTakvimSync::where('tenant_id', $tenantId)->first();
        // return $mapping->api_key;

        // Sandbox/testing: resolve from env
        $apiKey = config("channels.channex.api_key");

        if (empty($apiKey)) {
            throw new \RuntimeException(
                "No Channex API key configured for tenant {$tenantId}"
            );
        }

        return $apiKey;
    }

    /**
     * Extract availability events from Channex response.
     *
     * Validates and normalizes response structure.
     */
    private function extractAvailabilityEvents(array $response): array
    {
        if (!isset($response['data']) || !is_array($response['data'])) {
            Log::warning('ChannexTransport: Invalid pull response structure', [
                'has_data' => isset($response['data']),
                'response_keys' => array_keys($response),
            ]);
            return [];
        }

        $events = [];
        foreach ($response['data'] as $item) {
            if (!isset($item['date'], $item['available'])) {
                continue;
            }

            $events[] = [
                'date'      => $item['date'],
                'available' => (bool) $item['available'],
                'booked'    => (int) ($item['booked'] ?? 0),
                'room_type' => $item['room_type_id'] ?? null,
            ];
        }

        return $events;
    }

    /**
     * Mask external ID for logging (security).
     */
    private function maskId(string $id): string
    {
        if (strlen($id) <= 8) {
            return '***';
        }
        return substr($id, 0, 4) . '...' . substr($id, -4);
    }
}
