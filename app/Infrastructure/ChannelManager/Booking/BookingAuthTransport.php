<?php

namespace App\Infrastructure\ChannelManager\Booking;

use App\DTOs\ChannelManager\Booking\BookingAuthResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * BookingAuthTransport — Two-legged machine-account token authentication.
 *
 * Sprint 4.10 — Booking.com Provider Wave 1
 * ADR-009 §4: two-legged machine-account flow
 *
 * NO Basic Auth. Credentials are Client ID + Client Secret → token exchange.
 * Never logs client_secret or access_token.
 */
class BookingAuthTransport
{
    private const TOKEN_ENDPOINT = '/oauth/tokens';

    public function __construct(
        private readonly ?string $baseUrl = null,
    ) {}

    /**
     * Exchange client credentials for an access token.
     * Called once per token lifecycle (acquisition or refresh).
     *
     * @throws BookingAuthException on failure
     */
    public function exchangeToken(string $clientId, string $clientSecret): BookingAuthResult
    {
        $url = $this->tokenUrl();

        Log::info('BookingAuthTransport: token exchange requested', [
            'client_id' => $this->mask($clientId),
            'url'      => $url,
        ]);

        try {
            $response = Http::asForm()
                ->withTimeout(10)
                ->post($url, [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if ($response->failed()) {
                $body = $response->json();
                Log::error('BookingAuthTransport: token exchange failed', [
                    'status'  => $response->status(),
                    'message' => $body['message'] ?? $response->body(),
                ]);
                throw new BookingAuthException(
                    'Token exchange failed: ' . ($body['message'] ?? $response->status()),
                    $response->status(),
                );
            }

            $result = BookingAuthResult::fromTokenExchangeResponse($response->json());

            Log::info('BookingAuthTransport: token exchange succeeded', [
                'expires_in' => $result->expiresIn,
                'token_type' => $result->tokenType,
            ]);

            return $result;

        } catch (BookingAuthException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('BookingAuthTransport: token exchange exception', [
                'error' => $e->getMessage(),
            ]);
            throw new BookingAuthException('Token exchange network error: ' . $e->getMessage(), 0, $e);
        }
    }

    private function tokenUrl(): string
    {
        $base = $this->baseUrl ?? config('services.booking.api_url', 'https://supply-xml.booking.com');
        return rtrim($base, '/') . self::TOKEN_ENDPOINT;
    }

    private function mask(string $value): string
    {
        if (strlen($value) <= 8) {
            return str_repeat('*', strlen($value));
        }
        return substr($value, 0, 4) . '****' . substr($value, -4);
    }
}
