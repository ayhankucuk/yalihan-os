<?php

namespace App\UseCases\N8n;

use App\Models\AIIlanTaslagi;
use App\UseCases\N8n\DTOs\AIIlanTaslagiDTO;
use App\Services\Logging\LogService;
use App\Enums\TaslakDurumu;
use Illuminate\Support\Facades\DB;

class ProcessAIIlanTaslagiUseCase
{
    public function handle(AIIlanTaslagiDTO $dto): AIIlanTaslagi
    {
        return DB::transaction(function () use ($dto) {
            $taslak = AIIlanTaslagi::create([
                'danisman_id' => $dto->danismanId,
                'yayin_durumu' => TaslakDurumu::TASLAK->value,
                'ai_response' => $dto->aiResponse,
                'ai_model_used' => $dto->aiModelUsed,
                'ai_prompt_version' => $dto->aiPromptVersion,
                'ai_generated_at' => now(),
            ]);

            LogService::info('n8n webhook: AI ilan taslağı kaydedildi', [
                'taslak_id' => $taslak->id,
                'danisman_id' => $dto->danismanId,
            ], LogService::CHANNEL_API);

            return $taslak;
        });
    }
}
