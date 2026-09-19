<?php

namespace Tests\Unit\CommandCenter;

use App\DTOs\Command\NormalizedCommandInput;
use App\Modules\TakimYonetimi\Services\TelegramBotService;
use App\Services\CommandCenter\CommandGateway;
use App\Services\CommandCenter\Routing\IntentRouter;
use App\Services\SaaS\TenantContextService;
use Tests\TestCase;

class CommandGatewayTest extends TestCase
{
    public function test_it_returns_unauthorized_if_actor_not_registered()
    {
        $intentRouterMock = \Mockery::mock(IntentRouter::class);
        $telegramBotMock = \Mockery::mock(TelegramBotService::class);
        $telegramBotMock->shouldReceive('sendMessage')->once()->andReturn(true);

        $tenantContextMock = \Mockery::mock(TenantContextService::class);

        $gateway = new CommandGateway(
            $intentRouterMock,
            $telegramBotMock,
            $tenantContextMock
        );

        $input = new NormalizedCommandInput(
            channel: 'telegram',
            externalActorId: '99999999',
            chatId: '99999999',
            rawText: 'Elimizde €1M\'a ne var?'
        );

        $response = $gateway->process($input);

        $this->assertStringContainsString('Yetkisiz Erişim', $response);
    }
}
