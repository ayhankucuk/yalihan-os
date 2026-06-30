<?php

namespace Tests\Unit\Governance\Ast;

use PHPUnit\Framework\TestCase;
use App\Services\Governance\Ast\AstScannerService;
use App\Services\Governance\Ast\GovernanceAstRuleRegistry;
use App\Services\Governance\Ast\Rules\LanguageHardcodedArrayAstRule;

/**
 * Unit tests for AstScannerService.
 * Runs directly on the scanner — no artisan, no DB, no global guard side-effects.
 *
 * IMPORTANT: sab:integrity-scan also runs SabAutomationGuardService::runAllGuards()
 * globally. These unit tests intentionally bypass that layer.
 */
class AstScannerServiceTest extends TestCase
{
    private AstScannerService $scanner;

    protected function setUp(): void
    {
        $registry = new GovernanceAstRuleRegistry();
        $registry->register(new LanguageHardcodedArrayAstRule());
        $this->scanner = new AstScannerService($registry);
    }

    /** @test */
    public function it_detects_hardcoded_language_array_in_php_file(): void
    {
        $tmpFile = $this->writeTempPhpFile("<?php\n\$l = ['en', 'ru', 'ar', 'de', 'fr'];\n");

        $violations = $this->scanner->scanFile($tmpFile);

        $this->assertCount(1, $violations);
        $this->assertEquals('LanguageHardcodeAST', $violations[0]['rule']);
    }

    /** @test */
    public function all_ast_violations_are_report_only(): void
    {
        // P3A GUARANTEE: every AST violation must carry is_report_only=true
        $tmpFile = $this->writeTempPhpFile("<?php\n\$l = ['en', 'ru', 'ar', 'de', 'fr'];\n");

        $violations = $this->scanner->scanFile($tmpFile);

        $this->assertNotEmpty($violations);
        foreach ($violations as $v) {
            $this->assertTrue(
                $v['is_report_only'],
                "AST violation [{$v['rule']}] must be report-only in P3A — it must never block builds."
            );
        }
    }

    /** @test */
    public function it_skips_seeder_files(): void
    {
        // Files under database/seeders must be excluded
        $tmpFile = $this->writeTempPhpFile(
            "<?php\n\$l = ['en', 'ru', 'ar', 'de', 'fr'];\n",
            'database/seeders'
        );

        $violations = $this->scanner->scanFile($tmpFile);

        $this->assertCount(0, $violations, 'Seeder files should be excluded from AST scan.');
    }

    /** @test */
    public function it_skips_test_files(): void
    {
        $tmpFile = $this->writeTempPhpFile(
            "<?php\n\$l = ['en', 'ru', 'ar', 'de', 'fr'];\n",
            'tests'
        );

        $violations = $this->scanner->scanFile($tmpFile);

        $this->assertCount(0, $violations, 'Test files should be excluded from AST scan.');
    }

    /** @test */
    public function it_returns_empty_for_safe_file(): void
    {
        $tmpFile = $this->writeTempPhpFile("<?php\n\$x = 1 + 1;\n");

        $violations = $this->scanner->scanFile($tmpFile);

        $this->assertCount(0, $violations);
    }

    /** @test */
    public function it_returns_empty_for_non_php_file(): void
    {
        $tmpFile = sys_get_temp_dir() . '/ast_test_' . uniqid() . '.txt';
        file_put_contents($tmpFile, "en, ru, ar, de, fr");

        $violations = $this->scanner->scanFile($tmpFile);
        unlink($tmpFile);

        $this->assertCount(0, $violations, 'Non-PHP files should be ignored.');
    }

    // ──────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────

    /**
     * Write a PHP snippet to a temp file.
     * @param string $pathHint  A path fragment to embed in the filename for exclusion testing.
     */
    private function writeTempPhpFile(string $code, string $pathHint = ''): string
    {
        $suffix = $pathHint ? str_replace('/', '_', $pathHint) . '_' : '';
        $file = sys_get_temp_dir() . '/ast_' . $suffix . uniqid() . '.php';
        // Embed the pathHint into the actual path so exclusion logic in AstScannerService works
        if ($pathHint) {
            $dir = sys_get_temp_dir() . '/' . $pathHint;
            @mkdir($dir, 0777, true);
            $file = $dir . '/' . uniqid() . '.php';
        }
        file_put_contents($file, $code);

        // Register for cleanup at teardown
        $this->filesToCleanup[] = $file;

        return $file;
    }

    private array $filesToCleanup = [];

    protected function tearDown(): void
    {
        foreach ($this->filesToCleanup as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }
}
