<?php

namespace App\Console\Commands\Governance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * REPO-GOV-02B: Trace Analysis Command
 *
 * Analyzes Telescope trace files to generate runtime flow maps
 * and classify authority propagation patterns.
 */
class TraceAnalysisCommand extends Command
{
    protected $signature = 'governance:analyze-traces
                            {--output= : Output directory for analysis results}
                            {--format=md : Output format (md, json)}';

    protected $description = 'Analyze Telescope trace files for runtime authority flow mapping';

    protected array $stats = [
        'total_entries' => 0,
        'exceptions' => 0,
        'events' => 0,
        'jobs' => 0,
        'cache_operations' => 0,
        'queries' => 0,
        'models' => 0,
    ];

    protected array $exceptionPatterns = [];
    protected array $eventChains = [];
    protected array $cachePatterns = [];
    protected array $queryPatterns = [];

    public function handle(): int
    {
        $this->info('🔍 REPO-GOV-02B: Analyzing runtime traces...');
        $this->newLine();

        $storagePath = storage_path('telescope');

        if (!File::exists($storagePath)) {
            $this->error("Trace storage directory not found: {$storagePath}");
            return 1;
        }

        $traceFiles = File::glob("{$storagePath}/trace_*.json");

        if (empty($traceFiles)) {
            $this->warn('No trace files found. Run some requests with TELESCOPE_ENABLED=true first.');
            return 1;
        }

        $this->info("Found " . count($traceFiles) . " trace files");
        $this->newLine();

        // Analyze each trace file
        $bar = $this->output->createProgressBar(count($traceFiles));
        $bar->start();

        foreach ($traceFiles as $file) {
            $this->analyzeTraceFile($file);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Generate analysis report
        $outputDir = $this->option('output') ?: 'docs/governance/traces/' . now()->format('Y-m-d');
        $this->generateReport($outputDir);

        $this->info('✅ Trace analysis complete!');
        $this->info("📊 Results saved to: {$outputDir}");

        return 0;
    }

    protected function analyzeTraceFile(string $file): void
    {
        $data = json_decode(File::get($file), true);

        if (!isset($data['entries'])) {
            return;
        }

        foreach ($data['entries'] as $entry) {
            $this->stats['total_entries']++;

            $entryType = $entry['entry_type'] ?? $entry['type'] ?? 'unknown'; // Support both old and new format

            match($entryType) {
                'exception' => $this->analyzeException($entry),
                'event' => $this->analyzeEvent($entry),
                'job' => $this->analyzeJob($entry),
                'cache' => $this->analyzeCache($entry),
                'query' => $this->analyzeQuery($entry),
                'model' => $this->analyzeModel($entry),
                default => null,
            };
        }
    }

    protected function analyzeException(array $entry): void
    {
        $this->stats['exceptions']++;

        $content = $entry['content'] ?? [];
        $class = $content['class'] ?? 'Unknown';
        $file = $content['file'] ?? 'Unknown';
        $line = $content['line'] ?? 0;

        $key = "{$class}@{$file}:{$line}";

        if (!isset($this->exceptionPatterns[$key])) {
            $this->exceptionPatterns[$key] = [
                'class' => $class,
                'file' => $file,
                'line' => $line,
                'count' => 0,
                'message' => $content['message'] ?? '',
            ];
        }

        $this->exceptionPatterns[$key]['count']++;
    }

    protected function analyzeEvent(array $entry): void
    {
        $this->stats['events']++;

        $content = $entry['content'] ?? [];
        $event = $content['name'] ?? 'Unknown';

        if (!isset($this->eventChains[$event])) {
            $this->eventChains[$event] = [
                'name' => $event,
                'count' => 0,
                'listeners' => [],
            ];
        }

        $this->eventChains[$event]['count']++;

        if (isset($content['listeners'])) {
            foreach ($content['listeners'] as $listener) {
                if (!in_array($listener, $this->eventChains[$event]['listeners'])) {
                    $this->eventChains[$event]['listeners'][] = $listener;
                }
            }
        }
    }

    protected function analyzeJob(array $entry): void
    {
        $this->stats['jobs']++;
    }

    protected function analyzeCache(array $entry): void
    {
        $this->stats['cache_operations']++;

        $content = $entry['content'] ?? [];
        $operationType = $content['type'] ?? 'unknown'; // Renamed from $type to $operationType
        $key = $content['key'] ?? 'unknown';

        if (!isset($this->cachePatterns[$key])) {
            $this->cachePatterns[$key] = [
                'key' => $key,
                'operations' => [],
            ];
        }

        if (!isset($this->cachePatterns[$key]['operations'][$operationType])) {
            $this->cachePatterns[$key]['operations'][$operationType] = 0;
        }

        $this->cachePatterns[$key]['operations'][$operationType]++;
    }

    protected function analyzeQuery(array $entry): void
    {
        $this->stats['queries']++;

        $content = $entry['content'] ?? [];
        $sql = $content['sql'] ?? '';

        // Classify query type
        $queryType = 'unknown'; // Renamed from $type to $queryType
        if (preg_match('/^SELECT/i', $sql)) {
            $queryType = 'select';
        } elseif (preg_match('/^INSERT/i', $sql)) {
            $queryType = 'insert';
        } elseif (preg_match('/^UPDATE/i', $sql)) {
            $queryType = 'update';
        } elseif (preg_match('/^DELETE/i', $sql)) {
            $queryType = 'delete';
        }

        if (!isset($this->queryPatterns[$queryType])) {
            $this->queryPatterns[$queryType] = 0;
        }

        $this->queryPatterns[$queryType]++;
    }

    protected function analyzeModel(array $entry): void
    {
        $this->stats['models']++;
    }

    protected function generateReport(string $outputDir): void
    {
        File::ensureDirectoryExists($outputDir);

        $format = $this->option('format');

        if ($format === 'json') {
            $this->generateJsonReport($outputDir);
        } else {
            $this->generateMarkdownReport($outputDir);
        }
    }

    protected function generateMarkdownReport(string $outputDir): void
    {
        $report = $this->buildMarkdownReport();
        File::put("{$outputDir}/trace-analysis.md", $report);
    }

    protected function generateJsonReport(string $outputDir): void
    {
        $data = [
            'timestamp' => now()->toIso8601String(),
            'stats' => $this->stats,
            'exceptions' => array_values($this->exceptionPatterns),
            'events' => array_values($this->eventChains),
            'cache' => array_values($this->cachePatterns),
            'queries' => $this->queryPatterns,
        ];

        File::put("{$outputDir}/trace-analysis.json", json_encode($data, JSON_PRETTY_PRINT));
    }

    protected function buildMarkdownReport(): string
    {
        $md = "# REPO-GOV-02B: Runtime Trace Analysis\n\n";
        $md .= "**Generated:** " . now()->toIso8601String() . "\n\n";
        $md .= "---\n\n";

        // Statistics
        $md .= "## Summary Statistics\n\n";
        $md .= "| Metric | Count |\n";
        $md .= "|--------|-------|\n";
        foreach ($this->stats as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));
            $md .= "| {$label} | " . number_format($value) . " |\n";
        }
        $md .= "\n---\n\n";

        // Exception Patterns
        $md .= "## Exception Patterns\n\n";
        if (empty($this->exceptionPatterns)) {
            $md .= "*No exceptions traced*\n\n";
        } else {
            $md .= "**Total Unique Patterns:** " . count($this->exceptionPatterns) . "\n\n";

            // Sort by count
            uasort($this->exceptionPatterns, fn($a, $b) => $b['count'] <=> $a['count']);

            $md .= "| Exception | File | Count |\n";
            $md .= "|-----------|------|-------|\n";

            foreach (array_slice($this->exceptionPatterns, 0, 20) as $pattern) {
                $class = basename(str_replace('\\', '/', $pattern['class']));
                $file = basename($pattern['file']);
                $md .= "| `{$class}` | `{$file}:{$pattern['line']}` | {$pattern['count']} |\n";
            }
        }
        $md .= "\n---\n\n";

        // Event Chains
        $md .= "## Event Orchestration\n\n";
        if (empty($this->eventChains)) {
            $md .= "*No events traced*\n\n";
        } else {
            $md .= "**Total Events:** " . count($this->eventChains) . "\n\n";

            foreach ($this->eventChains as $event) {
                $md .= "### `{$event['name']}`\n\n";
                $md .= "- **Dispatched:** {$event['count']} times\n";
                $md .= "- **Listeners:** " . count($event['listeners']) . "\n\n";

                if (!empty($event['listeners'])) {
                    foreach ($event['listeners'] as $listener) {
                        $md .= "  - `{$listener}`\n";
                    }
                    $md .= "\n";
                }
            }
        }
        $md .= "\n---\n\n";

        // Cache Patterns
        $md .= "## Cache Authority Patterns\n\n";
        if (empty($this->cachePatterns)) {
            $md .= "*No cache operations traced*\n\n";
        } else {
            $md .= "**Total Cache Keys:** " . count($this->cachePatterns) . "\n\n";

            $md .= "| Key | Operations |\n";
            $md .= "|-----|------------|\n";

            foreach (array_slice($this->cachePatterns, 0, 20) as $pattern) {
                $ops = [];
                foreach ($pattern['operations'] as $type => $count) {
                    $ops[] = "{$type}:{$count}";
                }
                $md .= "| `{$pattern['key']}` | " . implode(', ', $ops) . " |\n";
            }
        }
        $md .= "\n---\n\n";

        // Query Patterns
        $md .= "## Database Query Patterns\n\n";
        if (empty($this->queryPatterns)) {
            $md .= "*No queries traced*\n\n";
        } else {
            $md .= "| Type | Count |\n";
            $md .= "|------|-------|\n";

            foreach ($this->queryPatterns as $type => $count) {
                $md .= "| " . strtoupper($type) . " | " . number_format($count) . " |\n";
            }
        }

        return $md;
    }
}
