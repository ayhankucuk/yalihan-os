<?php

namespace App\Services\Email;

/**
 * GmailOAuthService
 *
 * Wave 2 — Gmail Communications Intelligence
 *
 * Gmail API'ye OAuth 2.0 ile erişim saglar.
 * Token management + email fetching sorumluluğu.
 *
 * İki mod:
 *   1. Service Account (recommended) — Yalihan hesabi icin
 *   2. Polling / history.list — Degisiklik tespiti
 *
 * SAAB Kural: Tum Gmail API islemleri bu servis uzerinden gider.
 * Direkt HTTP islemleri YASAK.
 */
class GmailOAuthService
{
    private const GMAIL_API_BASE = 'https://gmail.googleapis.com';

    // OAuth scopes for Gmail read-only access
    private const SCOPES = [
        'https://www.googleapis.com/auth/gmail.readonly',
        'https://www.googleapis.com/auth/gmail.metadata',
    ];

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientEmail,
        private readonly string $privateKey,
    ) {}

    /**
     * Bu servis etkin mi (credential var mi)?
     */
    public function isEnabled(): bool
    {
        return ! empty($this->clientId);
    }

    /**
     * Gmail API'ye erisim icin access token al.
     *
     * @return string|null Access token veya basarisizlikta null
     */
    public function getAccessToken(): ?string
    {
        try {
            $now = time();
            $scopes = implode(' ', self::SCOPES);

            $jwtHeader = $this->base64UrlEncode(json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT',
            ]));

            $jwtClaimSet = $this->base64UrlEncode(json_encode([
                'iss'   => $this->clientEmail,
                'scope' => $scopes,
                'aud'   => 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]));

            $privateKey = $this->preparePrivateKey($this->privateKey);

            openssl_sign(
                $jwtHeader . '.' . $jwtClaimSet,
                $signature,
                $privateKey,
                OPENSSL_ALGO_SHA256
            );

            $jwtSignature = $this->base64UrlEncode($signature);
            $assertion = $jwtHeader . '.' . $jwtClaimSet . '.' . $jwtSignature;

            $response = \Illuminate\Support\Facades\Http::asForm()
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $assertion,
                ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            \Illuminate\Support\Facades\Log::warning('[GmailOAuthService] Token request failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[GmailOAuthService] getAccessToken exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Gmail history.list ile son değişiklikleri al.
     *
     * @param string $historyId Son bilinen history ID
     * @param string $accessToken
     * @return array{history: array, historyId: string}|null
     */
    public function getHistory(string $historyId, string $accessToken): ?array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->get(self::GMAIL_API_BASE . '/gmail/v1/users/me/history', [
                    'startHistoryId' => $historyId,
                    'historyTypes'    => 'messageAdded',
                    'maxResults'     => 50,
                ]);

            if ($response->successful()) {
                return [
                    'history'    => $response->json('history', []),
                    'historyId' => $response->json('historyId'),
                ];
            }

            return null;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[GmailOAuthService] getHistory failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Gmail message metadata (headers, subject, from) al — full body yok.
     *
     * @param string $messageId
     * @param string $accessToken
     * @return array|null
     */
    public function getMessageMetadata(string $messageId, string $accessToken): ?array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->get(self::GMAIL_API_BASE . "/gmail/v1/users/me/messages/{$messageId}", [
                    'format' => 'metadata',
                    'metadataHeaders' => ['From', 'Subject', 'Date', 'Message-ID', 'To'],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'message_id'  => $data['id'],
                    'thread_id'   => $data['threadId'],
                    'from'        => $this->extractHeader($data['payload']['headers'] ?? [], 'From'),
                    'subject'     => $this->extractHeader($data['payload']['headers'] ?? [], 'Subject'),
                    'date'        => $this->extractHeader($data['payload']['headers'] ?? [], 'Date'),
                    'message_id_header' => $this->extractHeader($data['payload']['headers'] ?? [], 'Message-ID'),
                    'snippet'    => $data['snippet'] ?? '',
                    'label_ids'  => $data['labelIds'] ?? [],
                ];
            }

            return null;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[GmailOAuthService] getMessageMetadata failed', [
                'message_id' => $messageId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Gmail message'in tam plaintext body al.
     *
     * @param string $messageId
     * @param string $accessToken
     * @return string|null
     */
    public function getMessageBody(string $messageId, string $accessToken): ?string
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->get(self::GMAIL_API_BASE . "/gmail/v1/users/me/messages/{$messageId}", [
                    'format' => 'full',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            $payload = $data['payload'] ?? [];

            // Try to find text/plain part
            $body = $this->extractBodyFromPayload($payload);

            return $body;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[GmailOAuthService] getMessageBody failed', [
                'message_id' => $messageId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ── Private ─────────────────────────────────────────────────────────────

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function preparePrivateKey(string $key): OpenSSLAsymmetricKey|string
    {
        // Support both with and without header/footer
        if (str_contains($key, '-----BEGIN ')) {
            return $key;
        }

        return "-----BEGIN RSA PRIVATE KEY-----\n"
            . chunk_split($key, 64, "\n")
            . "-----END RSA PRIVATE KEY-----\n";
    }

    private function extractHeader(array $headers, string $name): ?string
    {
        foreach ($headers as $header) {
            if (strcasecmp($header['name'], $name) === 0) {
                return $header['value'];
            }
        }
        return null;
    }

    private function extractBodyFromPayload(array $payload): ?string
    {
        $mimeType = $payload['mimeType'] ?? '';

        // text/plain directly in payload
        if ($mimeType === 'text/plain') {
            return $this->decodeBody($payload['body'] ?? []);
        }

        // text/html (not ideal but fallback)
        if ($mimeType === 'text/html') {
            return strip_tags($this->decodeBody($payload['body'] ?? []));
        }

        // multipart/* — recurse into parts
        if (str_starts_with($mimeType, 'multipart/')) {
            foreach ($payload['parts'] ?? [] as $part) {
                $body = $this->extractBodyFromPayload($part);
                if ($body !== null) {
                    return $body;
                }
            }
        }

        return null;
    }

    private function decodeBody(array $body): ?string
    {
        if (empty($body['data'])) {
            return null;
        }

        $data = $body['data'];
        $encoded = strtr($data, '-_', '+/');

        // Add padding if needed
        $pad = strlen($encoded) % 4;
        if ($pad > 0) {
            $encoded .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($encoded, true);

        return $decoded !== false ? $decoded : null;
    }
}
