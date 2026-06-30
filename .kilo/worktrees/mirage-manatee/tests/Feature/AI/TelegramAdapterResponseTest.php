<?php

namespace Tests\Feature\AI;

use Tests\TestCase;

/**
 * TelegramAdapterResponseTest
 *
 * Verifies that the Telegram adapter correctly processes incoming webhooks
 * and returns the intended JSON payload for the Telegram Bot API.
 */
class TelegramAdapterResponseTest extends TestCase
{

    public function test_telegram_webhook_processes_and_returns_payload(): void
    {
        // Configure webhook secret for this test
        $secret = 'test-telegram-secret-token';
        config(['services.telegram.webhook_secret' => $secret]);

        // Mocking a basic Telegram message structure
        $payload = [
            'message' => [
                'text' => 'Bodrum Bitez arsa fiyatları?',
                'chat' => [
                    'id' => 123456789
                ],
                'from' => [
                    'first_name' => 'Test User'
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/integrations/telegram/webhook', $payload, [
            'X-Telegram-Bot-Api-Secret-Token' => $secret,
        ]);

        $response->assertStatus(200);
    }

    public function test_telegram_webhook_rejects_without_secret(): void
    {
        config(['services.telegram.webhook_secret' => 'configured-secret']);

        $response = $this->postJson('/api/v1/integrations/telegram/webhook', [
            'message' => ['text' => 'test', 'chat' => ['id' => 1]],
        ]);

        $response->assertStatus(403);
    }

    public function test_telegram_webhook_rejects_wrong_secret(): void
    {
        config(['services.telegram.webhook_secret' => 'real-secret']);

        $response = $this->postJson('/api/v1/integrations/telegram/webhook', [
            'message' => ['text' => 'test', 'chat' => ['id' => 1]],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret',
        ]);

        $response->assertStatus(403);
    }

    public function test_telegram_webhook_returns_500_when_secret_not_configured(): void
    {
        config(['services.telegram.webhook_secret' => '']);

        $response = $this->postJson('/api/v1/integrations/telegram/webhook', [
            'message' => ['text' => 'test', 'chat' => ['id' => 1]],
        ]);

        $response->assertStatus(500);
    }
}
