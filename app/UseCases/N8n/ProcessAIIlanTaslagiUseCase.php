<?php

namespace App\UseCases\N8n;

use App\Models\AI\AIIlanTaslagi;
use App\UseCases\N8n\DTOs\AIIlanTaslagiDTO;
use App\Services\Logging\LogService;
use App\Services\N8n\CountryOwnershipResolver;
use App\Enums\TaslakDurumu;
use Illuminate\Support\Facades\DB;

class ProcessAIIlanTaslagiUseCase
{
    public function __construct(
        private readonly CountryOwnershipResolver $countryResolver
    ) {}

    public function handle(AIIlanTaslagiDTO $dto): AIIlanTaslagi
    {
        // Resolve canonical country ownership BEFORE persisting.
        // HasCountryScope::creating() is bypassed here because n8n routes
        // have no Auth context. CountryOwnershipResolver resolves it via
        // User(danisman_id).ulke_id or Ilan(ilan_id).ulke_id.
        $ulkeId = $this->countryResolver->resolveForIlanTaslagi(
            $dto->danismanId,
            $dto->ilanId
        );

        return DB::transaction(function () use ($dto, $ulkeId) {
            $taslak = AIIlanTaslagi::create([
                'ulke_id' => $ulkeId,
                'danisman_id' => $dto->danismanId,
                'ilan_id' => $dto->ilanId,
                'yayin_durumu' => TaslakDurumu::TASLAK->value,
                'ai_response' => $dto->aiResponse,
                'ai_model_used' => $dto->aiModelUsed,
                'ai_prompt_version' => $dto->aiPromptVersion,
                'ai_generated_at' => now(),
            ]);

            LogService::info('n8n webhook: AI ilan taslağı kaydedildi', [
                'taslak_id' => $taslak->id,
                'danisman_id' => $dto->danismanId,
                'ulke_id' => $ulkeId,
            ], LogService::CHANNEL_API);

            return $taslak;
        });
    }
}
