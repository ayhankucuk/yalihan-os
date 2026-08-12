<?php

namespace App\Services\Ydl;

/**
 * BlockerEvaluationResult — Value object for blocker evaluation output.
 *
 * YDL v1 Phase 1
 */
final class BlockerEvaluationResult
{
    public function __construct(
        public readonly bool   $hasActiveBlockers,
        public readonly bool   $blocksAllDevelopment,
        public readonly bool   $allowsParallelWork,
        public readonly bool   $blocksProductionGates,
        public readonly array  $securityBlockers,
        public readonly array  $internalBlockers,
        public readonly array  $externalBlockers,
        public readonly array  $infrastructureBlockers,
        public readonly string $recommendation,
    ) {}

    public function hasSecurityBlockers(): bool
    {
        return count($this->securityBlockers) > 0;
    }

    public function summary(): string
    {
        $parts = [];
        if (count($this->securityBlockers))      { $parts[] = 'SECURITY'; }
        if (count($this->internalBlockers))     { $parts[] = 'INTERNAL'; }
        if (count($this->externalBlockers))     { $parts[] = 'EXTERNAL'; }
        if (count($this->infrastructureBlockers)) { $parts[] = 'INFRA'; }
        return implode(' + ', $parts) ?: 'NONE';
    }
}
