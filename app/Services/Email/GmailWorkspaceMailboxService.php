<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Log;

/**
 * GmailWorkspaceMailboxService
 *
 * Wave 2 Phase 2 — Multi-mailbox Gmail integration
 *
 * Google Workspace Service Account + Domain-Wide Delegation ile
 * kurumsal @yalihanemlak.com.tr mailbox'ina dogrudan erisim saglar.
 *
 * Kullanici onayi GEREKMEZ — Service Account JWT ile calisir.
 * Tek .env degiskeni ile tum yetkilendirme tamamlanir.
 */
class GmailWorkspaceMailboxService
{
    private const GMAIL_API_BASE = 'https://gmail.googleapis.com';
    private const SCOPES_READONLY = [
        'https://www.googleapis.com/auth/gmail.readonly',
        'https://www.googleapis.com/auth/gmail.metadata',
    ];

    private const SCOPES_MODIFY = [
        'https://www.googleapis.com/auth/gmail.modify',
    ];

    /** @var string|null Cached access token */
    private ?string $cachedToken = null;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientEmail,
        private readonly string $privateKey,
        private readonly string $delegatedUser,
        private readonly string $mailboxLabel,
        private readonly ?int $tenantId = null,
    ) {}

    /**
     * Bu mailbox'un etkin olup olmadigini dondur.
     */
    public function isEnabled(): bool
    {
        return ! empty($this->clientId)
            && ! empty($this->clientEmail)
            && ! empty($this->privateKey);
    }

    /**
     * Access token al (Service Account JWT ile).
     */
    public function getAccessToken(): ?string
    {
        // Token onbellekte 55 dakika gecerli — yeniden istemci olusturmadan kullan
        if ($this->cachedToken !== null) {
            return $this->cachedToken;
        }

        $token = $this->requestAccessToken();
        if ($token !== null) {
            $this->cachedToken = $token;
        }

        return $token;
    }

    /**
     * Son history.list degerini al — incremental sync icin kullanilir.
     */
    public function getLastHistoryId(): ?string
    {
        return config("services.gmail.mailboxes.{$this->mailboxLabel}.history_id");
    }

    /**
     * Son history ID'yi kaydet.
     */
    public function saveLastHistoryId(string $historyId): void
    {
        $key = "services.gmail.mailboxes.{$this->mailboxLabel}.history_id";
        config([$key => $historyId]);
        $this->persistHistoryId($this->mailboxLabel, $historyId);
    }

    /**
     * Yeni mesajlari al (history.list veya initial sync).
     *
     * @return list<array{message_id: string, thread_id: string, from: string, subject: string, date: string, snippet: string, label_ids: list<string>}>
     */
    public function fetchNewMessages(?string $sinceHistoryId = null): array
    {
        $token = $this->getAccessToken();
        if ($token === null) {
            Log::error('[GmailWorkspace] No access token for mailbox', ['label' => $this->mailboxLabel]);
            return [];
        }

        // history.list ile degisiklikleri al
        if ($sinceHistoryId) {
            return $this->fetchViaHistory($token, $sinceHistoryId);
        }

        // İlk sync — son N mesaji al (UNREAD)
        return $this->fetchInitialMessages($token);
    }

    /**
     * Tek bir mesajin tam body'sini al.
     */
    public function fetchMessageBody(string $gmailMessageId): ?string
    {
        $token = $this->getAccessToken();
        if ($token === null) {
            return null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->timeout(10)
                ->get(self::GMAIL_API_BASE . "/gmail/v1/users/{$this->delegatedUser}/messages/{$gmailMessageId}", [
                    'format' => 'full',
                ]);

            if (! $response->successful()) {
                return null;
            }

            return $this->extractPlainBody($response->json()['payload'] ?? []);
        } catch (\Throwable $e) {
            Log::warning('[GmailWorkspace] fetchMessageBody failed', [
                'mailbox'     => $this->mailboxLabel,
                'message_id'  => $gmailMessageId,
                'error'       => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Mesaji okundu olarak isaretle (history ID tracking icin).
     */
    public function markAsRead(string $gmailMessageId): bool
    {
        $token = $this->getAccessToken();
        if ($token === null) {
            return false;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->timeout(5)
                ->post(
                    self::GMAIL_API_BASE . "/gmail/v1/users/{$this->delegatedUser}/messages/{$gmailMessageId}/modify",
                    ['removeLabelIds' => ['UNREAD']]
                );

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    // ── Private ─────────────────────────────────────────────────────────────

    private function requestAccessToken(): ?string
    {
        try {
            $now = time();
            $scopes = implode(' ', self::SCOPES_READONLY);

            $jwtHeader = $this->b64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $jwtClaims = $this->b64Url(json_encode([
                'iss'   => $this->clientEmail,
                'scope' => $scopes,
                'aud'   => 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
                'sub'   => $this->delegatedUser,
            ]));

            $privateKey = $this->preparePrivateKey($this->privateKey);
            openssl_sign(
                "{$jwtHeader}.{$jwtClaims}",
                $signature,
                $privateKey,
                OPENSSL_ALGO_SHA256
            );

            $assertion = "{$jwtHeader}.{$jwtClaims}." . $this->b64Url($signature);

            $resp = \Illuminate\Support\Facades\Http::asForm()
                ->timeout(10)
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $assertion,
                ]);

            if ($resp->successful()) {
                Log::info('[GmailWorkspace] Token acquired', [
                    'mailbox' => $this->mailboxLabel,
                    'user'    => $this->delegatedUser,
                ]);
                return $resp->json('access_token');
            }

            Log::error('[GmailWorkspace] Token request failed', [
                'mailbox' => $this->mailboxLabel,
                'status'  => $resp->status(),
                'body'    => substr($resp->body(), 0, 300),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('[GmailWorkspace] Token exception', [
                'mailbox' => $this->mailboxLabel,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function fetchViaHistory(string $token, string $startHistoryId): array
    {
        try {
            $resp = \Illuminate\Support\Facades\Http::withToken($token)
                ->timeout(15)
                ->get(
                    self::GMAIL_API_BASE . "/gmail/v1/users/{$this->delegatedUser}/history",
                    [
                        'startHistoryId' => $startHistoryId,
                        'historyTypes'    => 'messageAdded',
                        'maxResults'     => 100,
                    ]
                );

            if (! $resp->successful()) {
                Log::warning('[GmailWorkspace] history.list failed', [
                    'mailbox' => $this->mailboxLabel,
                    'status'  => $resp->status(),
                ]);
                return [];
            }

            $data = $resp->json();
            $newHistoryId = $data['historyId'] ?? null;
            $history = $data['history'] ?? [];

            if ($newHistoryId && $newHistoryId !== $startHistoryId) {
                $this->saveLastHistoryId($newHistoryId);
            }

            return $this->resolveMessagesFromHistory($token, $history);
        } catch (\Throwable $e) {
            Log::error('[GmailWorkspace] fetchViaHistory exception', [
                'mailbox' => $this->mailboxLabel,
                'error'   => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function fetchInitialMessages(string $token): array
    {
        try {
            $resp = \Illuminate\Support\Facades\Http::withToken($token)
                ->timeout(15)
                ->get(
                    self::GMAIL_API_BASE . "/gmail/v1/users/{$this->delegatedUser}/messages",
                    [
                        'maxResults' => 30,
                        'q'          => 'is:unread',
                    ]
                );

            if (! $resp->successful()) {
                return [];
            }

            $messages = $resp->json('messages', []);
            $result = [];

            foreach (array_slice($messages, 0, 20) as $msg) {
                $meta = $this->fetchMessageMetadata($token, $msg['id']);
                if ($meta !== null) {
                    $result[] = $meta;
                }
            }

            return $result;
        } catch (\Throwable) {
            return [];
        }
    }

    private function resolveMessagesFromHistory(string $token, array $history): array
    {
        $messageIds = [];
        foreach ($history as $item) {
            foreach ($item['messagesAdded'] ?? [] as $added) {
                $messageIds[$added['message']['id']] = true;
            }
        }
        $messageIds = array_keys($messageIds);

        if (empty($messageIds)) {
            return [];
        }

        $result = [];
        foreach ($messageIds as $id) {
            $meta = $this->fetchMessageMetadata($token, $id);
            if ($meta !== null) {
                $result[] = $meta;
            }
        }

        return $result;
    }

    private function fetchMessageMetadata(string $token, string $messageId): ?array
    {
        try {
            $resp = \Illuminate\Support\Facades\Http::withToken($token)
                ->timeout(10)
                ->get(
                    self::GMAIL_API_BASE . "/gmail/v1/users/{$this->delegatedUser}/messages/{$messageId}",
                    [
                        'format'         => 'metadata',
                        'metadataHeaders' => ['From', 'Subject', 'Date', 'Message-ID', 'To'],
                    ]
                );

            if (! $resp->successful()) {
                return null;
            }

            $data = $resp->json();
            $headers = $data['payload']['headers'] ?? [];

            return [
                'message_id'          => $data['id'],
                'thread_id'           => $data['threadId'],
                'from'               => $this->headerVal($headers, 'From'),
                'subject'            => $this->headerVal($headers, 'Subject'),
                'date'               => $this->headerVal($headers, 'Date'),
                'message_id_header'   => $this->headerVal($headers, 'Message-ID'),
                'snippet'            => $data['snippet'] ?? '',
                'label_ids'          => $data['labelIds'] ?? [],
                'mailbox_label'      => $this->mailboxLabel,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractPlainBody(array $payload): ?string
    {
        $mime = $payload['mimeType'] ?? '';

        if ($mime === 'text/plain') {
            return $this->decodeBodyPart($payload['body'] ?? []);
        }

        if (str_starts_with($mime, 'multipart/')) {
            foreach ($payload['parts'] ?? [] as $part) {
                $body = $this->extractPlainBody($part);
                if ($body !== null) {
                    return $body;
                }
            }
        }

        // text/html fallback
        if ($mime === 'text/html') {
            $html = $this->decodeBodyPart($payload['body'] ?? []);
            return $html !== null ? strip_tags($html) : null;
        }

        return null;
    }

    private function decodeBodyPart(array $body): ?string
    {
        if (empty($body['data'])) {
            return null;
        }
        $decoded = base64_decode(strtr($body['data'], '-_', '+/'), true);
        return $decoded !== false ? $decoded : null;
    }

    private function headerVal(array $headers, string $name): ?string
    {
        foreach ($headers as $h) {
            if (strcasecmp($h['name'], $name) === 0) {
                return $h['value'];
            }
        }
        return null;
    }

    private function b64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function preparePrivateKey(string $key): string
    {
        if (str_contains($key, "-----BEGIN ")) {
            return $key;
        }
        return "-----BEGIN RSA PRIVATE KEY-----\n"
            . chunk_split($key, 64, "\n")
            . "-----END RSA PRIVATE KEY-----\n";
    }

    private function persistHistoryId(string $mailboxLabel, string $historyId): void
    {
        try {
            $envFile = base_path('.env');
            $content = file_get_contents($envFile);
            $key = "GMAIL_{$mailboxLabel}_HISTORY_ID";

            if (preg_match("/^{$key}=/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$historyId}", $content);
            } else {
                $content .= "\n{$key}={$historyId}\n";
            }

            file_put_contents($envFile, $content);
        } catch (\Throwable) {
            // .env yazilabilir degilse sessiz atla — sonraki poll'da tekrar dener
        }
    }
}
