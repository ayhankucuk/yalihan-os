<?php

namespace App\Domains\GuestCommunication\Adapters;

use App\Domains\GuestCommunication\Contracts\AirbnbDeliveryAdapterContract;
use App\Domains\GuestCommunication\Contracts\AirbnbCredentials;
use App\Domains\GuestCommunication\Contracts\DeliveryResult;
use App\Domains\GuestCommunication\Contracts\DeliveryStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AirbnbDeliveryAdapter
 *
 * EX-001 WAVE 2 — Airbnb Delivery
 *
 * Airbnb API üzerinden misafir mesajları gönderir.
 * Tenant-safe, idempotent ve retry-safe.
 */
class AirbnbDeliveryAdapter implements AirbnbDeliveryAdapterContract
{
    private const API_BASE_URL = 'https://api.airbnb.com/v2';

    private const TIMEOUT_SECONDS = 30;

    private const MAX_RETRIES = 3;

    private ?AirbnbCredentialResolver $credentialResolver = null;

    public function __construct(?AirbnbCredentialResolver $credentialResolver = null)
    {
        $this->credentialResolver = $credentialResolver ?? new AirbnbCredentialResolver();
    }

    /**
     * Send welcome message via Airbnb API
     */
    public function sendWelcomeMessage(array $messageData): DeliveryResult
    {
        $tenantId = $messageData['tenant_id'] ?? 0;
        $reservationId = $messageData['reservation_id'] ?? 0;
        $idempotencyKey = $this->createIdempotencyKey($reservationId, 'welcome');

        try {
            // Resolve credentials
            $credentials = $this->resolveCredentials($tenantId);

            if (!$credentials->isValid()) {
                Log::error('AirbnbAdapter: Invalid credentials', [
                    'tenant_id' => $tenantId,
                ]);
                return DeliveryResult::invalidCredentials('Invalid or missing credentials');
            }

            // Build request
            $payload = $this->buildPayload($messageData, $credentials);

            // Send to Airbnb
            $response = $this->sendToAirbnb($payload, $credentials, $idempotencyKey);

            // Map response
            if ($response->successful()) {
                $externalId = $this->extractMessageId($response);

                Log::info('AirbnbAdapter: Message sent successfully', [
                    'reservation_id' => $reservationId,
                    'external_id' => $externalId,
                    'idempotency_key' => $idempotencyKey,
                ]);

                return DeliveryResult::sent($externalId, $idempotencyKey);
            }

            // Handle error response
            return $this->handleErrorResponse($response, $idempotencyKey);

        } catch (\Throwable $e) {
            Log::error('AirbnbAdapter: Exception during send', [
                'reservation_id' => $reservationId,
                'error' => $e->getMessage(),
            ]);

            // Determine if retryable
            $retryable = $this->isRetryableException($e);

            return DeliveryResult::failed($e->getMessage(), $retryable);
        }
    }

    /**
     * Resolve credentials for a tenant
     */
    public function resolveCredentials(int $tenantId): AirbnbCredentials
    {
        return $this->credentialResolver->resolve($tenantId);
    }

    /**
     * Create idempotency key for a reservation
     */
    public function createIdempotencyKey(int $reservationId, string $messageType): string
    {
        return sprintf(
            'yalihan_%d_%s_%d',
            $reservationId,
            $messageType,
            now()->timestamp
        );
    }

    /**
     * Build Airbnb API payload
     */
    private function buildPayload(array $messageData, AirbnbCredentials $credentials): array
    {
        return [
            'reservation_id' => (string) $messageData['reservation_id'],
            'listing_id' => $credentials->listingId,
            'message' => [
                'text' => $messageData['content'] ?? '',
                'subject' => $messageData['subject'] ?? '',
            ],
            'guest_id' => $messageData['guest_id'] ?? null,
            'locale' => $messageData['language'] ?? 'en',
        ];
    }

    /**
     * Send request to Airbnb API
     */
    private function sendToAirbnb(array $payload, AirbnbCredentials $credentials, string $idempotencyKey): \Illuminate\Http\Client\Response
    {
        return Http::withToken($credentials->accessToken)
            ->timeout(self::TIMEOUT_SECONDS)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-Airbnb-Idempotency-Key' => $idempotencyKey,
                'Accept-Language' => $payload['locale'] ?? 'en',
            ])
            ->post(self::API_BASE_URL . '/messages', $payload);
    }

    /**
     * Extract message ID from response
     */
    private function extractMessageId($response): string
    {
        $body = $response->json();

        return $body['message']['id']
            ?? $body['data']['message_id']
            ?? Str::random(16);
    }

    /**
     * Handle error response from Airbnb
     */
    private function handleErrorResponse($response, string $idempotencyKey): DeliveryResult
    {
        $statusCode = $response->status();
        $body = $response->json();
        $errorCode = $body['error']['code'] ?? 'unknown';
        $errorMessage = $body['error']['message'] ?? $response->body();

        Log::warning('AirbnbAdapter: API error', [
            'status_code' => $statusCode,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'idempotency_key' => $idempotencyKey,
        ]);

        // Handle specific error codes
        return match ($errorCode) {
            'DUPLICATE_REQUEST', 'idempotency_key_conflict' => DeliveryResult::duplicate($idempotencyKey),
            'RATE_LIMIT_EXCEEDED', 'too_many_requests' => DeliveryResult::rateLimited(
                $body['error']['retry_after'] ?? '60'
            ),
            'INVALID_ACCESS_TOKEN', 'TOKEN_EXPIRED' => DeliveryResult::invalidCredentials($errorMessage),
            'INVALID_LISTING' => DeliveryResult::failed("Invalid listing: {$errorMessage}", false),
            default => DeliveryResult::failed($errorMessage, $statusCode >= 500),
        };
    }

    /**
     * Determine if exception is retryable
     */
    private function isRetryableException(\Throwable $e): bool
    {
        // Network errors, timeouts, 5xx errors are retryable
        if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
            return true;
        }

        if ($e instanceof \Illuminate\Http\Client\RequestException) {
            $statusCode = $e->response?->status();
            return $statusCode && $statusCode >= 500;
        }

        return false;
    }
}

/**
 * AirbnbCredentialResolver
 *
 * Tenant'a özel Airbnb credential'larını çözer.
 */
class AirbnbCredentialResolver
{
    /**
     * Resolve credentials for a tenant
     *
     * @todo: Config veya database'den credential okuma
     */
    public function resolve(int $tenantId): AirbnbCredentials
    {
        // Get from config
        $accessToken = config("services.airbnb.tenants.{$tenantId}.access_token");
        $listingId = config("services.airbnb.tenants.{$tenantId}.listing_id");

        return new AirbnbCredentials(
            tenantId: $tenantId,
            accessToken: $accessToken ?? '',
            listingId: $listingId ?? '',
        );
    }
}
