<?php

namespace Tests\Feature\Governance;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use App\Services\Governance\Ast\AstScannerService;
use App\Services\Governance\Ast\GovernanceAstRuleRegistry;
use App\Services\Governance\Ast\Rules\LanguageHardcodedArrayAstRule;

class AstAnalyzerTest extends TestCase
{
    /** @test */
    public function it_detects_hardcoded_languages_in_php_snippet()
    {
        $registry = new GovernanceAstRuleRegistry();
        // Ensure our rule is there
        $registry->register(new LanguageHardcodedArrayAstRule());
        
        $scanner = new AstScannerService($registry);

        // Create a temporary PHP file to parse
        $phpCode = "<?php\n\$locales = ['en', 'ru', 'ar', 'de', 'fr'];\n";
        $tmpFile = tempnam(sys_get_temp_dir(), 'ast_test_') . '.php';
        file_put_contents($tmpFile, $phpCode);

        $violations = $scanner->scanFile($tmpFile);
        
        unlink($tmpFile);

        $this->assertCount(1, $violations);
        $this->assertEquals('LanguageHardcodeAST', $violations[0]['rule']);
        $this->assertTrue($violations[0]['is_report_only']);
    }

    /** @test */
    public function it_skips_safe_arrays()
    {
        $registry = new GovernanceAstRuleRegistry();
        $scanner = new AstScannerService($registry);

        $phpCode = "<?php\n\$safe = ['tr', 'en'];\n";
        $tmpFile = tempnam(sys_get_temp_dir(), 'ast_test_safe_') . '.php';
        file_put_contents($tmpFile, $phpCode);

        $violations = $scanner->scanFile($tmpFile);
        
        unlink($tmpFile);

        $this->assertCount(0, $violations);
    }

    /** @test */
    public function it_does_not_fail_sab_scan_for_report_only_ast_rules()
    {
        // Test this at the SERVICE level: AstScannerService produces is_report_only=true violations
        $registry = new GovernanceAstRuleRegistry();
        $registry->register(new LanguageHardcodedArrayAstRule());
        $scanner = new AstScannerService($registry);

        $phpCode = "<?php\n\$locales = ['en', 'ru', 'ar', 'de', 'fr'];\n";
        $tmpFile = tempnam(sys_get_temp_dir(), 'ast_test_pass_') . '.php';
        file_put_contents($tmpFile, $phpCode);

        $violations = $scanner->scanFile($tmpFile);
        unlink($tmpFile);

        $this->assertNotEmpty($violations);
        // ALL AST violations must be report_only so they cannot block the build
        foreach ($violations as $v) {
            $this->assertTrue($v['is_report_only'], "AST violation [{$v['rule']}] must be report-only in P3A.");
        }
    }

    /** @test */
    public function markdown_formatter_works()
    {
        // Test the formatter directly without running full artisan scan
        $violations = [
            [
                'file' => 'app/SomeService.php',
                'line' => 10,
                'rule' => 'LanguageHardcodeAST',
                'type' => 'LanguageHardcodeAST',
                'severity' => 'HIGH',
                'message' => 'Hardcoded language array found.',
                'suggestion' => '',
                'source' => 'yalihan-bekci',
                'origin' => 'ast_analyzer',
                'fingerprint' => 'abc123',
                'is_baseline' => false,
                'is_report_only' => true,
            ]
        ];

        $output = $this->captureMarkdownOutput($violations);

        $this->assertStringContainsString('# 🛡️ SAB Integrity Scan Report', $output);
        $this->assertStringContainsString('| File | Line | Rule | Severity | Message |', $output);
        $this->assertStringContainsString('LanguageHardcodeAST', $output);
        $this->assertStringContainsString('**PASS:**', $output);
        $this->assertStringContainsString('report-only', $output);
    }

    private function captureMarkdownOutput(array $violations): string
    {
        $buffer = '';
        $command = new class($buffer) extends \Illuminate\Console\Command {
            public function __construct(private string &$buf) { parent::__construct(); }
            public function line($string, $style = null, $verbosity = null): void { $this->buf .= $string; }
            public function info($string, $verbosity = null): void { $this->buf .= $string; }
            public function error($string, $verbosity = null): void { $this->buf .= $string; }
            public function warn($string, $verbosity = null): void { $this->buf .= $string; }
        };

        $formatter = new \App\Services\Governance\SabScanFormatter($command);
        $formatter->renderMarkdown($violations, ['path' => 'app', 'duration' => 0, 'legacyCount' => 0]);

        return $buffer;
    }
}
