<?php

namespace App\Services\Ydl\Collectors;

use Illuminate\Support\Facades\Log;

/**
 * YdlTestCollector — Runs Booking test suite via PHPUnit binary.
 *
 * Deterministic: parses PHPUnit summary output.
 * Uses vendor/bin/phpunit to avoid Artisan nesting.
 */
class YdlTestCollector
{
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? base_path();
    }

    /**
     * Run Booking test suite and parse results.
     */
    public function collect(): array
    {
        try {
            $phpunit = $this->basePath . '/vendor/bin/phpunit';
            $testDir = $this->basePath . '/tests/Feature/ChannelManager/Booking/';

            $cmd = sprintf(
                'cd %s && %s %s 2>&1',
                escapeshellarg($this->basePath),
                escapeshellarg($phpunit),
                escapeshellarg($testDir)
            );

            $output = (string) shell_exec($cmd);
        } catch (\Throwable $e) {
            Log::warning('YdlTestCollector: shell exec failed', ['error' => $e->getMessage()]);
            return $this->empty();
        }

        return $this->parseOutput($output);
    }

    private function parseOutput(string $output): array
    {
        // PHPUnit 10: "Tests: 73 passed (216 assertions)"
        if (preg_match('/Tests:\s*(\d+)\s+passed\s*\((\d+)\s+assertions\)/', $output, $m)) {
            return [
                'passed'     => (int) $m[1],
                'failed'     => 0,
                'assertions' => (int) $m[2],
                'suite'     => 'ChannelManager.Booking',
            ];
        }

        // PHPUnit failure: "Tests: X failed, Y passed"
        if (preg_match('/Tests:\s*(\d+)\s+failed,\s*(\d+)\s+passed/', $output, $m)) {
            return [
                'passed'     => (int) $m[2],
                'failed'     => (int) $m[1],
                'assertions' => 0,
                'suite'     => 'ChannelManager.Booking',
            ];
        }

        return $this->empty();
    }

    private function empty(): array
    {
        return [
            'passed'     => 0,
            'failed'     => 0,
            'assertions' => 0,
            'suite'     => 'ChannelManager.Booking',
        ];
    }
}
