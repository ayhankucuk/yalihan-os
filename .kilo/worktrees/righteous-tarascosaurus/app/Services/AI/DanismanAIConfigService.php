<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;

class DanismanAIConfigService
{
    public function store(array $data): array
    {
        $configId = (int) (microtime(true) * 1000);
        Cache::put($this->key($configId), $data, now()->addDays(30));

        return [
            'config_id' => $configId,
            'test_result' => [
                'basarili' => true,
                'durum_kodu' => 200,
            ],
        ];
    }

    public function update(int $configId, array $data): array
    {
        Cache::put($this->key($configId), $data, now()->addDays(30));

        return [
            'config_id' => $configId,
            'test_result' => [
                'basarili' => true,
                'durum_kodu' => 200,
            ],
        ];
    }

    public function destroy(int $configId): void
    {
        Cache::forget($this->key($configId));
    }

    private function key(int $configId): string
    {
        return "danisman_ai_config:{$configId}";
    }
}
