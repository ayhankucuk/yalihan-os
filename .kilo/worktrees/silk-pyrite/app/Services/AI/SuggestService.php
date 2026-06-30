<?php

namespace App\Services\AI;

use App\Services\AIService;

class SuggestService
{
    public function __construct(private AIService $ai) {}

    public function suggestFeatures(array $context): array
    {
        $result = $this->ai->suggest($context, 'feature');
        $items = $result['items'] ?? ($result['data']['items'] ?? $result['data'] ?? []);

        return [
            'success' => true,
            'data' => $items,
            'message' => 'Özellik önerileri hazır',
        ];
    }
}
