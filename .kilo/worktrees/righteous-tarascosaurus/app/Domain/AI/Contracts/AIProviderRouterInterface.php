<?php

namespace App\Domain\AI\Contracts;

use App\Application\AI\DTOs\CortexRequestData;
use App\Domain\AI\ValueObjects\RoutingDecision;

/**
 * 🛡️ AIProviderRouterInterface
 * Contract for the primary decision layer of the AI infrastructure.
 */
interface AIProviderRouterInterface
{
    /**
     * Analyze a request and decide which provider(s) should handle it.
     */
    public function decide(CortexRequestData $request): RoutingDecision;
}
