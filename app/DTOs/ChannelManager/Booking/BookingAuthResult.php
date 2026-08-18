<?php

namespace App\DTOs\ChannelManager\Booking;

/**
 * BookingAuthResult — Immutable DTO for token exchange responses.
 *
 * Sprint 4.10 — Booking.com Provider Wave 1
 * ADR-009 §4: two-legged machine-account token auth
 */
readonly final class BookingAuthResult
{
    public function __construct(
        public string  $accessToken,
        public string  $tokenType,
        public int     $expiresIn,        // seconds
        public ?string $refreshToken,
        public ?string $scope,
    ) {}

    public static function fromTokenExchangeResponse(array $json): self
    {
        return new self(
            accessToken:  $json['access_token']  ?? throw new \InvalidArgumentException('Missing access_token'),
            tokenType:    $json['token_type']    ?? 'Bearer',
            expiresIn:     (int) ($json['expires_in'] ?? 3600),
            refreshToken:  $json['refresh_token'] ?? null,
            scope:        $json['scope']         ?? null,
        );
    }

    /**
     * Calculate token expiry timestamp.
     */
    public function expiresAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable("+{$this->expiresIn} seconds");
    }

    /**
     * Check if a given expiry timestamp is in the past.
     */
    public static function isExpired(?\DateTimeInterface $expiresAt): bool
    {
        if ($expiresAt === null) {
            return true; // no token = treat as expired
        }
        return $expiresAt < new \DateTimeImmutable();
    }
}
