<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTO\AIRequest;
use App\Services\AI\DTO\AIResponse;

/**
 * 🛡️ SAB SEALED
 * AI Provider SSOT Contract.
 */
interface AIProviderInterface
{
    public function complete(AIRequest $request): AIResponse;
}
