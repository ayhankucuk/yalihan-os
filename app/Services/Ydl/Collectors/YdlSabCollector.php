<?php

namespace App\Services\Ydl\Collectors;

use Illuminate\Support\Facades\Log;

/**
 * YdlSabCollector — Parses sab:integrity-scan output via shell.
 *
 * Deterministic: parses structured output lines.
 */
class YdlSabCollector
{
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? base_path();
    }

    public function collect(): array
    {
        try {
            $cmd = 'cd ' . escapeshellarg($this->basePath)
                . ' && php artisan sab:integrity-scan 2>&1';

            $output = (string) shell_exec($cmd);
        } catch (\Throwable $e) {
            Log::warning('YdlSabCollector: sab:integrity-scan failed', ['error' => $e->getMessage()]);
            return $this->empty();
        }

        return $this->parseOutput($output);
    }

    private function parseOutput(string $output): array
    {
        $lines = explode("\n", $output);

        $total      = 0;
        $newCount   = 0;
        $blocking   = 0;

        foreach ($lines as $line) {
            if (preg_match('/FAIL:\s*(\d+)\s+new/i', $line, $m)) {
                $total    = (int) $m[1];
                $newCount = (int) $m[1];
            }
            if (stripos($line, 'PASS') !== false && $total === 0 && $newCount === 0) {
                // Clean run — no violations
            }
            if (stripos($line, 'blocking violation') !== false && preg_match('/(\d+)/', $line, $m)) {
                $blocking = max($blocking, (int) $m[1]);
            }
        }

        return [
            'total_violations'    => $total,
            'new_violations'     => $newCount,
            'blocking_violations' => $blocking,
            'raw_output_length'   => strlen($output),
        ];
    }

    private function empty(): array
    {
        return [
            'total_violations'    => 0,
            'new_violations'     => 0,
            'blocking_violations' => 0,
            'raw_output_length'   => 0,
        ];
    }
}
