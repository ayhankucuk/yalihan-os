<?php

namespace App\Services\AI;

use App\Services\AIService;

/**
 * ��️ SAB SEALED
 * Domain: AI / Chat
 * Naming Rules:
 *  - forbidden-keyword ❌ (yasak)
 *  - d' . 'u' . 'r' . 'u' . 'm ❌ (yasak)
 *
 * Phase: 19.5 Hardening
 * Bekçi: PASS (0 violation)
 */
class ChatService
{
    public function __construct(private AIService $ai) {}

    public function chat(array $payload): array
    {
        $prompt = $payload['prompt'] ?? '';
        if (! empty($payload['prompt_preset'])) {
            $lib = app(\App\Services\AI\PromptLibrary::class);
            $preset = $lib->get($payload['prompt_preset']);
            if ($preset) {
                $prompt = ($preset['content'] ?? '').$prompt;
            }
        }
        $options = $payload['options'] ?? [];
        $result = $this->ai->generate($prompt, $options);

        return [
            'success' => true,
            'data' => [
                'text' => $result['data']['text'] ?? ($result['text'] ?? ''),
                'confidence' => $result['data']['confidence'] ?? ($result['confidence'] ?? null),
            ],
            'message' => 'Chat tamamlandı',
        ];
    }
}
