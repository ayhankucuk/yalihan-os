<?php

declare(strict_types=1);

namespace Tests\Unit\Scripts;

use Tests\TestCase;

/**
 * CI Guard — Raw DB Write Bypass Testi
 *
 * `scripts/guards/ci-guard-raw-db-write.sh` tarayıcısının yasaklı pattern'leri
 * doğru tespit ettiğini ve temiz kodda hata vermediğini doğrular.
 *
 * Governance kuralı: feature_assignments tablosuna raw DB yazma işlemi
 * CI'da bloklayıcı hata verir (exit 1).
 *
 * @see scripts/guards/ci-guard-raw-db-write.sh
 * @see scripts/guards/quality-gate.sh — STEP 5.1
 * @see docs/adr/2026-02-21-governance-enforcement-layer.md
 */
class CiGuardRawDbWriteTest extends TestCase
{
    private string $guardScript;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guardScript = base_path('scripts/guards/ci-guard-raw-db-write.sh');
        $this->tempDir     = sys_get_temp_dir() . '/ci_guard_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    /**
     * Guard script'i mevcut ve çalıştırılabilir olmalı.
     *
     * @test
     */
    public function guard_script_exists_and_is_executable(): void
    {
        $this->assertFileExists($this->guardScript, 'CI guard script bulunamadı: scripts/guards/ci-guard-raw-db-write.sh');
        $this->assertTrue(is_executable($this->guardScript), 'CI guard script çalıştırılabilir değil');
    }

    /**
     * Temiz kodda guard EXIT:0 dönmeli (ihlal yok).
     *
     * @test
     */
    public function guard_passes_on_clean_codebase(): void
    {
        $exitCode = $this->runGuardOnDir(base_path());
        $this->assertSame(0, $exitCode, 'Guard temiz codebase\'de yanlış ihlal bildirdi');
    }

    /**
     * DB::table('feature_assignments')->insert() yasaklı paterni EXIT:1 döndürmeli.
     *
     * @test
     */
    public function guard_detects_forbidden_insert_pattern(): void
    {
        // Http/Controllers alt dizini oluştur (guard bu dizini tarar)
        $subDir  = $this->tempDir . '/app/Http/Controllers';
        mkdir($subDir, 0755, true);
        $tmpFile = $subDir . '/ForbiddenInsertTest.php';

        file_put_contents($tmpFile, <<<'PHP'
            <?php
            // Test dosyası — yasaklı pattern içerir
            DB::table('feature_assignments')->insert(['feature_id' => 1]);
            PHP);

        $exitCode = $this->runGuardOnDir($this->tempDir);
        $this->assertSame(1, $exitCode, 'Guard yasaklı insert() paterni tespit edemedi');
    }

    /**
     * DB::table('feature_assignments')->update() yasaklı paterni EXIT:1 döndürmeli.
     *
     * @test
     */
    public function guard_detects_forbidden_update_pattern(): void
    {
        $subDir  = $this->tempDir . '/app/Services';
        mkdir($subDir, 0755, true);
        $tmpFile = $subDir . '/ForbiddenUpdateTest.php';

        file_put_contents($tmpFile, <<<'PHP'
            <?php
            DB::table('feature_assignments')->where('id', 1)->update(['is_visible' => false]);
            PHP);

        $exitCode = $this->runGuardOnDir($this->tempDir);
        $this->assertSame(1, $exitCode, 'Guard yasaklı update() paterni tespit edemedi');
    }

    /**
     * DB::table('feature_assignments')->delete() yasaklı paterni EXIT:1 döndürmeli.
     *
     * @test
     */
    public function guard_detects_forbidden_delete_pattern(): void
    {
        $subDir  = $this->tempDir . '/app/Console/Commands';
        mkdir($subDir, 0755, true);
        $tmpFile = $subDir . '/ForbiddenDeleteTest.php';

        file_put_contents($tmpFile, <<<'PHP'
            <?php
            DB::table('feature_assignments')->where('feature_id', 99)->delete();
            PHP);

        $exitCode = $this->runGuardOnDir($this->tempDir);
        $this->assertSame(1, $exitCode, 'Guard yasaklı delete() paterni tespit edemedi');
    }

    /**
     * Karantina tablosu (_quarantine) muaf tutulmalı → EXIT:0.
     *
     * @test
     */
    public function guard_allows_quarantine_table_writes(): void
    {
        $subDir  = $this->tempDir . '/app/Console/Commands';
        mkdir($subDir, 0755, true);
        $tmpFile = $subDir . '/QuarantineOkTest.php';

        file_put_contents($tmpFile, <<<'PHP'
            <?php
            // Quarantine tablosu — muaf
            DB::table('feature_assignments_quarantine')->insert(['feature_id' => 1]);
            PHP);

        $exitCode = $this->runGuardOnDir($this->tempDir);
        $this->assertSame(0, $exitCode, 'Guard meşru karantina tablosu için hata verdi');
    }

    /**
     * Yorum satırı içindeki pattern tespit edilmemeli → EXIT:0.
     *
     * @test
     */
    public function guard_ignores_commented_patterns(): void
    {
        $subDir  = $this->tempDir . '/app/Services';
        mkdir($subDir, 0755, true);
        $tmpFile = $subDir . '/CommentedPatternTest.php';

        file_put_contents($tmpFile, <<<'PHP'
            <?php
            // DB::table('feature_assignments')->delete(); — ESKİ KOD, kaldırıldı
            /* DB::table('feature_assignments')->insert($data); */
            PHP);

        $exitCode = $this->runGuardOnDir($this->tempDir);
        $this->assertSame(0, $exitCode, 'Guard yorum satırındaki pattern için hata verdi');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Guard script'ini verilen dizin üzerinde çalıştırır ve exit code döner.
     * Script BASE_DIR override için geçici env değişkeni kullanır.
     */
    private function runGuardOnDir(string $dir): int
    {
        $script = escapeshellarg($this->guardScript);
        $dirArg = escapeshellarg($dir);

        // Guard'a hangi dizini tarayacağını söyle (CI_GUARD_BASE_DIR değişkeni)
        exec("CI_GUARD_BASE_DIR={$dirArg} bash {$script} 2>/dev/null", $output, $exitCode);

        return $exitCode;
    }

    /**
     * Geçici test dizinini temizle.
     */
    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($dir);
    }
}
