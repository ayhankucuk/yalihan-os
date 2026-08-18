<?php

namespace App\Services\Ydl\Patchers;

/**
 * YdlPatch — Planned patch for one memory target.
 *
 * YDL v1 Phase 2A
 *
 * @readonly
 */
final class YdlPatch
{
    public function __construct(
        public readonly string $target,       // e.g. 'docs/BEKCI_CHANGELOG.md'
        public readonly string $operation,     // e.g. 'prepend_section', 'update_state'
        public readonly string $currentHash,   // md5 of current file content
        public readonly string $plannedHash,   // md5 of planned new content
        public readonly string $rationale,     // Why this patch is needed
        public readonly array  $changes,      // Structured change description
        public readonly string $newContent,   // The actual planned content
    ) {}

    /**
     * True if this patch would actually change the file.
     */
    public function isChange(): bool
    {
        return $this->operation !== 'noop_idempotent';
    }

    /**
     * True if patch was identified as already applied (idempotent no-op).
     */
    public function isNoOp(): bool
    {
        return $this->operation === 'noop_idempotent';
    }

    public function toArray(): array
    {
        return [
            'target'       => $this->target,
            'operation'    => $this->operation,
            'current_hash' => $this->currentHash,
            'planned_hash' => $this->plannedHash,
            'would_change' => $this->isChange(),
            'noop'        => $this->isNoOp(),
            'rationale'    => $this->rationale,
            'changes'     => $this->changes,
        ];
    }
}
