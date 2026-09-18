<?php

namespace App\UseCases\N8n;

use App\Models\AI\AIContractDraft;
use App\UseCases\N8n\DTOs\AIContractDraftDTO;
use App\Services\Logging\LogService;
use App\Services\N8n\CountryOwnershipResolver;
use App\Services\N8n\TenantOwnershipResolver;
use App\Enums\TaslakDurumu;
use Illuminate\Support\Facades\DB;

class ProcessAIContractDraftUseCase
{
    public function __construct(
        private readonly CountryOwnershipResolver $countryResolver,
        private readonly TenantOwnershipResolver $tenantResolver
    ) {}

    public function handle(AIContractDraftDTO $dto): AIContractDraft
    {
        // Resolve canonical country ownership.
        // Ilan(property_id).ulke_id or Kisi(kisi_id).ulke_id.
        // Throws CountryOwnershipUnresolvableException if neither resolves.
        $ulkeId = $this->countryResolver->resolveForSozlesmeTaslagi(
            $dto->propertyId,
            $dto->kisiId
        );
        $tenantId = $this->tenantResolver->resolveForSozlesmeTaslagi(
            $dto->propertyId,
            $dto->kisiId
        );

        return DB::transaction(function () use ($dto, $ulkeId, $tenantId) {
            $draft = AIContractDraft::create([
                'ulke_id' => $ulkeId,
                'tenant_id' => $tenantId,
                'contract_type' => $dto->contractType,
                'property_id' => $dto->propertyId,
                'ilan_id' => $dto->propertyId,
                'kisi_id' => $dto->kisiId,
                'yayin_durumu' => TaslakDurumu::TASLAK->value,
                'content' => $dto->content,
                'draft_content' => $dto->content,
                'ai_model_used' => $dto->aiModelUsed,
                'ai_generated_at' => now(),
            ]);

            LogService::info('n8n webhook: AI sözleşme taslağı kaydedildi', [
                'draft_id' => $draft->id,
                'contract_type' => $dto->contractType,
                'ulke_id' => $ulkeId,
                'tenant_id' => $tenantId,
            ], LogService::CHANNEL_API);

            return $draft;
        });
    }
}
