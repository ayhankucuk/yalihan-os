<?php

namespace App\Services\Governance;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Services\Logging\LogService;

/**
 * BaselineDiffService — P3B
 *
 * Compares the current scan results against the persisted baseline to answer:
 * - Which violations were RESOLVED (in baseline, not in current scan)?
 * - Which violations are NEW (in current scan, not in baseline)?
 * - Which violations PERSIST (in both)?
 *
 * Read-only: never writes to sab-baseline.json.
 */
class BaselineDiffService
{
    protected array $baselineFingerprints = [];
    private string $baselinePath;

    public function __construct(string $baselinePath = '.sab/sab-baseline.json')
    {
        $this->baselinePath = $baselinePath;
        $this->loadBaseline();
    }

    /**
     * Load and index all fingerprints from the baseline file.
     */
    private function loadBaseline(): void
    {
        $path = base_path($this->baselinePath);

        if (!File::exists($path)) {
            return;
        }

        try {
            $content = json_decode(File::get($path), true);
            $ignored = $content['ignored_violations'] ?? [];

            foreach ($ignored as $file => $violations) {
                foreach ($violations as $v) {
                    $fp = $v['fingerprint'] ?? null;
                    if ($fp) {
                        $this->baselineFingerprints[$fp][] = [
                            'file'           => $file,
                            'line'           => $v['line'] ?? 0,
                            'violation_kind' => $v['type'] ?? '', // context7-ignore — reads baseline JSON schema field
                            'message'        => $v['message'] ?? '',
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Log at ERROR level — never swallow governance failures silently.
            Log::error('BaselineDiffService: Baseline load failed', [
                'path'  => $path,
                'error' => $e->getMessage(),
            ]);
            LogService::error('BaselineDiffService: Baseline load failed', ['path' => $path], $e);
        }
    }

    /**
     * Generate a diff against the current scan violations.
     *
     * @param  array  $currentViolations  Normalized violations from SabScanRunner::scan()
     * @return array{resolved: array, new: array, persisted: array, summary: array}
     */
    public function diff(array $currentViolations): array
    {
        $currentFps = [];
        foreach ($currentViolations as $v) {
            $fp = $v['fingerprint'] ?? null;
            if ($fp) {
                $currentFps[$fp][] = $v;
            }
        }

        $resolved = [];
        $new = [];
        $persisted = [];
        $baselineTotal = 0;

        foreach ($this->baselineFingerprints as $fp => $baselineEntries) {
            $bCount = count($baselineEntries);
            $baselineTotal += $bCount;
            
            $currentEntries = $currentFps[$fp] ?? [];
            $cCount = count($currentEntries);

            if ($bCount > $cCount) {
                // Some were resolved
                $resolvedCount = $bCount - $cCount;
                for ($i = 0; $i < $resolvedCount; $i++) {
                    $resolved[] = array_merge($baselineEntries[$i], ['fingerprint' => $fp]);
                }
                // The rest persisted
                for ($i = 0; $i < $cCount; $i++) {
                    $persisted[] = $currentEntries[$i];
                }
            } elseif ($cCount > $bCount) {
                // Some are new, baseline ones persisted
                for ($i = 0; $i < $bCount; $i++) {
                    $persisted[] = $currentEntries[$i];
                }
                $newCount = $cCount - $bCount;
                for ($i = $bCount; $i < $cCount; $i++) {
                    $new[] = $currentEntries[$i];
                }
            } else {
                // All persisted
                foreach ($currentEntries as $entry) {
                    $persisted[] = $entry;
                }
            }
        }

        // Find fingerprints that are completely new (not in baseline at all)
        foreach ($currentFps as $fp => $currentEntries) {
            if (!isset($this->baselineFingerprints[$fp])) {
                foreach ($currentEntries as $entry) {
                    $new[] = $entry;
                }
            }
        }

        return [
            'resolved'  => $resolved,
            'new'       => $new,
            'persisted' => $persisted,
            'summary'   => [
                'resolved_count'  => count($resolved),
                'new_count'       => count($new),
                'persisted_count' => count($persisted),
                'baseline_total'  => $baselineTotal,
            ],
        ];
    }

    /**
     * Get the total number of fingerprints in the baseline.
     */
    public function getBaselineFingerprintCount(): int
    {
        $count = 0;
        foreach ($this->baselineFingerprints as $entries) {
            $count += count($entries);
        }
        return $count;
    }

    /**
     * Check if a fingerprint exists in the baseline.
     */
    public function isInBaseline(string $fingerprint): bool
    {
        return isset($this->baselineFingerprints[$fingerprint]);
    }
}
