<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DetectSchemaDrift extends Command
{
    protected $signature = 'system:detect-schema-drift
        {--table=* : Scan for specific table(s) only}
        {--severity= : Filter by severity (critical, high, medium)}
        {--json : Output as JSON}';

    protected $description = '🛡️ Schema Guard: Detect forbidden field usage in codebase';

    private array $violations = [];
    private array $ambiguous = [];
    private array $stats = ['files_scanned' => 0, 'violations' => 0, 'ambiguous' => 0];
    private array $modelTableMap = [];

    public function handle(): int
    {
        $isJson = $this->option('json');

        if (!$isJson) {
            $this->info('');
            $this->info('🛡️  Schema Guard — Drift Detector');
            $this->info('─────────────────────────────────────────');
            $this->newLine();
        }

        $config = config('schema_guard');

        if (!$config || empty($config['forbidden_aliases'])) {
            $this->error('❌ config/schema_guard.php not found or empty.');
            return self::FAILURE;
        }

        $aliases = $config['forbidden_aliases'];
        $scanPaths = $config['scan_paths'] ?? [];
        $extensions = $config['scan_extensions'] ?? ['php'];
        $excludedFiles = $config['excluded_files'] ?? [];

        // Filter by table if specified
        $tableFilter = $this->option('table');
        if (!empty($tableFilter)) {
            $aliases = array_filter($aliases, fn($a) => in_array($a['table'], $tableFilter));
        }

        // Filter by severity if specified
        $severityFilter = $this->option('severity');
        if ($severityFilter) {
            $aliases = array_filter($aliases, fn($a) => $a['severity'] === $severityFilter);
        }

        if (empty($aliases)) {
            if (!$isJson) {
                $this->warn('⚠️  No matching aliases found for the given filters.');
            }
            return self::SUCCESS;
        }

        if (!$isJson) {
            $this->info('📋 Scanning ' . count($aliases) . ' forbidden alias rules...');
            $this->info('📁 Scan targets: ' . implode(', ', $scanPaths));
            $this->newLine();
        }

        // Collect all files to scan
        $files = $this->collectFiles($scanPaths, $extensions, $excludedFiles, $isJson);

        if (!$isJson) {
            $this->info('📄 Files to scan: ' . count($files));
            $this->newLine();

            $bar = $this->output->createProgressBar(count($files));
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        }

        $this->modelTableMap = $this->buildModelTableMap();

        foreach ($files as $file) {
            $this->scanFile($file, $aliases);
            if (!$isJson) {
                $bar->advance();
            }
        }

        if (!$isJson) {
            $bar->finish();
            $this->newLine(2);
        }

        // Output results
        if ($isJson) {
            $this->outputJson();
        } else {
            $this->outputHuman();
        }

        return $this->stats['violations'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function collectFiles(array $scanPaths, array $extensions, array $excludedFiles, bool $isJson = false): array
    {
        $basePath = base_path();
        $files = [];

        foreach ($scanPaths as $path) {
            $fullPath = $basePath . '/' . $path;

            if (!File::isDirectory($fullPath)) {
                if (!$isJson) {
                    $this->warn("  ⚠️  Directory not found: {$path}");
                }
                continue;
            }

            $allFiles = File::allFiles($fullPath);

            foreach ($allFiles as $file) {
                $relativePath = str_replace($basePath . '/', '', $file->getPathname());

                // Check exclusions
                if (in_array($relativePath, $excludedFiles)) {
                    continue;
                }

                // Check extensions
                $matchesExtension = false;
                foreach ($extensions as $ext) {
                    if (str_ends_with($file->getFilename(), '.' . $ext)) {
                        $matchesExtension = true;
                        break;
                    }
                }

                if ($matchesExtension) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return array_unique($files);
    }

    private function scanFile(string $filePath, array $aliases): void
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $relativePath = str_replace(base_path() . '/', '', $filePath);

        $this->stats['files_scanned']++;

        // Pre-compute which tables are relevant to this file
        $tablePresence = [];
        foreach ($aliases as $alias) {
            $table = $alias['table'];
            if (!isset($tablePresence[$table])) {
                $tablePresence[$table] = str_contains($content, "'{$table}'")
                    || str_contains($content, "\"{$table}\"")
                    || str_contains($content, "\\{$table}")
                    || str_contains($content, "table = '{$table}'");
            }
        }

        foreach ($aliases as $alias) {
            $wrongField = $alias['wrong_field'];
            $table = $alias['table'];

            // Build context-aware patterns for this alias
            $patterns = $this->buildPatterns($wrongField, $table, $tablePresence[$table] ?? false);

            foreach ($lines as $lineNum => $line) {
                $trimmedLine = trim($line);

                // Skip comments
                if (str_starts_with($trimmedLine, '//') || str_starts_with($trimmedLine, '*') || str_starts_with($trimmedLine, '/*')) {
                    continue;
                }

                foreach ($patterns as $patternInfo) {
                    if (preg_match($patternInfo['regex'], $line)) {
                        // Controlled STEP-3 rule: `name` is only actionable for `yayin_tipi_sablonlari`.
                        // All other table contexts are classified as ambiguous to prevent false positives.
                        if ($wrongField === 'name' && $table !== 'yayin_tipi_sablonlari') {
                            $this->ambiguous[] = [
                                'status'        => 'ambiguous',
                                'file'          => $relativePath,
                                'line'          => $lineNum + 1,
                                'table'         => $table,
                                'wrong_field'   => $wrongField,
                                'pattern'       => $patternInfo['name'],
                                'code'          => trim($line),
                                'inference'     => 'generic_name_outside_yayin_tipi_sablonlari',
                            ];
                            $this->stats['ambiguous']++;
                            continue;
                        }

                        $inference = $this->inferTableForLine($lines, $lineNum, $patternInfo, $table);

                        if (($patternInfo['requires_inference'] ?? false) && !$inference['confident']) {
                            $this->ambiguous[] = [
                                'status'        => 'ambiguous',
                                'file'          => $relativePath,
                                'line'          => $lineNum + 1,
                                'table'         => $table,
                                'wrong_field'   => $wrongField,
                                'pattern'       => $patternInfo['name'],
                                'code'          => trim($line),
                                'inference'     => $inference['reason'],
                            ];
                            $this->stats['ambiguous']++;
                            continue;
                        }

                        $confidence = $this->assessConfidence($line, $content, $table, $wrongField);

                        $this->violations[] = [
                            'file'          => $relativePath,
                            'line'          => $lineNum + 1,
                            'table'         => $table,
                            'wrong_field'   => $wrongField,
                            'correct_field' => $alias['correct_field'] ?? '(forbidden — no replacement)',
                            'severity'      => $alias['severity'],
                            'pattern'       => $patternInfo['name'],
                            'confidence'    => $confidence,
                            'status'        => 'violation',
                            'code'          => trim($line),
                            'note'          => $alias['note'],
                        ];
                        $this->stats['violations']++;
                    }
                }
            }
        }
    }

    /**
     * Generic field names that produce too many false positives in array/property patterns.
     * These are only flagged if the table name IS in the file context.
     * display_order: exists on property_features, yayin_tipi_sablonlari, feature_assignments, photos, etc.
     * group_name: exists on property_features table.
     */
    private const GENERIC_FIELDS = ['slug', 'name', 'description', 'type', 'latitude', 'longitude', 'display_order', 'group_name'];

    private function buildPatterns(string $field, string $table, bool $tableInFile): array
    {
        // Escape for regex
        $f = preg_quote($field, '/');
        $t = preg_quote($table, '/');

        $isGeneric = in_array($field, self::GENERIC_FIELDS, true);

        $patterns = [
            // SQL-style: table.field in queries — always high signal
            [
                'name'  => 'table_dot_field',
                'regex' => "/['\"]?{$t}['\"]?\s*\.\s*['\"]?{$f}['\"]?/i",
                'requires_inference' => false,
            ],
            // Select arrays: 'table.field' inside select([...])
            [
                'name'  => 'select_array',
                'regex' => "/->select\s*\(\s*\[.*['\"]({$t}\.){$f}['\"]/i",
                'requires_inference' => false,
            ],
            // Validation: unique:table,field or exists:table,field — always high signal
            [
                'name'  => 'validation_rule',
                'regex' => "/(unique|exists)\s*:\s*{$t}\s*,\s*{$f}/i",
                'requires_inference' => false,
            ],
            // Direct DB::table('table')->...('field')
            [
                'name'  => 'db_table_query',
                'regex' => "/DB::table\s*\(\s*['\"]({$t})['\"].*['\"]({$f})['\"]/i",
                'requires_inference' => false,
            ],
        ];

        // For non-generic fields, add broader patterns regardless of table context
        // For generic fields, only add broader patterns if table is referenced in file
        if (!$isGeneric || $tableInFile) {
            $patterns[] = [
                'name'  => 'query_builder_qualified',
                'regex' => "/->(where|orWhere|orderBy|orderByDesc|groupBy|having|pluck|value|sum|avg|count|min|max|whereNotNull|whereNull|whereIn|whereNotIn|whereBetween)\s*\(\s*['\"]{$t}\.{$f}['\"]/i",
                'requires_inference' => false,
            ];
            $patterns[] = [
                'name'  => 'query_builder_unqualified',
                'regex' => "/->(where|orWhere|orderBy|orderByDesc|groupBy|having|pluck|value|sum|avg|count|min|max|whereNotNull|whereNull|whereIn|whereNotIn|whereBetween)\s*\(\s*['\"]{$f}['\"]/i",
                'requires_inference' => true,
            ];
            $patterns[] = [
                'name'  => 'eager_load_select',
                'regex' => "/['\"][a-zA-Z]+:[a-z_,]*\b{$f}\b/i",
                'requires_inference' => true,
            ];
        }

        // Array/property access — only for unique field names OR when table context exists
        if (!$isGeneric && $tableInFile) {
            $patterns[] = [
                'name'  => 'array_or_property',
                'regex' => "/\[\s*['\"]({$f})['\"]\s*\]/",
                'requires_inference' => true,
            ];
        }

        return $patterns;
    }

    private function assessConfidence(string $line, string $fileContent, string $table, string $field): string
    {
        $score = 0;

        // Direct table.field reference = very high
        if (preg_match('/' . preg_quote($table, '/') . '\s*\.\s*' . preg_quote($field, '/') . '/', $line)) {
            $score += 50;
        }

        // Table name appears in the file = higher confidence
        if (str_contains($fileContent, "'{$table}'") || str_contains($fileContent, "\"{$table}\"")) {
            $score += 20;
        }

        // Query builder methods = higher confidence
        if (preg_match('/->(select|where|orderBy|groupBy|pluck|value)\s*\(/', $line)) {
            $score += 15;
        }

        // Validation rule with table.field = very high
        if (preg_match('/(unique|exists)\s*:\s*' . preg_quote($table, '/') . '\s*,\s*' . preg_quote($field, '/') . '/', $line)) {
            $score += 40;
        }

        // Is inside a blade file = moderate
        if (str_contains($line, '{{') || str_contains($line, '{!!')) {
            $score += 5;
        }

        if ($score >= 40) {
            return 'HIGH';
        }
        if ($score >= 15) {
            return 'MEDIUM';
        }
        return 'LOW';
    }

    private function outputHuman(): void
    {
        if (empty($this->violations)) {
            $this->info('✅ No schema drift detected. All fields align with DB truth.');
            $this->newLine();
            $this->info("📊 Files scanned: {$this->stats['files_scanned']}");
            if ($this->stats['ambiguous'] > 0) {
                $this->warn("ℹ️  Ambiguous matches skipped: {$this->stats['ambiguous']}");
            }
            return;
        }

        $this->error("❌ {$this->stats['violations']} schema drift violation(s) detected!");
        $this->newLine();

        // Group by table
        $grouped = [];
        foreach ($this->violations as $v) {
            $grouped[$v['table']][] = $v;
        }

        foreach ($grouped as $table => $tableViolations) {
            $this->warn("┌── Table: {$table} (" . count($tableViolations) . " issue(s))");

            foreach ($tableViolations as $v) {
                $severityIcon = match ($v['severity']) {
                    'critical' => '🔴',
                    'high'     => '🟠',
                    'medium'   => '🟡',
                    default    => '⚪',
                };

                $correctDisplay = $v['correct_field'] ?: '(no replacement)';

                $this->line("│  {$severityIcon} [{$v['severity']}] [{$v['confidence']}]");
                $this->line("│     File: {$v['file']}:{$v['line']}");
                $this->line("│     Wrong: \"{$v['wrong_field']}\" → Correct: \"{$correctDisplay}\"");
                $this->line("│     Pattern: {$v['pattern']}");
                $this->line("│     Code: {$v['code']}");
                $this->line("│     Note: {$v['note']}");
                $this->line('│');
            }

            $this->line('└──');
            $this->newLine();
        }

        // Summary
        $this->info('📊 Summary');
        $this->info("   Files scanned: {$this->stats['files_scanned']}");
        $this->info("   Total violations: {$this->stats['violations']}");
        $this->info("   Ambiguous skipped: {$this->stats['ambiguous']}");

        $bySeverity = [];
        foreach ($this->violations as $v) {
            $bySeverity[$v['severity']] = ($bySeverity[$v['severity']] ?? 0) + 1;
        }
        foreach ($bySeverity as $sev => $count) {
            $this->info("   {$sev}: {$count}");
        }
    }

    private function outputJson(): void
    {
        $this->line(json_encode([
            'drift_detected' => !empty($this->violations),
            'stats'          => $this->stats,
            'violations'     => $this->violations,
            'ambiguous'      => $this->ambiguous,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function buildModelTableMap(): array
    {
        $map = [];
        $modelPath = base_path('app/Models');

        if (!File::isDirectory($modelPath)) {
            return $map;
        }

        foreach (File::allFiles($modelPath) as $file) {
            $content = File::get($file->getPathname());

            if (!preg_match('/class\s+([A-Za-z0-9_]+)/', $content, $classMatch)) {
                continue;
            }

            $className = $classMatch[1];
            $tableName = null;

            if (preg_match('/protected\s+\$table\s*=\s*[\'\"]([^\'\"]+)[\'\"]\s*;/', $content, $tableMatch)) {
                $tableName = $tableMatch[1];
            }

            if (!$tableName) {
                $snake = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
                $tableName = str_ends_with($snake, 's') ? $snake : $snake . 's';
            }

            $map[$className] = $tableName;
        }

        return $map;
    }

    private function inferTableForLine(array $lines, int $lineNum, array $patternInfo, string $targetTable): array
    {
        if (($patternInfo['requires_inference'] ?? false) === false) {
            return ['confident' => true, 'reason' => 'explicit_table_binding'];
        }

        $windowStart = max(0, $lineNum - 8);
        $window = implode("\n", array_slice($lines, $windowStart, ($lineNum - $windowStart) + 1));
        $line = $lines[$lineNum] ?? '';

        // Model::query()/Model::where() chain context
        if (preg_match('/([A-Za-z0-9_\\\\]+)::(query|where|select|with|orderBy|join|from)\s*\(/', $window, $modelMatch)) {
            $fullModel = $modelMatch[1];
            $shortModel = str_contains($fullModel, '\\')
                ? substr($fullModel, strrpos($fullModel, '\\') + 1)
                : $fullModel;

            $inferredTable = $this->modelTableMap[$shortModel] ?? null;
            if ($inferredTable === $targetTable) {
                return ['confident' => true, 'reason' => "model_context:{$shortModel}"];
            }

            return ['confident' => false, 'reason' => 'model_context_other_table'];
        }

        // Variable builder context: $query = Model::query(); ... $query->where('field')
        if (preg_match('/\$([A-Za-z_][A-Za-z0-9_]*)\s*=\s*([A-Za-z0-9_\\\\]+)::(query|where|select|with)\s*\(/', $window, $varModelMatch)) {
            $fullModel = $varModelMatch[2];
            $shortModel = str_contains($fullModel, '\\')
                ? substr($fullModel, strrpos($fullModel, '\\') + 1)
                : $fullModel;

            $inferredTable = $this->modelTableMap[$shortModel] ?? null;
            if ($inferredTable === $targetTable && preg_match('/\$' . preg_quote($varModelMatch[1], '/') . '->/', $line)) {
                return ['confident' => true, 'reason' => "variable_model_context:{$shortModel}"];
            }
        }

        // Variable builder context (extended backward scan):
        // line may be far from variable assignment in fluent chains.
        $lineVar = null;
        if (preg_match('/\$([A-Za-z_][A-Za-z0-9_]*)->/', $line, $lineVarMatch)) {
            $lineVar = $lineVarMatch[1];
        }

        // Fluent chain continuation line (e.g. ->orderBy(...)) may not contain variable itself.
        if (!$lineVar && preg_match('/^\s*->/', $line)) {
            for ($j = $lineNum - 1; $j >= max(0, $lineNum - 8); $j--) {
                $prevLine = $lines[$j] ?? '';

                // Example: $features = $featuresQuery
                if (preg_match('/=\s*\$([A-Za-z_][A-Za-z0-9_]*)\s*$/', trim($prevLine), $rhsVarMatch)) {
                    $lineVar = $rhsVarMatch[1];
                    break;
                }

                // Example: $featuresQuery->where(...)
                if (preg_match('/\$([A-Za-z_][A-Za-z0-9_]*)->/', $prevLine, $prevVarMatch)) {
                    $lineVar = $prevVarMatch[1];
                    break;
                }
            }
        }

        if ($lineVar) {
            $scanStart = max(0, $lineNum - 80);
            for ($i = $lineNum; $i >= $scanStart; $i--) {
                $scanLine = $lines[$i] ?? '';
                if (preg_match('/\$' . preg_quote($lineVar, '/') . '\s*=\s*([A-Za-z0-9_\\\\]+)::(query|where|select|with)\s*\(/', $scanLine, $scanMatch)) {
                    $fullModel = $scanMatch[1];
                    $shortModel = str_contains($fullModel, '\\')
                        ? substr($fullModel, strrpos($fullModel, '\\') + 1)
                        : $fullModel;

                    $inferredTable = $this->modelTableMap[$shortModel] ?? null;
                    if ($inferredTable === $targetTable) {
                        return ['confident' => true, 'reason' => "variable_model_context_extended:{$shortModel}"];
                    }

                    return ['confident' => false, 'reason' => 'model_context_other_table'];
                }
            }
        }

        // Explicit from/join/table.column anywhere in nearby query block
        if (preg_match('/\b' . preg_quote($targetTable, '/') . '\b\s*\./i', $window)
            || preg_match('/\b(from|join|leftJoin|rightJoin|joinSub)\s*\(\s*[\'\"]' . preg_quote($targetTable, '/') . '[\'\"]/i', $window)
            || preg_match('/DB::table\s*\(\s*[\'\"]' . preg_quote($targetTable, '/') . '[\'\"]/i', $window)) {
            return ['confident' => true, 'reason' => 'query_block_mentions_target_table'];
        }

        // Relation context: relation call + target table join/from in same block
        if (preg_match('/->with\s*\(/', $window) && preg_match('/\b' . preg_quote($targetTable, '/') . '\b/i', $window)) {
            return ['confident' => true, 'reason' => 'relation_block_mentions_target_table'];
        }

        return ['confident' => false, 'reason' => 'unable_to_infer_table'];
    }
}
