<?php

namespace App\Services\SaaS;

use App\Services\AI\OllamaService;
use RuntimeException;

/**
 * AiMonetizationService (The Orthodox Orchestrator)
 * 
 * Purpose: Coordinates AI usage with financial and entitlement enforcement.
 * Adheres to the 'Safe Boundary' and 'Failure Contract' patterns.
 */
class AiMonetizationService
{
    public function __construct(
        protected TenantContextService $tenantContext,
        protected EntitlementService $entitlements,
        protected UsageMeteringService $usageMetering,
        protected BillingLedgerService $ledger,
        protected OllamaService $ollama
    ) {}

    /**
     * Orchestrates an AI generation request with monetization enforcement.
     */
    public function generate(string $prompt, array $options = []): array
    {
        // 1. Establish Authority
        $tenant = $this->tenantContext->getTenant();

        // 2. 🛡️ GATE: Entitlement Enforcement (Assertion)
        // Failure Contract: Entitlement denied -> Ollama NOT called.
        $this->entitlements->assertAllowed('ai_usage');

        // 3. 🤖 ACTION: Pure Provider Call (Isolated)
        // Adheres to 'Safe Boundary': OllamaService is just a provider.
        $response = $this->ollama->generateCompletion($prompt);

        // Failure Contract: Ollama fail -> usage NOT recorded.
        if (isset($response['error']) && $response['error']) {
            return $response;
        }

        // 4. 📊 METERING: Record Usage Event
        $usageEvent = $this->usageMetering->recordAiUsage(
            $tenant,
            'ollama',
            ['prompt' => $prompt],
            $response
        );

        // 5. 💰 LEDGER: Append-only Financial Recording
        // Failure Contract: Usage fail -> ledger NOT recorded.
        $this->ledger->appendUsageCharge($tenant, $usageEvent);

        return $response;
    }
}
