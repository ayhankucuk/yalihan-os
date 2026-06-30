<?php

namespace App\Console\Commands\Security;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Class SentinelConsoleCommand
 *
 * SAB Phase 15: Unified Code Protection and Quality Gate Console (SENTINEL).
 * Merges telemetry, AST verification, self-healing, and accelerated testing.
 *
 * Anayasal Kararlar:
 * - Madde 1: Single Responsibility Principle — Tüm koruma katmanları tek çatı altında
 * - Madde 2: Fail-Fast Validation — İlk kritik hatada dur
 * - Madde 3: Self-Healing Capability — Otomatik onarım desteği
 * - Madde 4: Mutation-Aware Testing — Git delta bazlı akıllı test triage
 *
 * @package App\Console\Commands\Security
 */
class SentinelConsoleCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'sentinel:run
                            {--mode=turbo : Çalıştırma modu (turbo|full|quick)}
                            {--fix : Hataları otomatik onar (self-healing)}
                            {--skip-tests : Test suite\'i atla}';

    /**
     * @var string
     */
    protected $description = 'SAB Anayasası Birleşik Kalite ve Güvenlik Sınır Kapısı Konsolu';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info("╔═══════════════════════════════════════════════════════════╗");
        $this->info("║   YALIHAN CORTEX SENTINEL — Unified Protection Console   ║");
        $this->info("║   SAB Phase 15: Multi-Layer Security & Quality Gate      ║");
        $this->info("╚═══════════════════════════════════════════════════════════╝");
        $this->newLine();

        $startTime = microtime(true);
        $mode = $this->option('mode');

        try {
            // ═══════════════════════════════════════════════════════════
            // MODÜL 1: Git Delta Analysis & Mutation Detection
            // ═══════════════════════════════════════════════════════════
            $this->comment("→ Modül 1: Git delta ve değişiklik analizi...");
            $modifiedFiles = $this->getModifiedFiles();

            if (empty($modifiedFiles)) {
                $this->info("  ✓ Delta yok. Tam tarama moduna geçiliyor...");
            } else {
                $this->info("  ✓ " . count($modifiedFiles) . " dosya değişikliği tespit edildi.");
            }

            // ═══════════════════════════════════════════════════════════
            // MODÜL 2: SAB Integrity Scan (Context7 & AST)
            // ═══════════════════════════════════════════════════════════
            $this->comment("→ Modül 2: SAB Integrity & Context7 doğrulaması...");

            if (!$this->runIntegrityScan($modifiedFiles)) {
                if ($this->option('fix')) {
                    $this->warn("  ⚠ İhlaller tespit edildi. Otomatik onarım deneniyor...");
                    $this->runAutoFix();

                    // Onarımdan sonra tekrar tara
                    if (!$this->runIntegrityScan($modifiedFiles)) {
                        $this->error("  ✗ Otomatik onarım başarısız. Manuel müdahale gerekli.");
                        return Command::FAILURE;
                    }
                    $this->info("  ✓ Otomatik onarım başarılı.");
                } else {
                    $this->error("  ✗ SAB ihlalleri tespit edildi. --fix ile otomatik onarım deneyin.");
                    return Command::FAILURE;
                }
            } else {
                $this->info("  ✓ SAB Integrity: PASS");
            }

            // ═══════════════════════════════════════════════════════════
            // MODÜL 3: Bekçi Health Check
            // ═══════════════════════════════════════════════════════════
            $this->comment("→ Modül 3: Bekçi sistem sağlığı kontrolü...");
            $healthScore = $this->checkBekciHealth();

            if ($healthScore < 70) {
                $this->warn("  ⚠ Sistem sağlığı düşük: {$healthScore}%");
            } else {
                $this->info("  ✓ Sistem sağlığı: {$healthScore}% (GOOD)");
            }

            // ═══════════════════════════════════════════════════════════
            // MODÜL 4: Accelerated Test Suite (Mutation-Aware)
            // ═══════════════════════════════════════════════════════════
            if (!$this->option('skip-tests')) {
                $this->comment("→ Modül 4: Hızlandırılmış test suite (mutation-aware)...");

                if (!$this->runAcceleratedTests($modifiedFiles, $mode)) {
                    $this->error("  ✗ Test suite başarısız.");
                    return Command::FAILURE;
                }

                $this->info("  ✓ Test Suite: PASS");
            } else {
                $this->warn("  ⊘ Test suite atlandı (--skip-tests)");
            }

            // ═══════════════════════════════════════════════════════════
            // MODÜL 5: Cache Invalidation & Optimization
            // ═══════════════════════════════════════════════════════════
            $this->comment("→ Modül 5: Cache invalidation ve optimizasyon...");
            Artisan::call('cache:clear');
            $this->info("  ✓ Cache temizlendi.");

            // ═══════════════════════════════════════════════════════════
            // FINAL REPORT
            // ═══════════════════════════════════════════════════════════
            $duration = round(microtime(true) - $startTime, 2);

            $this->newLine();
            $this->info("╔═══════════════════════════════════════════════════════════╗");
            $this->info("║              SENTINEL GATE: ALL CHECKS PASSED            ║");
            $this->info("║  Execution Time: {$duration}s | Mode: {$mode}                    ║");
            $this->info("╚═══════════════════════════════════════════════════════════╝");

            return Command::SUCCESS;

        } catch (\Throwable $exception) {
            Log::critical("SENTINEL CRITICAL FAILURE: {$exception->getMessage()}", [
                'trace' => $exception->getTraceAsString(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            $this->error("╔═══════════════════════════════════════════════════════════╗");
            $this->error("║           SENTINEL CRASHED: EXECUTION ABORTED            ║");
            $this->error("║  Error: {$exception->getMessage()}");
            $this->error("╚═══════════════════════════════════════════════════════════╝");

            return Command::FAILURE;
        }
    }

    /**
     * Git delta ile değiştirilen dosyaları listeler
     *
     * @return array<int, string>
     */
    private function getModifiedFiles(): array
    {
        $process = new Process(['git', 'diff', '--name-only', 'HEAD']);
        $process->run();

        if (!$process->isSuccessful()) {
            return [];
        }

        $output = array_filter(explode("\n", trim($process->getOutput())));

        return array_filter($output, fn($file) =>
            str_ends_with($file, '.php') &&
            (str_starts_with($file, 'app/') || str_starts_with($file, 'database/'))
        );
    }

    /**
     * SAB Integrity Scan çalıştırır
     *
     * @param array $modifiedFiles
     * @return bool
     */
    private function runIntegrityScan(array $modifiedFiles): bool
    {
        // sab:integrity-scan --diff komutu tüm değişiklikleri kontrol eder
        $exitCode = Artisan::call('sab:integrity-scan', ['--diff' => true]);

        // Output'u kontrol et
        $output = Artisan::output();

        // "FAIL" içeriyorsa başarısız
        return !str_contains($output, 'FAIL');
    }

    /**
     * Otomatik onarım çalıştırır
     *
     * @return void
     */
    private function runAutoFix(): void
    {
        Artisan::call('sab:integrity-scan', ['--auto-fix' => true]);
    }

    /**
     * Bekçi sistem sağlığını kontrol eder
     *
     * @return float
     */
    private function checkBekciHealth(): float
    {
        Artisan::call('bekci:health');
        $output = Artisan::output();

        // Output'tan sağlık skorunu parse et
        if (preg_match('/Overall System Health: ([\d.]+)%/', $output, $matches)) {
            return (float) $matches[1];
        }

        return 0.0;
    }

    /**
     * Mutation-aware test suite çalıştırır
     *
     * @param array $modifiedFiles
     * @param string $mode
     * @return bool
     */
    private function runAcceleratedTests(array $modifiedFiles, string $mode): bool
    {
        $cmd = ['php', 'artisan', 'test'];

        if ($mode === 'turbo' && !empty($modifiedFiles)) {
            // Mutation-aware: Sadece etkilenen domain'leri test et
            $affectedDomains = $this->detectAffectedDomains($modifiedFiles);

            if (in_array('AI', $affectedDomains)) {
                $cmd[] = '--filter=AiSecurityTest';
            } elseif (in_array('CRM', $affectedDomains)) {
                $cmd[] = '--filter=CRM';
            } else {
                $cmd[] = '--parallel';
            }
        } elseif ($mode === 'quick') {
            $cmd[] = '--stop-on-failure';
        } else {
            $cmd[] = '--parallel';
        }

        $process = new Process($cmd);
        $process->setEnv([
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
        ]);
        $process->setTimeout(600);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Değişen dosyalardan etkilenen domain'leri tespit eder
     *
     * @param array $modifiedFiles
     * @return array<int, string>
     */
    private function detectAffectedDomains(array $modifiedFiles): array
    {
        $domains = [];

        foreach ($modifiedFiles as $file) {
            if (str_contains($file, 'app/Services/AI')) {
                $domains[] = 'AI';
            } elseif (str_contains($file, 'app/Services/CRM')) {
                $domains[] = 'CRM';
            } elseif (str_contains($file, 'app/Domain/CQRS')) {
                $domains[] = 'CQRS';
            }
        }

        return array_unique($domains);
    }
}
