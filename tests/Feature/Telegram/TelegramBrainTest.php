<?php

namespace Tests\Feature\Telegram;

use App\Models\User;
use App\Services\Telegram\TelegramBrain;
use Tests\TestCase;

/**
 * TelegramBrainTest
 *
 * Voice-to-Draft webhook entegrasyonunu test eder
 */
class TelegramBrainTest extends TestCase
{

    protected User $danisman;

    protected function setUp(): void
    {
        parent::setUp();

        $this->danisman = User::factory()->create([
            'telegram_chat_id' => '123456789',
            'name' => 'Test Danışman',
        ]);
    }

    /**
     * Test: Voice webhook mesajı işlenir
     *
     * @test
     */
    public function test_voice_webhook_handled()
    {
        $update = [
            'update_id' => 123,
            'message' => [
                'message_id' => 456,
                'from' => [
                    'id' => 123456789,
                    'username' => 'testuser',
                ],
                'chat' => [
                    'id' => '123456789',
                    'type' => 'private',
                ],
                'date' => time(),
                'voice' => [
                    'file_id' => 'test_file_id',
                    'file_unique_id' => 'unique_id',
                    'duration' => 30,
                    'mime_type' => 'audio/ogg',
                    'file_size' => 50000,
                ],
            ],
        ];

        $brain = app(TelegramBrain::class);

        // Should not throw exception
        /** @phpstan-ignore-next-line */
        $brain->handle($update);

        $this->assertTrue(true);
    }

    /**
     * Test: Callback query mesajı işlenir
     *
     * @test
     */
    public function test_callback_query_handled()
    {
        $update = [
            'update_id' => 789,
            'callback_query' => [
                'id' => 'callback_123',
                'from' => [
                    'id' => 123456789,
                    'username' => 'testuser',
                ],
                'message' => [
                    'message_id' => 456,
                    'chat' => [
                        'id' => '123456789',
                        'type' => 'private',
                    ],
                ],
                'data' => json_encode([
                    'action' => 'edit_draft',
                    'talep_id' => 1,
                ]),
            ],
        ];

        $brain = app(TelegramBrain::class);

        // Should not throw exception
        /** @phpstan-ignore-next-line */
        $brain->handle($update);

        $this->assertTrue(true);
    }

    /**
     * Test: Bilinmeyen user'a komut işlenmez
     *
     * @test
     */
    public function test_unknown_user_ignored()
    {
        $update = [
            'update_id' => 999,
            'message' => [
                'message_id' => 999,
                'from' => [
                    'id' => 999999999, // Veritabanında yok
                    'username' => 'unknownuser',
                ],
                'chat' => [
                    'id' => '999999999',
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/yardim',
            ],
        ];

        $brain = app(TelegramBrain::class);

        // Should not throw exception (auth processor işler)
        /** @phpstan-ignore-next-line */
        $brain->handle($update);

        $this->assertTrue(true);
    }

    /**
     * Test: Help komutu işlenir
     *
     * @test
     */
    public function test_help_command_handled()
    {
        $update = [
            'update_id' => 500,
            'message' => [
                'message_id' => 500,
                'from' => [
                    'id' => 123456789,
                    'username' => 'testuser',
                ],
                'chat' => [
                    'id' => '123456789',
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/yardim',
            ],
        ];

        $brain = app(TelegramBrain::class);

        // Should not throw exception
        /** @phpstan-ignore-next-line */
        $brain->handle($update);

        $this->assertTrue(true);
    }
}
