<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * MetaWebhookSignatureTest
 *
 * Verifies that Facebook, Instagram, and WhatsApp webhook controllers
 * correctly validate X-Hub-Signature-256 using the Meta App Secret.
 */
class MetaWebhookSignatureTest extends TestCase
{
    private string $appSecret = 'test_meta_app_secret_12345';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.facebook.app_secret' => $this->appSecret,
            'services.facebook.webhook_verify_token' => 'fb_verify_token',
            'services.facebook.page_access_token' => 'fake_page_token',
            'services.instagram.app_secret' => $this->appSecret,
            'services.instagram.webhook_verify_token' => 'ig_verify_token',
            'services.whatsapp.app_secret' => $this->appSecret,
            'services.whatsapp.webhook_verify_token' => 'wa_verify_token',
        ]);
    }

    private function signPayload(string $payload): string
    {
        return 'sha256=' . hash_hmac('sha256', $payload, $this->appSecret);
    }

    // =========================================================================
    // Facebook Webhook
    // =========================================================================

    public function test_facebook_webhook_accepts_valid_signature(): void
    {
        $payload = json_encode([
            'object' => 'page',
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => '123'],
                    'message' => ['text' => 'Merhaba'],
                ]],
            ]],
        ]);

        $response = $this->call('POST', '/api/v1/webhook/facebook', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        // Signature validation passed — should NOT be 403 (rejected).
        // 200 = full success, 500 = internal processing error (acceptable in test env without DB fixtures).
        $this->assertNotEquals(403, $response->getStatusCode(), 'Valid signature was incorrectly rejected');
    }

    public function test_facebook_webhook_rejects_missing_signature(): void
    {
        $payload = json_encode(['object' => 'page', 'entry' => []]);

        $response = $this->call('POST', '/api/v1/webhook/facebook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(403);
    }

    public function test_facebook_webhook_rejects_wrong_signature(): void
    {
        $payload = json_encode(['object' => 'page', 'entry' => []]);

        $response = $this->call('POST', '/api/v1/webhook/facebook', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalidhash1234567890abcdef',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(403);
    }

    public function test_facebook_webhook_rejects_when_secret_not_configured(): void
    {
        config(['services.facebook.app_secret' => '']);

        $payload = json_encode(['object' => 'page', 'entry' => []]);

        $response = $this->call('POST', '/api/v1/webhook/facebook', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=somehash',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(403);
    }

    public function test_facebook_verify_webhook_with_correct_token(): void
    {
        $response = $this->get('/api/v1/webhook/facebook?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'fb_verify_token',
            'hub_challenge' => 'challenge_string_123',
        ]));

        $response->assertStatus(200);
        $response->assertSee('challenge_string_123');
    }

    public function test_facebook_verify_webhook_rejects_wrong_token(): void
    {
        $response = $this->get('/api/v1/webhook/facebook?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong_token',
            'hub_challenge' => 'challenge_string',
        ]));

        $response->assertStatus(403);
    }

    // =========================================================================
    // Instagram Webhook
    // =========================================================================

    public function test_instagram_webhook_accepts_valid_signature(): void
    {
        $payload = json_encode([
            'object' => 'instagram',
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => '456'],
                    'message' => ['text' => 'Bodrum villa?'],
                ]],
            ]],
        ]);

        $response = $this->call('POST', '/api/v1/webhook/instagram', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        // Signature validation passed — should NOT be 403.
        $this->assertNotEquals(403, $response->getStatusCode(), 'Valid signature was incorrectly rejected');
    }

    public function test_instagram_webhook_rejects_missing_signature(): void
    {
        $payload = json_encode(['object' => 'instagram', 'entry' => []]);

        $response = $this->call('POST', '/api/v1/webhook/instagram', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(403);
    }

    public function test_instagram_webhook_rejects_wrong_signature(): void
    {
        $payload = json_encode(['object' => 'instagram', 'entry' => []]);

        $response = $this->call('POST', '/api/v1/webhook/instagram', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=badhash',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(403);
    }

    public function test_instagram_verify_webhook_with_correct_token(): void
    {
        $response = $this->get('/api/v1/webhook/instagram?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'ig_verify_token',
            'hub_challenge' => 'ig_challenge_456',
        ]));

        $response->assertStatus(200);
        $response->assertSee('ig_challenge_456');
    }

    // =========================================================================
    // WhatsApp Webhook
    // =========================================================================

    public function test_whatsapp_webhook_accepts_valid_signature(): void
    {
        $payload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '905551234567',
                            'text' => ['body' => 'Fiyat sormak istiyorum'],
                            'type' => 'text', // context7-ignore
                        ]],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ]);

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        // Signature validation passed — should NOT be 403.
        $this->assertNotEquals(403, $response->getStatusCode(), 'Valid signature was incorrectly rejected');
    }

    public function test_whatsapp_webhook_rejects_missing_signature(): void
    {
        $payload = json_encode(['object' => 'whatsapp_business_account', 'entry' => []]);

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(403);
    }

    public function test_whatsapp_webhook_rejects_wrong_signature(): void
    {
        $payload = json_encode(['object' => 'whatsapp_business_account', 'entry' => []]);

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=wronghash',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(403);
    }

    public function test_whatsapp_verify_webhook_with_correct_token(): void
    {
        $response = $this->get('/api/v1/webhook/whatsapp?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wa_verify_token',
            'hub_challenge' => 'wa_challenge_789',
        ]));

        $response->assertStatus(200);
        $response->assertSee('wa_challenge_789');
    }
}
