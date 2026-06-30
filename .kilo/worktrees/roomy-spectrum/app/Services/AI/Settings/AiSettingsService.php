<?php

namespace App\Services\AI\Settings;

use App\Contracts\Settings\SettingsAuthorityInterface;
use App\Enums\AI\DeepSeekModel;
use InvalidArgumentException;

class AiSettingsService
{
    public function __construct(
        private readonly SettingsAuthorityInterface $authority
    ) {}

    public function updateDeepSeek(array $data): void
    {
        $model = $data['model'] ?? DeepSeekModel::CHAT->value;

        if (! in_array($model, DeepSeekModel::values(), true)) {
            throw new InvalidArgumentException('Invalid DeepSeek model.');
        }

        if (isset($data['api_key']) && !empty($data['api_key'])) {
            $this->authority->set('deepseek_api_key', $data['api_key']);
        }

        $this->authority->set('deepseek_model', $model);
    }
}
