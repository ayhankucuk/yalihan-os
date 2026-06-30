<?php

namespace App\Traits;

use App\Exceptions\AgentWriteViolationException;
use App\Services\OpenClaw\OpenClawAuditService;
use App\Support\AgentContext;
use Illuminate\Support\Facades\Log;

/**
 * GuardsAgentWrites — Service-level write isolation for agent requests.
 *
 * Apply this trait to any service that performs DB writes.
 * Call $this->blockAgentWrite(__FUNCTION__) at the top of write methods.
 *
 * This is the "inner lock" — middleware is the "door".
 * Even if middleware is somehow bypassed, this guard blocks writes.
 */
trait GuardsAgentWrites
{
    /**
     * Block the current operation if the request originates from an agent.
     *
     * @throws AgentWriteViolationException
     */
    protected function blockAgentWrite(string $method): void
    {
        if (!AgentContext::isAgent()) {
            return;
        }

        $service = static::class;
        $scope = AgentContext::scope();
        $correlationId = AgentContext::correlationId();

        Log::channel(config('openclaw.audit.log_channel', 'security'))->critical('agent_write_violation', [
            'service' => $service,
            'method' => $method,
            'scope' => $scope,
            'correlation_id' => $correlationId,
            'token_hash' => AgentContext::tokenHash(),
        ]);

        // DB audit record — fire-and-forget, never breaks the request
        try {
            app(OpenClawAuditService::class)->recordWriteViolation($service, $method);
        } catch (\Throwable $e) {
            // Audit persistence failure logged by the service itself
            report($e);
        }

        throw new AgentWriteViolationException($service, $method, $scope, $correlationId);
    }
}
