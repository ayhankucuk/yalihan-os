<?php

namespace App\Console\Commands;

use App\Services\Ydl\YdlContextReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * YDL Phase 3: Agent Context Reader.
 *
 * Usage:
 *   php artisan ydl:context              # Markdown (default)
 *   php artisan ydl:context --json       # JSON format
 *   php artisan ydl:context --authority # Authority summary only
 *   php artisan ydl:context --inject-claude # Update CLAUDE.md preamble (idempotent)
 *
 * YDL v1 Phase 3 — Agent Context Integration
 */
class YdlContextCommand extends Command
{
    protected $signature = 'ydl:context
                            {--json : Output as JSON instead of Markdown}
                            {--authority : Show only authority level and blockers}
                            {--inject-claude : Inject YDL state section into CLAUDE.md preamble}';

    protected $description = 'YDL Phase 3: Show current YDL state for agent context injection';

    public function handle(): int
    {
        $reader = new YdlContextReader();

        // ── Inject into CLAUDE.md ──────────────────────────────────────
        if ($this->option('inject-claude')) {
            return $this->injectClaude($reader);
        }

        // ── Authority-only output ───────────────────────────────────────
        if ($this->option('authority')) {
            $this->line($reader->toAuthoritySummary());
            return self::SUCCESS;
        }

        // ── JSON output ───────────────────────────────────────────────
        if ($this->option('json')) {
            $this->line($reader->toJson());
            return self::SUCCESS;
        }

        // ── Markdown output (default) ─────────────────────────────────
        $this->line($reader->toMarkdown());

        $ctx = $reader->read();
        if ($ctx->sprint === '') {
            $this->newLine();
            $this->warn('  No active sprint. Run: php artisan ydl:state');
        }

        return self::SUCCESS;
    }

    private function injectClaude(YdlContextReader $reader): int
    {
        $this->info('Injecting YDL state into CLAUDE.md...');

        $claudePath = base_path('CLAUDE.md');

        if (!File::exists($claudePath)) {
            $this->error("CLAUDE.md not found at: {$claudePath}");
            return self::FAILURE;
        }

        $existing = File::get($claudePath);

        // Build the YDL section to inject after the first heading
        $markdown = $reader->toMarkdown();

        // Find first H2 heading (##) in the file to inject after it
        // If no H2 found, inject after the first H1 (#)
        $pattern = '/^(# .+\n)/m';
        $count = 0;
        $injected = preg_replace($pattern, '$1' . "\n" . $markdown, $existing, 1, $count);

        if ($count === 0) {
            // No heading found — prepend to file
            $injected = $markdown . "\n\n" . $existing;
            $this->warn('No H2 heading found in CLAUDE.md — prepending YDL section');
        }

        // Also handle case where YDL section already exists — make idempotent
        // Remove ALL existing "## YDL State" sections (any delimiter: em dash, en dash, etc.)
        $injected = preg_replace(
            '/\n## YDL State[^\n]*.*?(?=\n## [^\n]|\n# [^\n]|\Z)/s',
            '',
            $injected
        );

        // Re-inject after first heading (H1)
        $injected = preg_replace($pattern, '$1' . "\n" . $markdown, $injected, 1, $count);

        File::put($claudePath, $injected);

        $this->info("Updated: {$claudePath}");
        $this->newLine();
        $this->line($markdown);

        return self::SUCCESS;
    }
}
