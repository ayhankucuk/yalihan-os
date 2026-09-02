<?php

declare(strict_types=1);

namespace App\Domain\DriftAudit\DTO;

/**
 * Individual drift check result.
 */
final class DriftCheck
{
    public function __construct(
        public readonly string $name,
        public readonly string $status,      // PASS | FAIL | WARN | SKIP
        public readonly string $label,         // REPO_VERIFIED | etc.
        public readonly string $summary,
        public readonly array  $findings,
        public readonly int    $findingCount,
    ) {}

    public function toArray(): array
    {
        return [
            'name'          => $this->name,
            'status'       => $this->status,
            'label'        => $this->label,
            'summary'      => $this->summary,
            'findings'     => $this->findings,
            'finding_count'=> $this->findingCount,
        ];
    }
}
