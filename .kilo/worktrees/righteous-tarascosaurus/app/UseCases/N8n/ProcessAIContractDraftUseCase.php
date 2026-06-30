<?php

namespace App\UseCases\N8n;

use App\Models\AIContractDraft;
use App\UseCases\N8n\DTOs\AIContractDraftDTO;
use App\Services\Logging\LogService;
use App\Enums\TaslakDurumu;
use Illuminate\Support\Facades\DB;

class ProcessAIContractDraftUseCase
{
    public function handle(AIContractDraftDTO $dto): AIContractDraft
    {
        return DB::transaction(function () use ($dto) {
            $draft = AIContractDraft::create([
                'contract_type' => $dto->contractType,
                'property_id' => $dto->propertyId,
                'kisi_id' => $dto->kisiId,
                'yayin_durumu' => TaslakDurumu::TASLAK->value,
                'content' => $dto->content,
                'ai_model_used' => $dto->aiModelUsed,
                'ai_generated_at' => now(),
            ]);

            LogService::info('n8n webhook: AI sözleşme taslağı kaydedildi', [
                'draft_id' => $draft->id,
                'contract_type' => $dto->contractType,
            ], LogService::CHANNEL_API);

            return $draft;
        });
    }
}
