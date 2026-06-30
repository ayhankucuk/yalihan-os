<?php

namespace App\UseCases\N8n;

use App\Models\AIMessage;
use App\UseCases\N8n\DTOs\AIMesajTaslagiDTO;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\DB;

class ProcessAIMesajTaslagiUseCase
{
    public function handle(AIMesajTaslagiDTO $dto): AIMessage
    {
        return DB::transaction(function () use ($dto) {
            $message = AIMessage::create([
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
            ], LogService::CHANNEL_API);

            return $message;
        });
    }
}
