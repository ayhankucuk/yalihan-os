<?php

namespace App\Services\Ydl\Collectors;

use Illuminate\Support\Facades\Artisan;

/**
 * YdlGitCollector — Reads git branch and commit from repository.
 *
 * Deterministic: always returns current HEAD state.
 */
class YdlGitCollector
{
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
        return trim((string) shell_exec('git branch --show-current 2>/dev/null') ?: 'unknown');
    }

    private function getCommit(): string
    {
        return trim((string) shell_exec('git rev-parse --short HEAD 2>/dev/null') ?: 'unknown');
    }

    private function getStatus(): string
    {
        $output = trim((string) shell_exec('git status --porcelain 2>/dev/null') ?: '');
        return $output === '' ? 'clean' : 'dirty';
    }
}
