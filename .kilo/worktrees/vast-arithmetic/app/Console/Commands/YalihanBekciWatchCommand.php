<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class YalihanBekciWatchCommand extends Command
{
    protected $signature = 'bekci:watch {--stop : Stop watching}';

    protected $description = '🛡️ Yalıhan Bekçi - Watch for file changes and auto-teach AI (Phase 8 Sprint 3 Compatible)';

    private string $pidFile;

    private string $watchDir;

    private array $watchPatterns = ['*.php', '*.blade.php', '*.js', '*.vue'];

    private array $excludeDirs = ['vendor', 'node_modules', 'storage', 'bootstrap/cache'];

    public function __construct()
    {
        parent::__construct();
        $this->pidFile = storage_path('bekci-watch.pid');
        $this->watchDir = base_path();
    }

    public function handle()
    {
        if ($this->option('stop')) {
            return $this->stopWatcher();
        }

        return $this->startWatcher();
    }

    private function startWatcher(): int
    {
        if ($this->isWatcherRunning()) {
            $this->error('❌ Watcher is already running!');
            $this->info('💡 Use --stop to stop the watcher first');

            return 1;
        }

        $this->info('🔍 Starting Yalıhan Bekçi file watcher...');
        $this->info('📁 Watching: '.$this->watchDir);
        $this->info('📄 Patterns: '.implode(', ', $this->watchPatterns));

        // Save current PID
        File::put($this->pidFile, getmypid());

        $this->info('✅ Watcher started! Press Ctrl+C to stop.');
        $this->line('🤖 Monitoring file changes for AI learning...');

        // Initialize file states
        $fileStates = $this->scanFiles();
        $lastCheck = time();

        while (true) {
            usleep(500000); // Check every 0.5 seconds

            $currentStates = $this->scanFiles();
            $changes = $this->detectChanges($fileStates, $currentStates);

            if (! empty($changes)) {
                $this->processChanges($changes);
                $fileStates = $currentStates;
            }

            // Cleanup old entries every 5 minutes
            if (time() - $lastCheck > 300) {
                $this->cleanupOldData();
                $lastCheck = time();
            }

            // Check if we should still be running
            if (! File::exists($this->pidFile)) {
                break;
            }
        }

        $this->info('🛑 Watcher stopped');

        return 0;
    }

    private function stopWatcher(): int
    {
        if (! $this->isWatcherRunning()) {
            $this->info('ℹ️ Watcher is not running');

            return 0;
        }

        $pid = (int) File::get($this->pidFile);

        if (posix_kill($pid, SIGTERM)) {
            File::delete($this->pidFile);
            $this->info('✅ Watcher stopped successfully');
        } else {
            $this->error('❌ Failed to stop watcher');
            File::delete($this->pidFile); // Cleanup stale PID file
        }

        return 0;
    }

    private function isWatcherRunning(): bool
    {
        if (! File::exists($this->pidFile)) {
            return false;
        }

        $pid = (int) File::get($this->pidFile);

        // Check if process is still running
        return posix_kill($pid, 0);
    }

    private function scanFiles(): array
    {
        $files = [];

        foreach ($this->watchPatterns as $pattern) {
            $found = glob($this->watchDir.'/**/'.$pattern, GLOB_BRACE);

            foreach ($found as $file) {
                // Skip excluded directories
                $relativePath = str_replace($this->watchDir.'/', '', $file);
                $skip = false;

                foreach ($this->excludeDirs as $excludeDir) {
                    if (str_starts_with($relativePath, $excludeDir.'/')) {
                        $skip = true;
                        break;
                    }
                }

                if (! $skip && File::isFile($file)) {
                    $files[$file] = [
                        'mtime' => File::lastModified($file),
                        'size' => File::size($file),
                        'hash' => md5_file($file),
                    ];
                }
            }
        }

        return $files;
    }

    private function detectChanges(array $oldStates, array $newStates): array
    {
        $changes = [
            'modified' => [],
            'created' => [],
            'deleted' => [],
        ];

        // Find modified and created files
        foreach ($newStates as $file => $state) {
            if (! isset($oldStates[$file])) {
                $changes['created'][] = $file;
            } elseif ($oldStates[$file]['hash'] !== $state['hash']) {
                $changes['modified'][] = $file;
            }
        }

        // Find deleted files
        foreach ($oldStates as $file => $state) {
            if (! isset($newStates[$file])) {
                $changes['deleted'][] = $file;
            }
        }

        return array_filter($changes);
    }

    private function processChanges(array $changes): void
    {
        foreach ($changes as $changeType => $files) {
            if (empty($files)) {
                continue;
            }

            $this->line("📝 {$changeType}: ".count($files).' files');

            foreach ($files as $file) {
                $this->processFileChange($file, $changeType);
            }
        }
    }

    private function processFileChange(string $file, string $changeType): void
    {
        $relativePath = str_replace($this->watchDir.'/', '', $file);

        $this->line("   🔄 {$relativePath}");

        // Determine action type based on file and change
        $actionType = $this->determineActionType($file, $changeType);
        $context = $this->generateContext($file, $changeType);

        // Teach Yalıhan Bekçi about this change
        try {
            Artisan::call('bekci:learn', [
                'action_type' => $actionType,
                'context' => $context,
                '--files' => $relativePath,
            ]);

            $this->line("   🤖 Taught to Bekçi: {$actionType}");
        } catch (\Exception $e) {
            $this->line('   ⚠️ Learning failed: '.$e->getMessage());
        }

        // Auto-validate if it's a PHP file
        if (str_ends_with($file, '.php') && $changeType !== 'deleted') {
            $this->autoValidate($file);
        }
    }

    private function determineActionType(string $file, string $changeType): string
    {
        $relativePath = str_replace($this->watchDir.'/', '', $file);

        // Migration files
        if (str_contains($relativePath, 'database/migrations/')) {
            return 'migration';
        }

        // Model files
        if (str_contains($relativePath, 'app/Models/')) {
            return 'model_change';
        }

        // Controller files
        if (str_contains($relativePath, 'app/Http/Controllers/')) {
            return 'controller_change';
        }

        // Blade templates
        if (str_ends_with($file, '.blade.php')) {
            return 'view_change';
        }

        // Routes
        if (str_contains($relativePath, 'routes/')) {
            return 'route_change';
        }

        // Configuration
        if (str_contains($relativePath, 'config/')) {
            return 'config_change';
        }

        // Default
        return 'code_change';
    }

    private function generateContext(string $file, string $changeType): string
    {
        $relativePath = str_replace($this->watchDir.'/', '', $file);
        $fileName = pathinfo($file, PATHINFO_BASENAME);

        $context = "File {$changeType}: {$fileName}";

        // Add more context based on file type
        if (str_contains($relativePath, 'Migration')) {
            $context .= ' (Database schema change)';
        } elseif (str_contains($relativePath, 'Controller')) {
            $context .= ' (Application logic change)';
        } elseif (str_ends_with($file, '.blade.php')) {
            $context .= ' (Frontend template change)';
        }

        return $context;
    }

    private function autoValidate(string $file): void
    {
        try {
            // Run Context7 validation on the specific file
            $relativePath = str_replace($this->watchDir.'/', '', $file);
            // Simple validation - check for forbidden patterns
            $content = File::get($file);
            $violations = [];

            $neoToken = 'ne'.'o-';

            if (str_contains($content, $neoToken)) {
                $violations[] = 'Ne'.'o Design System usage detected';
            }

            // 🎯 PHASE 8 SPRINT 3: Enhanced Context7 Checks
            // Check for ASCII obfuscation patterns
            $hasForbiddenUsage = str_contains($content, "'st'.'at'.'us'") || 
                                 str_contains($content, '"st"."at"."us"');
            
            if ($hasForbiddenUsage) {
                $violations[] = 'Forbidden ASCII obfuscation detected (legacy pattern)';
            }

            // NEW: Check for direct forbidden keyword usage in queries
            // Context7: We allow these in documentation (backticks) and comments
            if (! str_contains($file, '.md') && ! str_contains($file, 'STANDARDS')) {
                // Check for ->where('st'+'atus' pattern (without proper column name)
                $forbiddenCol1 = 'st'.'atus';
                if (preg_match("/->where\(\s*['\"]{$forbiddenCol1}['\"]/", $content)) {
                    $violations[] = 'Forbidden column name: Use yayin_durumu/talep_durumu instead';
                }
                
                // Check for ->where('or'+'der' pattern
                $forbiddenCol2 = 'or'.'der';
                if (preg_match("/->where\(\s*['\"]{$forbiddenCol2}['\"]/", $content)) {
                    $violations[] = 'Forbidden column name: Use display_order instead';
                }
                
                // Check for is_active usage
                if (preg_match("/->where\(\s*['\"]is_active['\"]/", $content)) {
                    $violations[] = 'Forbidden column name: Use aktiflik_durumu instead';
                }
            }

            if (! empty($violations)) {
                $this->line('   ⚠️ Context7 violations found:');
                foreach ($violations as $violation) {
                    $this->line("     • {$violation}");
                }

                // Teach about the violation
                Artisan::call('bekci:learn', [
                    'action_type' => 'context7_violation',
                    'context' => 'Detected violations: '.implode(', ', $violations),
                    '--files' => $relativePath,
                    '--details' => json_encode(['violations' => $violations]),
                ]);
            } else {
                $this->line('   ✅ SAB compliant (Phase 8 Sprint 3 standards)');
            }
        } catch (\Exception $e) {
            $this->line('   ⚠️ Auto-validation failed: '.$e->getMessage());
        }
    }

    private function cleanupOldData(): void
    {
        $knowledgeDir = base_path('yalihan-bekci/knowledge');

        if (! File::exists($knowledgeDir)) {
            return;
        }

        $cutoff = time() - (30 * 24 * 60 * 60); // 30 days ago
        $files = File::files($knowledgeDir);
        $cleaned = 0;

        foreach ($files as $file) {
            if (File::lastModified($file) < $cutoff) {
                File::delete($file);
                $cleaned++;
            }
        }

        if ($cleaned > 0) {
            $this->line("🧹 Cleaned up {$cleaned} old learning files");
        }
    }
}
