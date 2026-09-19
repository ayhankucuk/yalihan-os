<?php

namespace Tests\Feature\CommandCenter;

use App\Models\User;
use App\Modules\TakimYonetimi\Services\TelegramBotService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TelegramIngressE2ETest extends TestCase
{
    public function test_telegram_ingress_executes_property_search_vertical()
    {
        // Mock TelegramBotService outbound dispatch
        $telegramMock = \Mockery::mock(TelegramBotService::class);
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text, $options) {
                return $chatId === 987654321
                    && str_contains($text, 'Yalıhan Portföy Arama Sonuçları');
            })
            ->andReturn(true);

        $this->app->instance(TelegramBotService::class, $telegramMock);

        // Seed or create user with telegram_chat_id
        $user = User::factory()->create([
            'telegram_chat_id' => '987654321',
        ]);

        // Get the default tenant from TestCase::injectDefaultTenantContext()
        // so the listing's tenant_id matches the tenant-isolated search context.
        $tenant = \App\Models\SaaS\Tenant::firstOrFail();

        // Insert test listing under max price — tenant_id required by Phase 1k tenant isolation.
        DB::table('ilanlar')->insert([
            'baslik' => 'Villa Betül Bodrum',
            'slug' => 'villa-betul-bodrum',
            'fiyat' => 850000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
            'tenant_id' => $tenant->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'update_id' => 99991,
            'message' => [
                'message_id' => 101,
                'from' => [
                    'id' => 987654321,
                    'first_name' => 'Ayhan',
                ],
                'chat' => [
                    'id' => 987654321,
                ],
                'text' => 'Elimizde €1M\'a ne var?',
            ],
        ];

        $response = $this->postJson(route('api.telegram.webhook.native'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }
}
