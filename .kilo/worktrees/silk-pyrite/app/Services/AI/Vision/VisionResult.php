<?php

namespace App\Services\AI\Vision;

/**
 * Value object for Vision Analysis Result
 */
class VisionResult
{
    public function __construct(
        public bool $success,
        public array $suggestions = [],
        public ?string $errorMessage = null,
        public array $metadata = [] // [provider, model, latency_ms, cost_estimate]
    ) {}

    public static function success(array $suggestions, array $metadata = []): self
    {
        return new self(true, $suggestions, null, $metadata);
    }

    public static function failure(string $message, array $metadata = []): self
    {
        return new self(false, [], $message, $metadata);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'suggestions' => $this->suggestions,
            'error' => $this->errorMessage,
            'metadata' => $this->metadata,
        ];
    }
}
