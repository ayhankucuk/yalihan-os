<?php

declare(strict_types=1);

namespace App\Services\CommandCenter;

use App\DTOs\Command\NormalizedCommandInput;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Modules\TakimYonetimi\Services\TelegramBotService;
use App\Services\CommandCenter\Routing\IntentRouter;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * CommandGateway
 *
 * Context7 Standard: C7-COMMAND-GATEWAY-2026-09-19
 *
 * YALIHAN Command Center merkezi komut ve niyet işleme gateway'i.
 */
class CommandGateway
{
    public function __construct(
        private IntentRouter $intentRouter,
        private TelegramBotService $telegramBotService,
        private TenantContextService $tenantContextService
    ) {}

    /**
     * Kanal bağımsız komut girdisini işle
     */
    public function process(NormalizedCommandInput $input): string
    {
        Log::info('CommandGateway: Komut alındı', [
            'channel' => $input->channel,
            'actor_id' => $input->externalActorId,
            'chat_id' => $input->chatId,
            'raw_text' => $input->rawText,
        ]);

        // 1. Kimlik ve yetki doğrulama
        $user = $this->resolveActor($input);

        if (! $user && $input->channel === 'telegram') {
            $unauthorizedResponse = "🔒 *Yetkisiz Erişim*\n\n".
                                    "Yalıhan Command Center sistemine erişim yetkiniz bulunmamaktadır.\n".
                                    'Lütfen sistem yöneticiniz ile iletişime geçin.';

            $this->sendOutboundResponse($input, $unauthorizedResponse);

            return $unauthorizedResponse;
        }

        // 2. Tenant bağlamını kur — request lifecycle boyunca korunur, finally ile temizlenir
        $this->establishTenantContext($user);

        try {
            // 3. Intent Routing & Execution
            $response = $this->intentRouter->route($input);
        } finally {
            // ⚠️ Context cleanup: singleton state sızıntısını önler (long-running worker/runtime)
            $this->tenantContextService->clearTenant();
        }

        // 4. Outbound Response Dispatch
        $this->sendOutboundResponse($input, $response);

        return $response;
    }

    /**
     * Aktör kimliğini çözümle
     */
    private function resolveActor(NormalizedCommandInput $input): ?User
    {
        if ($input->channel === 'telegram') {
            return User::where('telegram_chat_id', $input->externalActorId)
                ->orWhere('id', (int) $input->externalActorId)
                ->first();
        }

        return null;
    }

    /**
     * Tenant bağlamını kur — fail-closed, mevcut SetTenantContext mekanizmasıyla aynı çalışır
     */
    private function establishTenantContext(User $user): void
    {
        if (empty($user->tenant_id)) {
            Log::channel('governance_security')->critical('TENANT_RUNTIME_PROPAGATION_FAIL: tenant_id eksik', [
                'user_id' => $user->id,
                'channel' => 'telegram',
            ]);
            throw new RuntimeException('Tenant context kurulamadı: tenant_id eksik. Erişim reddedildi.');
        }

        // Canonical tenant resolution — SetTenantContext middleware ile aynı mekanizma
        // Cache: 5 dakika (300s), long-running worker'lar için memory-safe
        $tenant = Cache::remember(
            "tenant:{$user->tenant_id}",
            300,
            fn() => Tenant::find($user->tenant_id)
        );

        if (! $tenant) {
            Log::channel('governance_security')->critical('TENANT_RUNTIME_PROPAGATION_FAIL: Tenant bulunamadı', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
            ]);
            throw new RuntimeException('Tenant context kurulamadı: geçersiz tenant_id. Erişim reddedildi.');
        }

        $this->tenantContextService->setTenant($tenant);

        Log::info('CommandGateway: Tenant bağlamı kuruldu', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
        ]);
    }

    /**
     * Yanıtı ilgili kanala gönder
     */
    private function sendOutboundResponse(NormalizedCommandInput $input, string $responseText): void
    {
        if ($input->channel === 'telegram' && ! empty($input->chatId)) {
            try {
                $this->telegramBotService->sendMessage(
                    (int) $input->chatId,
                    $responseText,
                    ['parse_mode' => 'Markdown']
                );
            } catch (\Exception $e) {
                Log::error('CommandGateway: Telegram yanıt gönderme hatası', [
                    'chat_id' => $input->chatId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
