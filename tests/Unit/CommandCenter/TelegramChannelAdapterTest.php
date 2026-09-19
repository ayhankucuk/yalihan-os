<?php

namespace Tests\Unit\CommandCenter;

use App\Services\CommandCenter\Adapters\TelegramChannelAdapter;
use Tests\TestCase;

class TelegramChannelAdapterTest extends TestCase
{
    public function test_it_normalizes_telegram_message_update_correctly()
    {
        $adapter = new TelegramChannelAdapter;

        $update = [
            'update_id' => 12345678,
            'message' => [
                'message_id' => 99,
                'from' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'Ayhan',
                ],
                'chat' => [
                    'id' => 987654321,
                    'type' => 'private',
                ],
                'date' => 1700000000,
                'text' => 'Elimizde €1M\'a ne var?',
            ],
        ];

        $normalized = $adapter->normalize($update);

        $this->assertNotNull($normalized);
        $this->assertEquals('telegram', $normalized->channel);
        $this->assertEquals('987654321', $normalized->externalActorId);
        $this->assertEquals('987654321', $normalized->chatId);
        $this->assertEquals('Elimizde €1M\'a ne var?', $normalized->rawText);
    }
}
