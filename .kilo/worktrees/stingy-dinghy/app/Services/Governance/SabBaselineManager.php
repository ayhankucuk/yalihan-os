<?php

namespace App\Services\Governance;

use Illuminate\Support\Facades\File;
use App\Services\Logging\LogService;

/**
 * SabBaselineManager - Centralized authority for baseline and domain seal governance.
 */
class SabBaselineManager
{
    protected array $baseline = [];
    protected array $sealedDomains = [];
    protected string $baselinePath = '.sab/sab-baseline.json';

    public function __construct()
    {
        $this->loadBaseline();
        $this->loadSealedDomains();
    }

    /**
     * Load the current baseline from disk.
     */
    public function loadBaseline(?string $customPath = null): void
    {
        $path = base_path($customPath ?? $this->baselinePath);
        
        if (File::exists($path)) {
            try {
                $content = json_decode(File::get($path), true);
                $this->baseline = $content['ignored_violations'] ?? [];
            } catch (\Exception $e) {
                LogService::error('SAB Baseline: Load Error', ['path' => $path], $e);
            }
        }
    }

    /**
     * Load sealed domains from authority.json.
     */
    public function loadSealedDomains(): void
    {
        $authorityPath = base_path('.sab/authority.json');
        if (File::exists($authorityPath)) {
            try {
                $authority = json_decode(File::get($authorityPath), true);
                $this->sealedDomains = $authority['governance']['sealed_domains'] ?? [];
            } catch (\Exception $e) {
                LogService::error('SAB Sealed Domains: Load Error', ['path' => $authorityPath], $e);
            }
        }
    }

    /**
     * Check if a violation should be ignored based on the baseline.
     */
    public function isIgnored(string $file, array $violation): bool
    {
        // 🛡️ Domain Immunization (SAB v1.5)
        // If file is in a sealed domain, zero tolerance applies. Baseline is ignored.
        if ($this->isSealed($file)) {
            return false;
        }

        $fingerprint = $this->generateFingerprint($file, $violation);
        
        // Normalize file path for baseline lookup
        // Baseline might have paths relative to 'app/' or root.
        $lookupKeys = [$file];
        if (str_starts_with($file, 'app/')) {
            $lookupKeys[] = substr($file, 4); // Strip 'app/'
        }

        foreach ($lookupKeys as $key) {
            if (isset($this->baseline[$key])) {
                foreach ($this->baseline[$key] as $ignored) {
                    if (($ignored['fingerprint'] ?? '') === $fingerprint) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Update the baseline file with current violations.
     */
    public function updateBaseline(array $violations): bool
    {
        $path = base_path($this->baselinePath);
        
        $ignored = [];
        foreach ($violations as $v) {
            $file = $v['file'];
            if (str_starts_with($file, 'app/')) {
                $file = substr($file, 4);
            }
            
            $ignored[$file][] = [
                'line' => $v['line'],
                'type' => $v['type'],
                'message' => $v['message'],
                'fingerprint' => $v['fingerprint'],
            ];
        }

        $data = [
            'governance_version' => '1.7',
            'generated_at' => now()->toIso8601String(),
            'ignored_violations' => $ignored,
        ];

        try {
            File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->baseline = $ignored;
            return true;
        } catch (\Exception $e) {
            LogService::error('SAB Baseline: Update Error', ['path' => $path], $e);
            return false;
        }
    }

    /**
     * Check if a key exists in the baseline (debug helper).
     */
    public function hasBaselineKey(string $key): bool
    {
        return isset($this->baseline[$key]);
    }

    /**
     * Check if a file belongs to a sealed domain.
     */
    public function isSealed(string $file): bool
    {
        foreach ($this->sealedDomains as $sealedPath) {
            if (str_starts_with($file, $sealedPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a line-agnostic fingerprint for a violation.
     */
    public function generateFingerprint(string $file, array $violation): string
    {
        // 🛡️ SAB v1.7: Line-agnostic fingerprint — prevents drift from code shifts.
        // Normalize file path: if it starts with 'app/', strip it to match baseline format.
        $normalizedFile = $file;
        if (str_starts_with($file, 'app/')) {
            $normalizedFile = substr($file, 4);
        }

        // We use file, original type, and message to match existing baseline.
        $type = $violation['type'] ?? ''; 
        $message = $violation['message'] ?? '';
        
        return md5($normalizedFile . $type . $message);
    }

    /**
     * Get the current baseline violations count.
     */
    public function getBaselineCount(): int
    {
        $count = 0;
        foreach ($this->baseline as $violations) {
            $count += count($violations);
        }
        return $count;
    }
}
