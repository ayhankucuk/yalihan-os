<?php

namespace App\Services\Ydl\Collectors;

/**
 * YdlGitCollector — Reads git branch and commit from repository.
 *
 * YDL v1 Phase 1
 *
 * Deterministic: always returns current HEAD state.
 * Uses git -C flag to target the correct repository root.
 */
class YdlGitCollector
{
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? base_path();
    }

    public function collect(): array
    {
        $branch = $this->getBranch();
        $commit = $this->getCommit();
        $status = $this->getStatus();

        return [
            'branch' => $branch,
            'commit' => $commit,
            'status' => $status,
            'clean'  => $status === 'clean',
        ];
    }

    private function getBranch(): string
    {
        return trim((string) shell_exec("git -C {$this->basePath} branch --show-current 2>/dev/null") ?: 'unknown');
    }

    private function getCommit(): string
    {
        return trim((string) shell_exec("git -C {$this->basePath} rev-parse --short HEAD 2>/dev/null") ?: 'unknown');
    }

    private function getStatus(): string
    {
        // Only check tracked files for changes.
        // Untracked files (e.g. runtime state like memory/ydl/state/current.json)
        // are intentionally excluded — they are managed by the YDL pipeline, not git.
        $output = trim((string) shell_exec("git -C {$this->basePath} diff --stat 2>/dev/null") ?: '');
        $staged = trim((string) shell_exec("git -C {$this->basePath} diff --cached --stat 2>/dev/null") ?: '');
        return ($output !== '' || $staged !== '') ? 'dirty' : 'clean';
    }
}
