<?php

namespace App\UseCases\N8n;

use App\Models\AI\AIMessage;
use App\UseCases\N8n\DTOs\AIMesajTaslagiDTO;
use App\Services\Logging\LogService;
use App\Services\N8n\CountryOwnershipResolver;
use App\Services\N8n\TenantOwnershipResolver;
use Illuminate\Support\Facades\DB;

class ProcessAIMesajTaslagiUseCase
{
    public function __construct(
        private readonly CountryOwnershipResolver $countryResolver,
        private readonly TenantOwnershipResolver $tenantResolver
    ) {}

    public function handle(AIMesajTaslagiDTO $dto): AIMessage
    {
        // Resolve country via polymorphic chain:
        // Communication → communicable (Ilan|Kisi|User) → ulke_id
        // Throws CountryOwnershipUnresolvableException if unresolvable.
        $ulkeId = $this->countryResolver->resolveForMesajTaslagi($dto->communicationId);
        $tenantId = $this->tenantResolver->resolveForMesajTaslagi($dto->communicationId);

        return DB::transaction(function () use ($dto, $ulkeId, $tenantId) {
            $message = AIMessage::create([
                'ulke_id' => $ulkeId,
                'tenant_id' => $tenantId,
                'communication_id' => $dto->communicationId,
                'channel' => $dto->channel,
                'role' => 'assistant',
                'content' => $dto->content,
                'mesaj_durumu' => 'draft',
                'ai_model_used' => $dto->aiModelUsed,
                'ai_generated_at' => now(),
            ]);

            LogService::info('n8n webhook: AI mesaj taslağı kaydedildi', [
                'message_id' => $message->id,
                'communication_id' => $dto->communicationId,
                'ulke_id' => $ulkeId,
                'tenant_id' => $tenantId,
            ], LogService::CHANNEL_API);

            return $message;
        });
    }
}
