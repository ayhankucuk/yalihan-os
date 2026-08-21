<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

/**
 * GmailApiOAuthService
 *
 * Wave 2 Phase 2 — Keyless Gmail Integration
 *
 * OAuth 2.0 Authorization Code Flow ile Gmail API'ye erisim saglar.
 *
 * Akış:
 *   1. Ayhan ilk kurulumda consent URL'sini ziyaret eder
 *   2. Google redirect ile ?code= dondurur
 *   3. Bu servis code → access_token + refresh_token'e 교환 eder
 *   4. refresh_token encrypt edilip veritabanina kaydedilir
 *   5. access_token süresi dolunca refresh_token ile yenilenir
 *
 * Güvenlik:
 *   - refresh_token APP_KEY ile encrypt edilir (Crypt::encryptString)
 *   - Token diske yazilmaz, sadece veritabaninda tutulur
 *   - Hiçbir secrets Git'e yazilmaz
 *
 * SAAB Kural: Tum Gmail API islemleri bu servis uzerinden gider.
 */
class GmailApiOAuthService
{
    private const GMAIL_API_BASE = 'https://gmail.googleapis.com';
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    private const AUTH_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const SCOPES = [
        'https://www.googleapis.com/auth/gmail.readonly',
        'https://www.googleapis.com/auth/gmail.metadata',
    ];

    /** @var string|null In-memory access token cache */
    private ?string $accessToken = null;

    /** @var int|null Token expiry timestamp */
    private ?int $tokenExpiry = null;

    public function __construct(
        private readonly ?string $clientId = null,
        private readonly ?string $clientSecret = null,
        private readonly ?string $redirectUri = null,
    ) {}

    // ── OAuth 2.0 Flow ─────────────────────────────────────────────────

    /**
     * OAuth consent URL'sini olustur.
     * Ayhan bu URL'yi ziyaret edip Gmail erisimini onaylar.
     */
    public function getConsentUrl(?string $state = null): string
    {
        $params = [
            'client_id'    => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope'        => implode(' ', self::SCOPES),
            'access_type'   => 'offline',
            'prompt'       => 'consent',   // Her zaman consent screen göster
            'state'        => $state ?? '',
        ];

        return self::AUTH_ENDPOINT . '?' . http_build_query($params);
    }

    /**
     * Authorization code'u access_token + refresh_token'e çevir.
     * Kurulum sırasında bir kez çagırılır.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     * @throws \RuntimeException Token exchange basarisiz olursa
     */
    public function exchangeCodeForTokens(string $code): array
    {
        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post(self::TOKEN_ENDPOINT, [
                    'code'          => $code,
                    'client_id'     => $this->clientId,
                    'client_secret'  => $this->clientSecret,
                    'redirect_uri'  => $this->redirectUri,
                    'grant_type'    => 'authorization_code',
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    'Token exchange failed: ' . $response->body()
                );
            }

            $data = $response->json();

            Log::info('[GmailApiOAuthService] Tokens obtained', [
                'has_refresh_token' => isset($data['refresh_token']),
                'expires_in'       => $data['expires_in'] ?? 'N/A',
            ]);

            return [
                'access_token'  => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_in'    => $data['expires_in'] ?? 3600,
            ];
        } catch (\Throwable $e) {
            Log::error('[GmailApiOAuthService] Token exchange exception', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException(
                'OAuth token exchange failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * access_token'i yenile (refresh_token ile).
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post(self::TOKEN_ENDPOINT, [
                    'refresh_token' => $refreshToken,
                    'client_id'    => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type'  => 'refresh_token',
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    'Token refresh failed: ' . $response->body()
                );
            }

            $data = $response->json();

            return [
                'access_token' => $data['access_token'],
                'expires_in'   => $data['expires_in'] ?? 3600,
            ];
        } catch (\Throwable $e) {
            Log::error('[GmailApiOAuthService] Token refresh failed', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException(
                'OAuth token refresh failed: ' . $e->getMessage()
            );
        }
    }

    // ── Token Storage ────────────────────────────────────────────────

    /**
     * Refresh token'i encrypt edip veritabanina kaydet.
     */
    public function saveRefreshToken(string $refreshToken, int $tenantId): void
    {
        $encrypted = Crypt::encryptString($refreshToken);

        \Illuminate\Support\Facades\DB::table('oauth_tokens')->updateOrInsert(
            ['service' => 'gmail', 'tenant_id' => $tenantId],
            [
                'encrypted_token' => $encrypted,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        Log::info('[GmailApiOAuthService] Refresh token saved', [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Encrypt edilmis refresh token'i veritabanindan al ve decrypt et.
     */
    public function loadRefreshToken(int $tenantId): ?string
    {
        $row = \Illuminate\Support\Facades\DB::table('oauth_tokens')
            ->where('service', 'gmail')
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $row || empty($row->encrypted_token)) {
            return null;
        }

        try {
            return Crypt::decryptString($row->encrypted_token);
        } catch (\Throwable $e) {
            Log::error('[GmailApiOAuthService] Token decrypt failed', [
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function hasRefreshToken(int $tenantId): bool
    {
        return $this->loadRefreshToken($tenantId) !== null;
    }

    public function deleteRefreshToken(int $tenantId): void
    {
        \Illuminate\Support\Facades\DB::table('oauth_tokens')
            ->where('service', 'gmail')
            ->where('tenant_id', $tenantId)
            ->delete();

        Log::info('[GmailApiOAuthService] Token deleted', ['tenant_id' => $tenantId]);
    }

    // ── Gmail API Calls ──────────────────────────────────────────────

    /**
     * Gecerli access token al (cache'li veya yenilenmis).
     */
    public function getAccessToken(): ?string
    {
        // Bellekte gecerli token var mi?
        if ($this->accessToken !== null && $this->tokenExpiry !== null) {
            if (time() < $this->tokenExpiry - 60) {
                return $this->accessToken;
            }
        }

        // Yukle ve yenile
        $tenantId = $this->getDefaultTenantId();
        $refreshToken = $this->loadRefreshToken($tenantId);

        if ($refreshToken === null) {
            Log::warning('[GmailApiOAuthService] No refresh token found');
            return null;
        }

        try {
            $tokens = $this->refreshAccessToken($refreshToken);
            $this->accessToken = $tokens['access_token'];
            $this->tokenExpiry = time() + ($tokens['expires_in'] ?? 3600);

            return $this->accessToken;
        } catch (\Throwable) {
            $this->accessToken = null;
            $this->tokenExpiry = null;
            return null;
        }
    }

    /**
     * Son N mesaji al (INBOX).
     *
     * @return list<array>
     */
    public function fetchInboxMessages(int $maxResults = 20): array
    {
        $token = $this->getAccessToken();
        if ($token === null) {
            return [];
        }

        try {
            $resp = Http::withToken($token)
                ->timeout(15)
                ->get(self::GMAIL_API_BASE . '/gmail/v1/users/me/messages', [
                    'maxResults' => $maxResults,
                    'q'          => 'is:unread',
                ]);

            if (! $resp->successful()) {
                return [];
            }

            $messages = $resp->json('messages', []);
            $result = [];

            foreach (array_slice($messages, 0, min($maxResults, 20)) as $msgRef) {
                $meta = $this->fetchMessageMetadata($token, $msgRef['id']);
                if ($meta !== null) {
                    $result[] = $meta;
                }
            }

            return $result;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * history.list ile son degisiklikleri al.
     */
    public function fetchHistory(string $historyId, string $token): array
    {
        $accessToken = $token ?: $this->getAccessToken();
        if ($accessToken === null) {
            return [];
        }

        try {
            $resp = Http::withToken($accessToken)
                ->timeout(15)
                ->get(
                    self::GMAIL_API_BASE . '/gmail/v1/users/me/history',
                    [
                        'startHistoryId' => $historyId,
                        'historyTypes'    => 'messageAdded',
                        'maxResults'     => 100,
                    ]
                );

            if (! $resp->successful()) {
                return [];
            }

            return $resp->json('history', []);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Tek mesajin metadata'sini al.
     */
    public function fetchMessageMetadata(string $accessToken, string $messageId): ?array
    {
        try {
            $resp = Http::withToken($accessToken)
                ->timeout(10)
                ->get(
                    self::GMAIL_API_BASE . "/gmail/v1/users/me/messages/{$messageId}",
                    [
                        'format'          => 'metadata',
                        'metadataHeaders' => ['From', 'Subject', 'Date', 'Message-ID', 'To'],
                    ]
                );

            if (! $resp->successful()) {
                return null;
            }

            $data = $resp->json();
            $headers = $data['payload']['headers'] ?? [];

            return [
                'message_id'        => $data['id'],
                'thread_id'         => $data['threadId'],
                'from'             => $this->headerVal($headers, 'From'),
                'subject'          => $this->headerVal($headers, 'Subject'),
                'date'             => $this->headerVal($headers, 'Date'),
                'message_id_header' => $this->headerVal($headers, 'Message-ID'),
                'snippet'          => $data['snippet'] ?? '',
                'label_ids'        => $data['labelIds'] ?? [],
                'mailbox_label'    => 'workspace',
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    public function fetchMessageBody(string $accessToken, string $messageId): ?string
    {
        try {
            $resp = Http::withToken($accessToken)
                ->timeout(10)
                ->get(
                    self::GMAIL_API_BASE . "/gmail/v1/users/me/messages/{$messageId}",
                    ['format' => 'full']
                );

            if (! $resp->successful()) {
                return null;
            }

            return $this->extractBody($resp->json()['payload'] ?? []);
        } catch (\Throwable) {
            return null;
        }
    }

    // ── Private ──────────────────────────────────────────────────

    private function headerVal(array $headers, string $name): ?string
    {
        foreach ($headers as $h) {
            if (strcasecmp($h['name'], $name) === 0) {
                return $h['value'];
            }
        }
        return null;
    }

    private function extractBody(array $payload): ?string
    {
        $mime = $payload['mimeType'] ?? '';

        if ($mime === 'text/plain') {
            return $this->decodeBody($payload['body'] ?? []);
        }

        if (str_starts_with($mime, 'multipart/')) {
            foreach ($payload['parts'] ?? [] as $part) {
                $body = $this->extractBody($part);
                if ($body !== null) {
                    return $body;
                }
            }
        }

        if ($mime === 'text/html') {
            $html = $this->decodeBody($payload['body'] ?? []);
            return $html !== null ? strip_tags($html) : null;
        }

        return null;
    }

    private function decodeBody(array $body): ?string
    {
        if (empty($body['data'])) {
            return null;
        }
        $decoded = base64_decode(strtr($body['data'], '-_', '+/'), true);
        return $decoded !== false ? $decoded : null;
    }

    private function getDefaultTenantId(): int
    {
        // TenantContextService'ten al ya da tenant 5 (Yalihan Emlak) kullan
        try {
            $ctx = app(\App\Services\SaaS\TenantContextService::class);
            $t = $ctx->getTenant();
            return $t?->id ?? 5;
        } catch (\Throwable) {
            return 5;
        }
    }
}
