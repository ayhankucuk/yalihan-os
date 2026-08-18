<?php

namespace App\Domain\ChannelManager\Models;

/**
 * SyncResult — Value Object for sync operation results
 *
 * Sprint 13 E01: Domain Foundation
 */
readonly class SyncResult
{
    public function __construct(
        public bool $success,
        public int $syncedCount,
        public int $conflictCount,
        public array $conflicts = [],
        public ?string $errorMessage = null,
        public array $metadata = [],
    ) {}

    /**
     * Create a successful sync result
     */
    public static function success(int $syncedCount, array $conflicts = [], array $metadata = []): self
    {
        return new self(
            success: true,
            syncedCount: $syncedCount,
            conflictCount: count($conflicts),
            conflicts: $conflicts,
            metadata: $metadata,
        );
    }

    /**
     * Create a failed sync result
     */
    public static function failure(string $errorMessage, int $syncedCount = 0): self
    {
        return new self(
            success: false,
            syncedCount: $syncedCount,
            conflictCount: 0,
            errorMessage: $errorMessage,
        );
    }

    /**
     * Check if there are conflicts
     */
    public function hasConflicts(): bool
    {
        return $this->conflictCount > 0;
    }

    /**
     * Check if operation was fully successful (no errors, no conflicts)
     */
    public function isFullySuccessful(): bool
    {
        return $this->success && $this->conflictCount === 0;
    }
}
