<?php

namespace App\Domain\Workforce;

use App\Domain\Workforce\Contracts\WorkforceAgentContract;
use App\Domain\Workforce\DTO\WorkforceContext;
use App\Domain\Workforce\DTO\WorkforceResult;
use App\Enums\AgentStatus;
use App\Enums\AgentType;
use App\Services\AI\YalihanCortex;

/**
 * BaseWorkforceAgent — Tüm ajanların extend ettiği base sınıf.
 *
 * Sprint 7.2 — AI Workforce Foundation
 *
 * Ortak işleri burada yapar:
 * - Telemetry/logging
 * - Hata yakalama
 * - Replay-safe context
 */
abstract class BaseWorkforceAgent implements WorkforceAgentContract
{
    protected YalihanCortex $cortex;

    public function __construct(YalihanCortex $cortex)
    {
        $this->cortex = $cortex;
    }

    /** @inheritDoc */
    public function getType(): AgentType
    {
        return static::AGENT_TYPE;
    }

    /** @inheritDoc */
    public function canHandle(WorkforceContext $context): bool
    {
        // Workspace gerekiyorsa kontrol et
        if ($this->requiresWorkspace() && $context->workspace === null) {
            return false;
        }
        return true;
    }

    /** @inheritDoc */
    public function handle(WorkforceContext $context): WorkforceResult
    {
        $baslat = microtime(true);

        try {
            // Ön koşul kontrolü
            if (!$this->canHandle($context)) {
                return WorkforceResult::failure(
                    agent: $this->getType(),
                    error: 'Ön koşul sağlanmadı: ' . $this->description(),
                    payload: ['context_has_workspace' => $context->workspace !== null],
                );
            }

            // Asıl iş
            $result = $this->execute($context);

            // Metadata ekle
            $result = $result->mergePayload([
                '_agent' => $this->getType()->value,
                '_executed_at' => now()->toIso8601String(),
            ]);

            // Metadata: latency
            $latency = round((microtime(true) - $baslat) * 1000, 2);
            $result = new WorkforceResult(
                status: $result->status,
                agent: $result->agent,
                payload: $result->payload,
                metadata: array_merge($result->metadata, ['latency_ms' => $latency]),
                errors: $result->errors,
                warnings: $result->warnings,
            );

            return $result;

        } catch (\Throwable $e) {
            $latency = round((microtime(true) - $baslat) * 1000, 2);
            return WorkforceResult::failure(
                agent: $this->getType(),
                error: $e->getMessage(),
                payload: [
                    '_exception' => true,
                    '_latency_ms' => $latency,
                ],
            );
        }
    }

    /**
     * Asıl iş yapan soyut metod — her ajan override eder.
     */
    abstract protected function execute(WorkforceContext $context): WorkforceResult;

    /**
     * Bu ajan workspace gerektiriyor mu?
     */
    protected function requiresWorkspace(): bool
    {
        return true;
    }

    /**
     * Kısa log satırı
     */
    protected function log(string $message, array $context = []): void
    {
        $agent = $this->getType()->value;
        \Illuminate\Support\Facades\Log::channel('workforce')->info("[{$agent}] {$message}", $context);
    }
}
