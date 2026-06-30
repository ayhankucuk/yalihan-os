<?php

namespace App\Services\Sab;

use Illuminate\Support\Facades\File;

/**
 * SAB Guard v3 - Self-Protecting Production Integrity Hardening & Anti-Bypass
 */
class SabAutomationGuardService
{
    private array $issues = [];

    public function runAllGuards(): array
    {
        $this->issues = [];

        $this->checkSelfGuardIntegrity();
        $this->checkContext7();
        $this->checkAntiBypass();
        $this->checkThinController();
        $this->checkServiceLayer();
        $this->checkCQRS();
        $this->checkSilentCatch();
        $this->checkReadMeSync();
        $this->checkUiResponseSync();
        $this->checkAiDiscovery();

        return $this->issues;
    }

    private function addIssue(string $guard, string $file, int $line, string $message, string $severity = 'ERROR', string $fix = ''): void
    {
        $this->issues[] = [
            'guard' => $guard,
            'file' => str_replace(base_path() . '/', '', $file),
            'line' => $line,
            'message' => $message,
            'severity' => strtoupper($severity),
            'fix' => $fix
        ];
    }

    private function getFiles(string $directory, string $extension = 'php'): array
    {
        if (!File::isDirectory(base_path($directory))) {
            return [];
        }

        $allFiles = File::allFiles(base_path($directory));
        return array_filter($allFiles, fn($f) => $f->getExtension() === $extension);
    }

    private function getForbiddenWords(): array
    {
        return array_keys(config('sab.forbidden_fields', []));
    }

    private function checkSelfGuardIntegrity(): void
    {
        $file = __FILE__;
        $content = File::get($file);

        // We no longer hardcode the regexes or suppressions here.
        // We just ensure the config isolation is respected.
        if (!config()->has('sab.forbidden_fields')) {
            $this->addIssue('CONFIG_ISOLATION_RULE', $file, 1, "config('sab.forbidden_fields') is missing. Guard metadata must be isolated.", 'CRITICAL', 'Create config/sab.php.');
        }

        // To ensure the guard doesn't have evasions itself, we dynamically check against the config (if available)
        $evasions = config('sab.evasion_regexes', []);
        foreach ($evasions as $regex => $type) {
            // We strip out the regexes from the source code itself so it doesn't self-trigger on the definitions
            $cleanContent = str_replace($regex, '', $content);
            if (preg_match($regex, $cleanContent)) {
                 $this->addIssue('SELF_GUARD_INTEGRITY_V3', $file, 1, "Guard uses $type evasion hacks. Must use config isolation.", 'CRITICAL', 'Remove evasion and use config(\'sab.forbidden_fields\').');
            }
        }

        $suppressions = config('sab.suppressions', []);
        foreach ($suppressions as $suppress) {
            $cleanContent = str_replace($suppress, '', $content);
            if (str_contains(strtolower($cleanContent), strtolower($suppress))) {
                $this->addIssue('SELF_GUARD_INTEGRITY_V3', $file, 1, "Guard source contains linter suppression.", 'CRITICAL', 'Remove suppression.');
            }
        }
    }

    private function checkAntiBypass(): void
    {
        $directories = ['app', 'resources/views'];
        $suppressions = config('sab.suppressions', []);
        $evasionRegexes = config('sab.evasion_regexes', []);

        foreach ($directories as $dir) {
            foreach ($this->getFiles($dir, 'php') as $file) {
                if ($file->getFilename() === 'SabAutomationGuardService.php') continue;

                $content = File::get($file);
                $lines = explode("\n", $content);

                foreach ($lines as $i => $line) {
                    $trimmed = trim($line);
                    if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*')) {
                        continue;
                    }

                    foreach ($suppressions as $suppress) {
                        if (str_contains(strtolower($line), strtolower($suppress))) {
                            $this->addIssue('ANTI_BYPASS_GUARD_V3', $file->getPathname(), $i + 1, "Suppression detected: `{$suppress}`. Rule evasion is forbidden.", 'CRITICAL', "Remove suppression code entirely.");
                        }
                    }

                    foreach ($evasionRegexes as $regex => $type) {
                        if (preg_match($regex, $line)) {
                            $this->addIssue('ANTI_BYPASS_GUARD_V3', $file->getPathname(), $i + 1, "{$type} evasion attempt detected. Do not obfuscate fields.", 'CRITICAL', "Use real domain model field.");
                        }
                    }
                }
            }
        }
    }

    private function checkContext7(): void
    {
        $rules = config('sab.forbidden_fields', []);
        if (empty($rules)) return;

        $forbidden = array_keys($rules);
        $pattern = '/([\'"]|->|\.)(' . implode('|', $forbidden) . ')([\'"]|\b)/i';

        $directories = ['app/Services', 'app/Http/Controllers', 'app/Models', 'resources/views/advisor'];

        foreach ($directories as $dir) {
            foreach ($this->getFiles($dir, str_contains($dir, 'views') ? 'php' : 'php') as $file) {
                if ($file->getFilename() === 'SabAutomationGuardService.php') continue;

                $content = File::get($file);
                $lines = explode("\n", $content);

                foreach ($lines as $i => $line) {
                    if (str_contains($line, '//') || str_contains($line, '/*')) {
                        if (str_contains($line, 'context7-ignore')) {
                            continue; // Legitimation for API schemas / external payloads
                        }

                        // Very strict: verify required if it's in a comment.
                        if (preg_match($pattern, $line, $matches)) {
                            $this->addIssue('CONTEXT7_GUARD_V3', $file->getPathname(), $i + 1, "Context7 forbidden keyword '{$matches[2]}' in comment/doc. False positive?", 'VERIFY_REQUIRED', "Review comment context.");
                        }
                        continue;
                    }

                    if (preg_match($pattern, $line, $matches)) {
                        $word = strtolower($matches[2]);
                        $fix = $rules[$word] ?? 'Domain field names';

                        $this->addIssue('CONTEXT7_GUARD_V3', $file->getPathname(), $i + 1, "Forbidden field '{$word}' usage detected in code.", 'FAIL', "Replace with: {$fix}");
                    }
                }
            }
        }
    }

    private function checkThinController(): void
    {
        $files = $this->getFiles('app/Http/Controllers');
        // Match direct mutations, complex builder, AI logic inline
        $forbiddenRegex = '/(DB::|\->save\(\)|\->update\(\)|\->delete\(\)|\->create\(\)|\->where\(\)|\->join\(\)|\->select\(\))/';

        foreach ($files as $file) {
            $content = File::get($file);
            // Allow file-level skipping for known complex controllers
            if (str_contains($content, '@sab-ignore-thin') || str_contains($content, 'sab-ignore-thin')) {
                continue;
            }

            $lines = explode("\n", $content);
            $ifCount = 0;
            $chainCount = 0;

            foreach ($lines as $i => $line) {
                if (str_contains($line, '//') || str_contains($line, 'sab-ignore')) continue;

                if (preg_match($forbiddenRegex, $line)) {
                    $this->addIssue('THIN_CONTROLLER_GUARD_V3', $file->getPathname(), $i + 1, "Direct mutation or query building inside Controller.", 'FAIL', "Move logic to Service Layer or Repository.");
                }

                if (preg_match('/^\s*if\s*\(/', $line)) {
                    $ifCount++;
                }

                if (substr_count($line, '->') >= 3) {
                    $chainCount++;
                }
            }

            if ($ifCount >= 4) {
                $this->addIssue('THIN_CONTROLLER_GUARD_V3', $file->getPathname(), 1, "Complex branching logic detected in Controller ({$ifCount} blocks).", 'FAIL', "Extract orchestration to a Service.");
            }
            if ($chainCount >= 3) {
                $this->addIssue('THIN_CONTROLLER_GUARD_V3', $file->getPathname(), 1, "Heavy method chaining / orchestration detected in Controller.", 'FAIL', "Delegate mapping/orchestration to Service.");
            }
        }
    }

    private function checkServiceLayer(): void
    {
        $files = $this->getFiles('app/Http/Controllers');
        foreach ($files as $file) {
            if ($file->getFilename() === 'Controller.php') continue;

            $content = File::get($file);
            if (str_contains($content, '@sab-ignore-service') || str_contains($content, 'sab-ignore-service')) {
                continue;
            }
            if (!preg_match('/use App\\\\(Services|UseCases)/', $content) && preg_match('/class.*extends Controller/', $content)) {
                $this->addIssue('SERVICE_LAYER_GUARD_V3', $file->getPathname(), 1, "Controller bypasses Service Layer. No App\Services or App\UseCases imported.", 'FAIL', "Inject a Service to handle business logic.");
            }
        }
    }

    private function checkCQRS(): void
    {
        $files = $this->getFiles('app/Models/Projections');

        foreach ($files as $file) {
            $lines = explode("\n", File::get($file));
            foreach ($lines as $i => $line) {
                if (str_contains($line, '//')) continue;
                if (preg_match('/(->save\(\)|->update\(\)|->delete\(\))/', $line)) {
                    $this->addIssue('CQRS_GUARD_V3', $file->getPathname(), $i + 1, "Write mutation inside a Read-Model Projection. CQRS boundary broken.", 'FAIL', "Use core write model via dispatch projectable events.");
                }
            }
        }
    }

    private function checkSilentCatch(): void
    {
        $directories = ['app/Services', 'app/Http/Controllers'];
        foreach ($directories as $dir) {
            foreach ($this->getFiles($dir) as $file) {
                $content = File::get($file);
                if (str_contains($content, '@sab-ignore-catch') || str_contains($content, 'sab-ignore-catch')) {
                    continue;
                }
                // Extract full catch bodies
                if (preg_match_all('/catch\s*\([^)]+\)\s*\{\s*([^}]+)\s*\}/s', $content, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[1] as $index => $match) {
                        $catchBody = $match[0];
                        if (str_contains($catchBody, 'sab-ignore')) continue;

                        if (!preg_match('/(throw|return .*Response|Log::|\$fail|report\()/', $catchBody)) {
                            // Fails if it returns a generic false without logging
                            if (preg_match('/return\s+(false|null|0|\[\]);/', $catchBody)) {
                                $line = substr_count(mb_substr($content, 0, $matches[0][$index][1]), "\n") + 1;
                                $this->addIssue('SILENT_CATCH_GUARD_V3', $file->getPathname(), $line, "Catch block returns generic failure without logging.", 'FAIL', "Fail-loud: Add Log::error() before returning.");
                            } else {
                                $line = substr_count(mb_substr($content, 0, $matches[0][$index][1]), "\n") + 1;
                                $this->addIssue('SILENT_CATCH_GUARD_V3', $file->getPathname(), $line, "Silent/Swallowed catch block.", 'FAIL', "Fail-loud: Add Log::error() or throw exception.");
                            }
                        }
                    }
                }

                // Catch completely empty blocks
                if (preg_match_all('/catch\s*\([^)]+\)\s*\{\s*\}/', $content, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $match) {
                        $line = substr_count(mb_substr($content, 0, $match[1]), "\n") + 1;
                        $this->addIssue('SILENT_CATCH_GUARD_V3', $file->getPathname(), $line, "Empty catch block detected.", 'FAIL', "Log or throw the exception.");
                    }
                }
            }
        }
    }

    private function checkReadMeSync(): void
    {
        $readme = base_path('README.md');
        if (!File::exists($readme)) {
            $this->addIssue('README_SYNC_GUARD_V3', 'README.md', 1, "README.md not found. Required for SSOT.", 'FAIL', "Create README.md.");
            return;
        }

        $content = File::get($readme);
        if (!preg_match('/Self-Protecting/i', $content) || !preg_match('/Config-Isolated scanner metadata/i', $content)) {
            $this->addIssue('README_SYNC_GUARD_V3', 'README.md', 1, "Self-Protecting Enforcement or Config-isolated scanner metadata not documented.", 'FAIL', "Document SAB Guard V3 concepts in README.");
        }

        // Match endpoints
        if (preg_match_all('|GET /api/v1/([a-zA-Z0-9/_{}-]+)|', $content, $matches)) {
            $apiContent = File::exists(base_path('routes/api.php')) ? File::get(base_path('routes/api.php')) : '';
            foreach ($matches[1] as $endpoint) {
                $segments = explode('/', trim($endpoint, '/'));
                if (count($segments) >= 2) {
                    $search = '/' . $segments[count($segments)-2] . '/' . $segments[count($segments)-1];
                    $search = preg_replace('/\{[a-zA-Z0-9]+\}/', '', $search);

                    if (!empty(trim($search, '/')) && !str_contains($apiContent, trim($search, '/'))) {
                        $this->addIssue('README_SYNC_GUARD_V3', 'README.md', 1, "Endpoint {$endpoint} claimed in README but not in routes/api.php", 'FAIL', "Remove from README or implement real endpoint.");
                    }
                }
            }
        }
    }

    private function checkUiResponseSync(): void
    {
        $blade = base_path('resources/views/advisor/doctor/dashboard.blade.php');
        if (File::exists($blade)) {
            $content = File::get($blade);
            $forbiddenWords = $this->getForbiddenWords();

            foreach ($forbiddenWords as $word) {
                if (preg_match('/summary\.' . $word . '/i', $content) || preg_match('/probData\.' . $word . '/i', $content)) {
                    $this->addIssue('UI_RESPONSE_SYNC_GUARD_V3', $blade, 1, "Forbidden binding (summary.{$word} or probData.{$word}) left in UI.", 'FAIL', "Map to proper API schema domain field.");
                }
                if (preg_match('/data\.' . $word . '\s*\|\|/i', $content)) {
                    $this->addIssue('UI_RESPONSE_SYNC_GUARD_V3', $blade, 1, "UI fallback drift: using forbidden JS binding (data.{$word}).", 'FAIL', "Fix API response mapping.");
                }
            }
        }
    }

    private function checkAiDiscovery(): void
    {
        $docService = base_path('app/Services/AI/Portfolio/PortfolioDoctorService.php');
        if (!File::exists($docService)) return;

        $content = File::get($docService);
        $checkServices = ['MarketIntelligenceService', 'DealPredictor', 'CompetitorMapService', 'CortexPriceForecastService', 'IntelligenceHub'];

        foreach ($checkServices as $svc) {
            $imported = preg_match('/use.*' . $svc . ';/', $content);
            $used = preg_match('/new ' . $svc . '|::[a-zA-Z]+\(|->' . current(explode('Service', lcfirst($svc))) . '/i', $content);
            $injected = preg_match('/' . $svc . '\s+\$[a-zA-Z]/', $content);

            if (($imported || $injected) && !$used) {
                if ($svc !== 'IntelligenceHub') {
                    $this->addIssue('AI_DISCOVERY_GUARD_V3', $docService, 1, "Service {$svc} is a Ghost Integration (imported but uncalled).", 'FAIL', "Remove import/injection or implement real logic.");
                } else {
                    $this->addIssue('AI_DISCOVERY_GUARD_V3', $docService, 1, "IntelligenceHub delegated but direct usage is obscured.", 'VERIFY_REQUIRED', "Check orchestration delegation.");
                }
            }
        }
    }
}
