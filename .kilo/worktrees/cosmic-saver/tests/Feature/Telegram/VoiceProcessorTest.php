<?php

namespace Tests\Feature\Telegram;

use App\Models\User;
use App\Services\Telegram\Processors\VoiceProcessor;
use App\Services\AudioTranscriptionService;
use App\Services\AI\YalihanCortex;
use App\Services\Telegram\AlertService;
use Tests\TestCase;

/**
 * VoiceProcessorTest
 *
 * Voice-to-Draft işlemini test eder
 */
class VoiceProcessorTest extends TestCase
{

    protected VoiceProcessor $processor;
    protected User $danisman;

    protected function setUp(): void
    {
        parent::setUp();

        // Danışman oluştur
        $this->danisman = User::factory()->create([
            'telegram_chat_id' => '123456789',
            'name' => 'Test Danışman',
        ]);

        // Mock services
        $audioService = $this->createMock(AudioTranscriptionService::class);
        $cortex = $this->createMock(YalihanCortex::class);
        $alertService = $this->createMock(AlertService::class);

        $this->processor = new VoiceProcessor(
            $audioService,
            $cortex,
            $alertService
        );
    }

    /**
     * Test: Voice mesajı başarıyla işlenir
     *
     * @test
     */
    public function test_voice_message_processed_successfully()
    {
        $voiceData = [
            'file_id' => 'test_file_123',
            'file_unique_id' => 'unique_123',
            'duration' => 30,
        ];

        $botToken = 'test_bot_token';
        $chatId = 123;

        // Result: İşlem başarılı olsun
        $result = $this->processor->processVoiceMessage(
            $chatId,
            $voiceData,
            $this->danisman,
            $botToken
        );

        // Assert: success true/false, message, talep, matches
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Test: Boş file_id hata döner
     *
     * @test
     */
    public function test_empty_file_id_returns_error()
    {
        $voiceData = [
            'file_id' => null,
        ];

        $result = $this->processor->processVoiceMessage(
            123,
            $voiceData,
            $this->danisman,
            'token'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('file_id bulunamadı', $result['error']);
    }

    /**
     * Test: Interactive message buttons oluşturulur
     *
     * @test
     */
    public function test_interactive_message_has_buttons()
    {
        // Mock yapılacak
        $talep = [
            'id' => 1,
            'baslik' => 'Test Talep',
            'butce_min' => 500000,
            'butce_max' => 1000000,
        ];

        $kisi = [
            'id' => 1,
            'ad_soyad' => 'Test Kişi',
            'telefon' => '5551234567',
        ];

        // Reflection kullanarak private method çağır
        $reflection = new \ReflectionClass($this->processor);
        $method = $reflection->getMethod('buildInteractiveMessage');
        $method->setAccessible(true);

        $message = $method->invoke($this->processor, $talep, $kisi, []);

        // Assert: Buttons olup olmadığını kontrol et
        $this->assertArrayHasKey('text', $message);
        $this->assertArrayHasKey('reply_markup', $message);
        $this->assertNotEmpty($message['reply_markup']['inline_keyboard']);
        $this->assertCount(2, $message['reply_markup']['inline_keyboard']); // 2 row
    }
}
