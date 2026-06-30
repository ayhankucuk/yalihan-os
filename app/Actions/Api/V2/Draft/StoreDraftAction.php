<?php

namespace App\Actions\Api\V2\Draft;

use App\Models\V2\AiIlanTaslagi;

class StoreDraftAction
{
    public function handle(array $data): AiIlanTaslagi
    {
        return AiIlanTaslagi::create([
            'kullanici_id' => auth('sanctum')->id(),
            'ai_response' => $data['ai_response'],
            'ai_model_used' => $data['ai_model_used'],
            'ai_prompt_version' => $data['ai_prompt_version'],
            'ai_generated_at' => now(),
            'taslak_durumu' => 'Onay Bekliyor',
            'metadata' => $data['metadata'] ?? null,
        ]);
    }
}
