<?php

namespace App\Services\ChannelManager;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ChannexSignatureVerifier — HMAC-SHA256 signature verification for Channex webhooks.
 * CHANNEL_MANAGER_PROVIDER Wave 2 — ADR-007
 */
class ChannexSignatureVerifier
{
    private const HEADER = 'X-Channex-Signature';
    private const ALGO   = 'sha256';

    public function __construct(private readonly string $secret) {}

    public function verify(Request $request): bool
    {
        $signature = $request->header(self::HEADER);
        if (empty($signature)) {
            Log::warning('ChannexSignatureVerifier: missing signature header');
            return false;
        }
        if (!str_starts_with($signature, self::ALGO . '=')) {
            return false;
        }
        $expected = self::ALGO . '=' . hash_hmac(self::ALGO, $request->getContent(), $this->secret);
        $valid = hash_equals($expected, $signature);
        if (!$valid) {
            Log::warning('ChannexSignatureVerifier: signature mismatch', ['ip' => $request->ip()]);
        }
        return $valid;
    }
}
