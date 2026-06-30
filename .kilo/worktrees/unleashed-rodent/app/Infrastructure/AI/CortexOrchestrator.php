<?php

namespace App\Infrastructure\AI;

use App\Application\AI\DTOs\CortexRequestData;
use App\Application\AI\DTOs\CortexResponseData;
use App\Application\AI\Routing\CortexRoutingService;
use App\Domain\AI\Contracts\CortexServiceInterface;
use App\Domain\AI\Enums\CortexCapability;

/**
 * 🛡️ Cortex Orchestrator
 * 
 * The primary implementation of CortexServiceInterface that uses 
 * the Intelligent Routing Engine to execute tasks.
 */
final class CortexOrchestrator implements CortexServiceInterface
{
    public function __construct(
        private readonly \App\Application\AI\Support\RoutedCortexExecutor $executor
    ) {}

    public function execute(CortexRequestData $request): CortexResponseData
    {
        return $this->executor->execute($request);
    }

    public function supports(CortexCapability $capability): bool
    {
        // Orchestrator supports everything that any of its adapters support
        return true; 
    }

    public function providerName(): string
    {
        return 'cortex-orchestrator';
    }
}
