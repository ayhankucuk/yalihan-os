<?php

namespace App\Services\AI\Copilot;

class CopilotDecisionResolver
{
    public function resolve(array $output): string
    {
        return match ($output['decision']['action']) {
            'proceed' => 'allow',
            'proceed_with_caution' => 'warn',
            'block' => 'block',
            default => 'block',
        };
    }
}
