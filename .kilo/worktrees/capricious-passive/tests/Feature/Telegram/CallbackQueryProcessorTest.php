<?php

namespace Tests\Feature\Telegram;

use App\Models\User;
use App\Services\Telegram\Processors\CallbackQueryProcessor;
use App\Services\Telegram\AlertService;
use Tests\TestCase;

/**
 * CallbackQueryProcessorTest
 *
 * Telegram inline button callbacks'ini test eder
 */
class CallbackQueryProcessorTest extends TestCase
{

    protected CallbackQueryProcessor $processor;
    protected User $danisman;

    protected function setUp(): void
    {
        parent::setUp();

        $this->danisman = User::factory()->create([
            'telegram_chat_id' => '123456789',
        ]);

        $alertService = $this->createMock(AlertService::class);
        $this->processor = new CallbackQueryProcessor($alertService);
    }

    /**
     * Test: Edit draft callback işlenir
     *
     * @test
     */
    public function test_edit_draft_callback_processed()
    {
        $callbackQuery = [
            'id' => 'callback_123',
            'from' => ['id' => 123456789],
            'message' => [
                'chat' => ['id' => 987654321],
                'message_id' => 123,
            ],
            'data' => json_encode([
                'action' => 'edit_draft',
                'talep_id' => 1,
                'type' => 'talep',
            ]),
        ];

        // Should not throw exception
        $this->processor->process($callbackQuery, $this->danisman);

        $this->assertTrue(true);
    }

    /**
     * Test: Publish callback oluşturulur
     *
     * @test
     */
    public function test_publish_callback_processed()
    {
        // Talep oluştur
        $talep = \App\Models\Talep::factory()->create([
            'talep_durumu' => 'Beklemede', // Context7: talep_durumu (TalepDurumu enum)
        ]);

        $callbackQuery = [
            'id' => 'callback_456',
            'from' => ['id' => 123456789],
            'message' => [
                'chat' => ['id' => 987654321],
                'message_id' => 456,
            ],
            'data' => json_encode([
                'action' => 'publish',
                'talep_id' => $talep->id,
            ]),
        ];

        $this->processor->process($callbackQuery, $this->danisman);

        // Assert: Talep aktif edildi mi?
        $talep->refresh();
        $this->assertEquals('Aktif', $talep->talep_durumu->value);
    }

    /**
     * Test: TKGM fill callback oluşturulur
     *
     * @test
     */
    public function test_tkgm_fill_callback_processed()
    {
        $talep = \App\Models\Talep::factory()->create();

        $callbackQuery = [
            'id' => 'callback_789',
            'from' => ['id' => 123456789],
            'message' => [
                'chat' => ['id' => 987654321],
                'message_id' => 789,
            ],
            'data' => json_encode([
                'action' => 'tkgm_fill',
                'talep_id' => $talep->id,
            ]),
        ];

        // Should not throw exception
        $this->processor->process($callbackQuery, $this->danisman);

        $this->assertTrue(true);
    }

    /**
     * Test: Bilinmeyen callback handle edilir
     *
     * @test
     */
    public function test_unknown_callback_handled()
    {
        $callbackQuery = [
            'id' => 'callback_unknown',
            'from' => ['id' => 123456789],
            'message' => [
                'chat' => ['id' => 987654321],
                'message_id' => 999,
            ],
            'data' => json_encode([
                'action' => 'unknown_action',
                'talep_id' => 1,
            ]),
        ];

        // Should not throw exception
        $this->processor->process($callbackQuery, $this->danisman);

        $this->assertTrue(true);
    }
}
