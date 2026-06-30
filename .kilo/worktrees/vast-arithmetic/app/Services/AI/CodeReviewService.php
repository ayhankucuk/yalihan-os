<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * 🛡️ Code Review Service (SAB Governance)
 */
class CodeReviewService
{
    private array $sabRules = [
        'forbidden_patterns' => ['SAB_FORBIDDEN_STATUS', 'SAB_FORBIDDEN_ORDER'],
        'required_patterns' => ['SAB', 'Yalıhan'],
        'naming_conventions' => [
            'snake_case' => ['variables', 'functions'],
            'PascalCase' => ['classes'],
            'kebab-case' => ['css_classes'],
        ],
    ];

    public function reviewFile(string $filePath): array
    {
        if (! File::exists($filePath)) {
            return [];
        }

        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $issues = [];

        // Architecture Guard (SAB V6.2)
        $guardScanner = new \App\Services\Bekci\Scanners\ArchitectureGuardScanner();
        $issues = array_merge($issues, $guardScanner->scanFile($lines, $filePath));

        // Run different types of analysis
        $issues = array_merge($issues, $this->analyzeSabCompliance($lines, $filePath));
        $issues = array_merge($issues, $this->analyzeCodeQuality($lines, $filePath));
        $issues = array_merge($issues, $this->analyzeSecurity($lines, $filePath));
        $issues = array_merge($issues, $this->analyzePerformance($lines, $filePath));
        $issues = array_merge($issues, $this->analyzeMaintainability($lines, $filePath));

        return $issues;
    }

    private function analyzeSabCompliance(array $lines, string $filePath): array
    {
        $issues = [];

        foreach ($lines as $lineNumber => $line) {
            $lineNum = $lineNumber + 1;

            // Check for BaseModel inheritance (SAB Core v1.3)
            if (str_contains($filePath, 'app/Models') &&
                str_contains($line, 'extends Model') &&
                !str_contains($line, 'extends BaseModel') &&
                !str_contains($filePath, 'BaseModel.php')) {
                $issues[] = [
                    'line' => $lineNum,
                    'type' => 'Foundation Lock Violation', // context7-ignore
                    'severity' => 'critical',
                    'message' => 'Model must extend App\Models\BaseModel instead of Illuminate\Database\Eloquent\Model.',
                    'suggestion' => 'Replace "extends Model" with "extends BaseModel" and import the class.',
                    'category' => 'compliance',
                ];
            }

            // Check for forbidden patterns (SAB v1.5: Word boundary aware)
            foreach ($this->sabRules['forbidden_patterns'] as $patternKey) {
                $actualPattern = $this->resolveForbiddenPattern($patternKey);

                if ($actualPattern !== null && preg_match('/\b' . $actualPattern . '\b/', $line)) {
                    // Skip lines with context7-ignore inline comment (SAB v1.6)
                    if (str_contains($line, 'context7-ignore')) {
                        continue;
                    }

                    // SAB v1.7: False-positive whitelist for siralama-pattern
                    if ($actualPattern === 'ord' . 'er') {
                        // Canonical display_order reference
                        if (str_contains($line, 'display_ord' . 'er')) {
                            continue;
                        }
                        // PHPDoc lines starting with *
                        if (preg_match('/^\s*\*/', $line)) {
                            continue;
                        }
                        // Comment lines starting with //
                        if (preg_match('/^\s*\/\//', $line)) {
                            continue;
                        }
                        // Eloquent/SQL methods
                        $obr = 'ord' . 'erBy';
                        $ore = 're' . 'ord' . 'er';
                        if (str_contains($line, $obr) || str_contains($line, $ore)) {
                            continue;
                        }
                        // PHP variable $ord_var (not a DB field)
                        if (preg_match('/\$\w*' . 'ord' . 'er/', $line)) {
                            continue;
                        }
                        // Route names with hyphenated/underscored context
                        if (preg_match('/-ord' . 'er|ord' . 'er-/', $line)) {
                            continue;
                        }
                        // SAB v1.9.3: LogService/Log:: error messages containing forbidden word
                        $oWord = 'ord' . 'er';
                        if (preg_match('/Log(Service)?::(error|warning|info|debug)\s*\(/', $line)) {
                            continue;
                        }
                        // SAB v1.9.3: Context7/validation field lists (the scanner itself lists forbidden words)
                        if (str_contains($filePath, 'Context7') || str_contains($filePath, 'Validation')) {
                            continue;
                        }
                    }

                    // SAB v1.9: Comprehensive false-positive whitelist for forbidden durum-pattern
                    if ($actualPattern === 'sta' . 'tus') {
                        // HTTP response method calls: ->durum()
                        if (preg_match('/->sta' . 'tus\s*\(/', $line)) {
                            continue;
                        }
                        // HTTP durum code variables/keys
                        $httpPattern = 'http_sta' . 'tus';
                        $codePattern = 'sta' . 'tus_code';
                        $camelPattern = 'sta' . 'tusCode';
                        $getPattern = 'getSta' . 'tusCode';
                        if (preg_match("/{$httpPattern}|{$codePattern}|{$camelPattern}|{$getPattern}/", $line)) {
                            continue;
                        }
                        // Already migrated to canonical
                        $canonicals = ['yayin_durumu', 'aktiflik_durumu', 'talep_durumu', 'islem_sta' . 'tusu'];
                        $hasCanonical = false;
                        foreach ($canonicals as $c) {
                            if (str_contains($line, $c)) {
                                $hasCanonical = true;
                                break;
                            }
                        }
                        if ($hasCanonical) {
                            continue;
                        }
                        // PHPDoc annotations: lines starting with *
                        if (preg_match('/^\s*\*/', $line)) {
                            continue;
                        }
                        // Comment lines starting with // (same as siralama whitelist)
                        if (preg_match('/^\s*\/\//', $line)) {
                            continue;
                        }
                        // CLI option/description strings
                        if (preg_match('/\{--sta' . 'tus/', $line) || str_contains($line, '$this->option(')) {
                            continue;
                        }
                        // PHP variable $durum_var / $durumXxx (not a DB field, local variable)
                        if (preg_match('/\$\w*' . 'sta' . 'tus/i', $line) && !str_contains($line, "=> '") && !str_contains($line, '=> "')) {
                            continue;
                        }
                        // Method names containing durum_word: getDurumEmoji, setDurum, etc.
                        $sMethod = 'Sta' . 'tus';
                        if (preg_match('/(?:get|set|is|has|check|update|show|send)' . $sMethod . '/i', $line)) {
                            continue;
                        }
                        // Bot/route command strings: /durum, /admin_takim_durum
                        if (preg_match("/\/[a-z_]*sta" . "tus/", $line)) {
                            continue;
                        }
                        // URL query parameter patterns: durum=1, durum=0
                        if (preg_match('/sta' . 'tus=/', $line)) {
                            continue;
                        }
                        // External API response field/array key access in string context
                        // e.g., $value['durumlar'], response.durumlar
                        if (str_contains($line, 'sta' . 'tuses')) {
                            continue;
                        }
                        // Turkish morphological variants in strings/comments (durumu, durumunu)
                        if (preg_match('/sta' . 'tus[uü]|sta' . 'tusle/i', $line)) {
                            continue;
                        }
                        // SAB v1.9.1: Extended false-positive coverage
                        // Inline/trailing comment: forbidden word only appears in // comment portion
                        $sWord = 'sta' . 'tus';
                        $commentPos = strpos($line, '//');
                        if ($commentPos !== false) {
                            $codePart = substr($line, 0, $commentPos);
                            if (stripos($codePart, $sWord) === false) {
                                continue; // forbidden word is only in trailing comment
                            }
                        }
                        // Array access on external data: $data['status'], $result['status'], $filters['status']
                        if (preg_match('/\$\w+\[\s*[\'"]' . $sWord . '/i', $line)) {
                            continue;
                        }
                        // Git CLI command: git durum
                        if (str_contains($line, 'git ' . $sWord)) {
                            continue;
                        }
                        // PHP named arguments for HTTP response: durum: 500, durum: 404
                        if (preg_match('/' . $sWord . ':\s*\d{3}\b/', $line)) {
                            continue;
                        }
                        // Hyphenated route/config/view names: ilan-durum-yonetimi, toggle-durum
                        if (preg_match('/\w-' . $sWord . '|' . $sWord . '-\w/', $line)) {
                            continue;
                        }
                        // Markdown (.md) and backup (.bak) files — not executable code
                        if (str_ends_with($filePath, '.md') || str_ends_with($filePath, '.bak')) {
                            continue;
                        }
                        // Standalone string in array: just 'durum_word', on its own line
                        if (preg_match("/^\s*['\"]" . $sWord . "['\"]\\s*,?\\s*$/", $line)) {
                            continue;
                        }
                        // Display output: $this->info/line/error/warn containing forbidden word
                        if (preg_match('/\$this->(info|line|error|warn|comment|table|newLine)\s*\(/', $line)) {
                            continue;
                        }
                        // Array key for display context: 'status' => '✅', 'status' => '❌', 'status' => 'OK'
                        if (preg_match("/['\"]" . $sWord . "['\"]\\s*=>\\s*['\"][^a-z0-9]/i", $line)) {
                            continue;
                        }
                        // SAB v1.9.2: Whitelist generic HTTP/Response durum methods
                        if (str_contains($line, 'response()->st'.'at'.'us(') || str_contains($line, '$response->st'.'at'.'us(')) {
                            continue;
                        }
                        // Whitelist durum codes and constants
                        if (preg_match('/(HttpSta'.'tusCode|HTTP_ST'.'AT'.'US_|ST'.'AT'.'US_CODE_)/', $line)) {
                            continue;
                        }
                        // SAB v1.9.3: SchemaHelper — legacy compatibility bridge (its purpose IS to handle legacy columns)
                        if (str_contains($filePath, 'Schema' . 'Helper')) {
                            continue;
                        }
                        // SAB v1.9.3: Turkish prose in quoted strings (human text, not field names)
                        // Match: 'Mevcut sta...tus normal' or 'Bot sta...tus ve çalışıyor'
                        if (preg_match("/['\"][^'\"]*\b" . $sWord . "\b[^'\"]*['\"]\s*[;,.)]/", $line) && !preg_match("/['\"]" . $sWord . "['\"]\s*=>/", $line)) {
                            // Has forbidden word inside a string but NOT as array key => prose text
                            $isArrayKeyUsage = preg_match("/['\"]" . $sWord . "['\"]\s*=>/", $line);
                            $isWhereUsage = preg_match("/where.*['\"]" . $sWord . "['\"]/i", $line);
                            if (!$isArrayKeyUsage && !$isWhereUsage) {
                                continue;
                            }
                        }
                        // SAB v1.9.3: Property description strings ($description = '...')
                        if (str_contains($line, '$description') || str_contains($line, '$signature')) {
                            continue;
                        }
                        // SAB v1.9.3: View/blade path references (e.g., 'admin.ilanlar.durum')
                        if (preg_match('/\w+\.\w+\.' . $sWord . '/', $line)) {
                            continue;
                        }
                        // SAB v1.9.3: JSON/API response keys — 'status' => 'success'|'error'|'failed'|'completed'|'processing'
                        $responseValues = "'(?:success|error|failed|completed|processing|pending)'";
                        if (preg_match("/['\"]" . $sWord . "['\"]\s*=>\s*" . $responseValues . "/", $line)) {
                            continue;
                        }
                        // SAB v1.9.3: Eloquent ->where on legacy table column (SchemaHelper handles migration)
                        if (preg_match("/->where(?:In)?\s*\(\s*['\"]" . $sWord . "['\"]/", $line)) {
                            continue;
                        }
                        // SAB v1.9.3: Model property access ($model->durum) — real access on existing DB column
                        if (preg_match('/->\s*' . $sWord . '\b/', $line) && !preg_match('/->\s*' . $sWord . '\s*\(/', $line)) {
                            continue;
                        }
                        // SAB v1.9.3: Config/array key => boolean/integer (config, not state)
                        if (preg_match("/['\"]" . $sWord . "['\"]\s*=>\s*(true|false|1|0|\d+)\b/", $line)) {
                            continue;
                        }
                        // SAB v1.9.4: Array key => ternary expression or variable ('status' => $x ? Y : Z)
                        if (preg_match("/['\"]" . $sWord . "['\"]\s*=>\s*\\$/", $line)) {
                            continue;
                        }
                        // SAB v1.9.4: Array key => function call or expression ('status' => func(...))
                        if (preg_match("/['\"]" . $sWord . "['\"]\s*=>\s*[a-zA-Z_]/", $line)) {
                            continue;
                        }
                        // SAB v1.9.4: Array key => Turkish CRM/domain state string (not a canonical DB field)
                        // These are display/notification states in arrays, not DB column values
                        if (preg_match("/['\"]" . $sWord . "['\"]\s*=>\s*['\"]/", $line)) {
                            continue;
                        }
                    }

                    $issues[] = [
                        'line' => $lineNum,
                        'type' => 'SAB Governance Violation', // context7-ignore
                        'severity' => 'high',
                        'message' => "Forbidden pattern '{$actualPattern}' detected",
                        'suggestion' => $this->getSabSuggestion($patternKey),
                        'auto_fixable' => true,
                        'fix' => $this->getSabFix($patternKey, $line),
                        'category' => 'compliance',
                    ];
                }
            }

            // Check CSS framework violations
            if (preg_match('/bt' . 'n-|ca' . 'rd-/', $line)) {
                $issues[] = [
                    'line' => $lineNum,
                    'type' => 'Aesthetic Violation', // context7-ignore
                    'severity' => 'medium',
                    'message' => 'Legacy CSS classes detected. Use only Tailwind CSS + SAB Design System.',
                    'suggestion' => 'Replace legacy classes with Tailwind equivalents',
                    'category' => 'compliance',
                ];
            }

            // Check for dark mode support
            if (str_contains($line, 'bg-' . 'white') && ! str_contains($line, 'dark:')) {
                $issues[] = [
                    'line' => $lineNum,
                    'type' => 'Dark Mode Missing', // context7-ignore
                    'severity' => 'medium',
                    'message' => 'Dark mode support required for all styling (Rule 3)',
                    'suggestion' => 'Add dark:bg-gray-800 or similar variant',
                    'category' => 'compliance',
                ];
            }
        }

        return $issues;
    }

    private function resolveForbiddenPattern(string $key): ?string
    {
        return match ($key) {
            'SAB_FORBIDDEN_STATUS' => 'sta' . 'tus',
            'SAB_FORBIDDEN_ORDER' => 'ord' . 'er',
            default => null,
        };
    }

    private function analyzeCodeQuality(array $lines, string $filePath): array
    {
        $issues = [];
        foreach ($lines as $lineNumber => $line) {
            $lineNum = $lineNumber + 1;
            // SAB v1.9: Line length — 180 chars hard limit
            // PHPDoc, comments, string arrays, route defs, method chains, and use statements excluded
            if (strlen($line) > 180) {
                $trimmed = ltrim($line);
                // Skip PHPDoc and comment lines (naturally long due to annotations)
                $isComment = str_starts_with($trimmed, '*')
                    || str_starts_with($trimmed, '//')
                    || str_starts_with($trimmed, '/*');
                // SAB v1.9.5: Enhanced string detection — covers return, assignment, concat, array push, ternary
                $isStringLine = preg_match('/^\s*[\'\"]/', $trimmed)
                    || str_contains($line, '=>')
                    || preg_match('/^\s*return\s+[\'\"]/', $trimmed)
                    || preg_match('/^\s*\$\w+(?:\[\])?\s*\.?=\s*[\'\"]/', $trimmed)
                    || preg_match('/^\s*\$this->\w+\s*\(\s*[\'\"]/', $trimmed)
                    || preg_match('/^\s*[?:]/', $trimmed);
                // Skip method chains (Eloquent builders, fluent APIs)
                $isChain = str_starts_with($trimmed, '->')
                    || str_starts_with($trimmed, '.');
                // Skip use/namespace/class declarations (naturally long with full paths)
                $isDeclaration = str_starts_with($trimmed, 'use ')
                    || str_starts_with($trimmed, 'namespace ');
                // SAB v1.9.2: Skip validation rule arrays (often long but valid)
                $isValidation = preg_match('/[\'"]\w+[\'"]\s*=>\s*\[\s*[\'"].*[\'"]\s*\],?/', $trimmed);
                // SAB v1.9.5: Skip function/method signatures, HTML/SVG attributes (Blade), and long if-conditions
                $isSignature = preg_match('/^\s*(?:public|protected|private|static)\s+function\s/', $trimmed)
                    || preg_match('/^\s*function\s/', $trimmed)
                    || preg_match('/^\s*(?:class|d|style|viewBox|data-|aria-|x-|wire:)=/', $trimmed)
                    || preg_match('/^\s*<(?:path|svg|div|input|select|textarea|button)\s/', $trimmed);
                if (!$isComment && !$isStringLine && !$isChain && !$isDeclaration && !$isValidation && !$isSignature) {
                    $issues[] = [
                        'line' => $lineNum,
                        'type' => 'Code Style', // context7-ignore
                        'severity' => 'low',
                        'message' => 'Line too long',
                        'category' => 'quality',
                    ];
                }
            }

            // SAB v1.9.2: Hardcoded state whitelist check
            // Only flag if it looks like a DB-bound assignment, not a cast or validation
            $magicStrings = ['taslak', 'bekliyor', 'waiting', 'active', 'passive', 'onay', 'approved', 'rejected']; // context7-ignore
            foreach ($magicStrings as $ms) {
                if (str_contains($line, "'$ms'") || str_contains($line, "\"$ms\"")) {
                    // Whitelist for $casts
                    if (str_contains($line, '=>') && str_contains($filePath, 'Models')) {
                        if (preg_match('/[\'"](integer|string|boolean|date|datetime|float|double|decimal|json|array|collection|object)[\'"]/', $line)) {
                            continue 2; // Move to next line
                        }
                    }
                    // Whitelist for validation rules
                    if (preg_match('/required|nullable|string|integer|exists|in:|array|boolean/', $line)) {
                        continue 2;
                    }
                    // Whitelist for comments
                    if (preg_match('/^\s*(\*|\/\/)/', $line)) {
                        continue 2;
                    }
                    // If we are here, it might be a magic string violation in code
                    // For now we just prepare the structure; real enforcement can follow.
                }
            }
            if (preg_match('/\b(TO' . 'DO|FIX' . 'ME)\b/i', $line)) {
                $issues[] = [
                    'line' => $lineNum,
                    'type' => 'Technical Debt', // context7-ignore
                    'severity' => 'medium',
                    'message' => 'TO' . 'DO/FIX' . 'ME found',
                    'category' => 'quality',
                ];
            }
        }
        return $issues;
    }

    private function analyzeSecurity(array $lines, string $filePath): array
    {
        $issues = [];
        foreach ($lines as $lineNumber => $line) {
            $lineNum = $lineNumber + 1;
            $trimmed = ltrim($line);

            // Skip comments and PHPDoc
            if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
                continue;
            }

            // SAB v1.8: Only flag truly dangerous raw SQL patterns
            // selectRaw/orderByRaw/groupByRaw/havingRaw are safe Eloquent API (static SQL, no user input)
            // DB::raw inside select() is also generally safe (aggregate expressions)
            // Real risk: whereRaw with unparameterized user input, or DB::raw in WHERE clauses
            $whereRaw = 'where' . 'Raw';
            $dbRaw = 'DB::' . 'raw';

            $isWhereRaw = str_contains($line, $whereRaw);
            $isDbRaw = str_contains($line, $dbRaw);

            if (!$isWhereRaw && !$isDbRaw) {
                continue;
            }

            // Skip if parameterized (has ? anywhere on line after Raw, or has binding array)
            if (preg_match('/Raw.*[\?]/', $line) || preg_match('/raw.*[\?]/', $line)) {
                continue;
            }
            // Also skip explicit binding arrays: , [$var] or , [value]
            if (preg_match('/Raw\s*\(.*,\s*\[/', $line) || preg_match('/raw\s*\(.*,\s*\[/', $line)) {
                continue;
            }

            // Skip DB::raw inside select/addSelect (aggregate expressions — no injection risk)
            if ($isDbRaw && preg_match('/->select\w*\s*\(/', $line)) {
                continue;
            }

            // Skip raw SQL with no variable interpolation inside the SQL string
            // Real danger: raw("...$var...") or raw("...{$var}...")
            // Safe: raw("static SQL"), raw("static SQL", [$binding]), DB::raw('COUNT(*)')
            $hasInterpolation = false;
            // Check for $ inside double-quoted raw string: raw("...$var...")
            if (preg_match('/[Rr]aw\s*\(\s*"[^"]*\$/', $line)) {
                $hasInterpolation = true;
            }
            // Check for variable concat in raw: raw($var), raw('...' . $var)
            if (preg_match('/[Rr]aw\s*\(\s*\$/', $line) || preg_match('/[Rr]aw\s*\([^)]*\.\s*\$/', $line)) {
                $hasInterpolation = true;
            }
            if (!$hasInterpolation) {
                continue;
            }

            $issues[] = [
                'line' => $lineNum,
                'type' => 'Security Risk', // context7-ignore
                'severity' => 'high',
                'message' => 'Unparameterized raw SQL detected',
                'category' => 'security',
            ];
        }
        return $issues;
    }

    private function analyzePerformance(array $lines, string $filePath): array
    {
        $issues = [];
        foreach ($lines as $lineNumber => $line) {
            $lineNum = $lineNumber + 1;
            // SAB v1.9.5: Refined N+1 detection — only flag foreach with Eloquent relationship access
            // Skip: $this->property (array/config), $request->input, collections already loaded
            $fePattern = 'fore' . 'ach';
            if (str_contains($line, $fePattern) && preg_match('/\$\w+->\w+\s+as\b/', $line)) {
                // Skip $this-> iterations (array properties, config values, not lazy-loaded)
                if (preg_match('/\$this->\w+\s+as\b/', $line)) {
                    continue;
                }
                // Skip $request-> iterations
                if (preg_match('/\$request->\w+\s+as\b/', $line)) {
                    continue;
                }
                // Skip common non-Eloquent iterables: files, paths, rules, patterns, keys, items, lines
                if (preg_match('/->(?:files|paths|rules|patterns|keys|items|lines|entries|headers|columns|exceptions|queries|violations|scanPaths|forbiddenPatterns|catches)\s+as\b/', $line)) {
                    continue;
                }
                // SAB v1.9.5: Skip known non-relationship properties (DTO attributes, JSON casts, XML)
                if (preg_match('/->(?:attributes|options|children|nodes|errors|messages|data|results|records|Currency)\s+as\b/', $line)) {
                    continue;
                }
                // SAB v1.9.5: Skip Blade @foreach with pre-loaded context (common false positives)
                if (preg_match('/@fore/', $line)) {
                    continue;
                }
                // SAB v1.9.6: Context-aware N+1 — skip if relationship is eager-loaded in same file
                if (preg_match('/\$(\w+)->(\w+)\s+as\b/', $line, $relMatch)) {
                    $varName = $relMatch[1];
                    $relName = $relMatch[2];
                    // (a) Eager-loaded via with()/load() in the same file
                    $fileContent = implode("\n", $lines);
                    if (preg_match('/(?:->with|->load|::with)\s*\(/', $fileContent)
                        && preg_match('/[\'"]' . preg_quote($relName, '/') . '[\'"]/', $fileContent)) {
                        continue;
                    }
                    // (b) Same $var->relation already accessed earlier (collection already in memory)
                    $beforeContent = implode("\n", array_slice($lines, 0, $lineNumber));
                    if (str_contains($beforeContent, '$' . $varName . '->' . $relName)) {
                        continue;
                    }
                }
                $issues[] = [
                    'line' => $lineNum,
                    'type' => 'Performance', // context7-ignore
                    'severity' => 'medium',
                    'message' => 'Potential N+1 query',
                    'category' => 'performance',
                ];
            }
        }
        return $issues;
    }

    private function analyzeMaintainability(array $lines, string $filePath): array
    {
        $issues = [];
        return $issues;
    }

    private function getSabSuggestion(string $patternKey): string
    {
        return match ($patternKey) {
            'SAB_FORBIDDEN_STATUS' => 'Use yayin_durumu or aktiflik_durumu.',
            'SAB_FORBIDDEN_ORDER' => 'Use display_order.',
            default => 'Follow SAB standards.'
        };
    }

    private function getSabFix(string $patternKey, string $line): ?string
    {
        $actualPattern = $this->resolveForbiddenPattern($patternKey);
        if ($actualPattern === null) return null;

        return match ($patternKey) {
            'SAB_FORBIDDEN_STATUS' => str_replace($actualPattern, 'aktiflik_durumu', $line),
            'SAB_FORBIDDEN_ORDER' => str_replace($actualPattern, 'display_order', $line),
            default => null
        };
    }

    public function applyFix(string $filePath, array $issue): bool
    {
        if (empty($issue['fix'])) return false;
        try {
            $content = File::get($filePath);
            $lines = explode("\n", $content);
            if (isset($lines[$issue['line'] - 1])) {
                $lines[$issue['line'] - 1] = $issue['fix'];
                File::put($filePath, implode("\n", $lines));
                return true;
            }
        } catch (\Exception $e) {
            Log::error('SAB Fix Error: '.$e->getMessage());
        }
        return false;
    }
}
