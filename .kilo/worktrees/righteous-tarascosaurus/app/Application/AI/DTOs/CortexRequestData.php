<?php

namespace App\Application\AI\DTOs;

use App\Domain\AI\Enums\AITaskType;

final class CortexRequestData
{
    public function __construct(
        public readonly AITaskType $taskType,
        public readonly array $input,
        public readonly \App\Application\Shared\DTOs\TenantContext $tenantContext,
        public readonly array $context = [],
        public readonly ?string $model = null,
        public readonly array $meta = [],
    ) {}

    public function getMessages(): array
    {
        return $this->input['messages'] ?? [['role' => 'user', 'content' => $this->input['prompt'] ?? '']];
    }

    public function getFeatureKey(): string
    {
        return $this->meta['feature_key'] ?? $this->taskType->value;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getTemperature(): ?float
    {
        return $this->meta['temperature'] ?? null;
    }

    public function getUserId(): int
    {
        return $this->tenantContext->userId;
    }

    public function getTenantId(): int
    {
        return $this->tenantContext->tenantId;
    }

    public function getTraceId(): string
    {
        return $this->tenantContext->requestId;
    }

    public function getCapability(): \App\Domain\AI\Enums\CortexCapability
    {
        // Map TaskType to Capability if needed, or return a default
        return \App\Domain\AI\Enums\CortexCapability::TEXT_GENERATION;
    }

    public function getOption(string $key, $default = null)
    {
        return $this->meta[$key] ?? $default;
    }
}
