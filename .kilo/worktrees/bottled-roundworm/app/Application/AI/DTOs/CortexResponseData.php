<?php

namespace App\Application\AI\DTOs;

final class CortexResponseData
{
    public function __construct(
        public readonly bool $success,
        public readonly array $output = [],
        public readonly ?string $rawText = null,
        public readonly ?CortexUsage $usage = null,
        public readonly ?string $provider = null,
        public readonly ?string $model = null,
        public readonly ?string $traceId = null,
        public readonly array $meta = [],
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public function failed(): bool
    {
        return !$this->success;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->output,
            'trace_id' => $this->traceId,
            'provider' => $this->provider,
            'model' => $this->model,
            'error' => $this->failed() ? [
                'code' => $this->errorCode,
                'message' => $this->errorMessage,
            ] : null,
            'meta' => $this->meta,
        ];
    }
}
