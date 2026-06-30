<?php

namespace Tests\Unit\Governance\Ast;

use PHPUnit\Framework\TestCase;
use App\Services\Governance\Ast\Rules\SilentCatchAstRule;
use App\Services\Governance\Ast\AstScannerService;
use App\Services\Governance\Ast\GovernanceAstRuleRegistry;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

/**
 * Unit tests for SilentCatchAstRule — Pack-P3C.
 * Tests run directly on the rule and scanner, no artisan / DB.
 */
class SilentCatchAstRuleTest extends TestCase
{
    private SilentCatchAstRule $rule;

    protected function setUp(): void
    {
        $this->rule = new SilentCatchAstRule();
    }

    // ──────────────────────────────────────────────────────
    // Rule metadata
    // ──────────────────────────────────────────────────────

    /** @test */
    public function it_has_expected_rule_id_and_severity(): void
    {
        $this->assertEquals('SilentCatchAST', $this->rule->getRuleId());
        $this->assertEquals('MEDIUM', $this->rule->getSeverity());
    }

    /** @test */
    public function it_is_enabled_by_default(): void
    {
        $this->assertTrue($this->rule->isEnabled());
    }

    /** @test */
    public function it_excludes_tests_and_seeders(): void
    {
        $excluded = $this->rule->getExcludedPaths();
        $this->assertContains('tests/', $excluded);
        $this->assertContains('database/seeders', $excluded);
    }

    // ──────────────────────────────────────────────────────
    // Detection: flagged patterns
    // ──────────────────────────────────────────────────────

    /** @test */
    public function it_flags_empty_catch_block(): void
    {
        $violations = $this->scan("<?php\ntry {\n  \$x = 1;\n} catch (\\Exception \$e) {\n}\n");
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Empty catch', $violations[0]['message']);
    }

    /** @test */
    public function it_flags_catch_with_only_comment(): void
    {
        $violations = $this->scan("<?php\ntry {\n  \$x = 1;\n} catch (\\Exception \$e) {\n  // TODO handle\n}\n");
        // Comments are not statements — AST sees this as empty
        $this->assertCount(1, $violations);
    }

    /** @test */
    public function it_flags_catch_returning_false_without_log(): void
    {
        $violations = $this->scan("<?php\nfunction foo() {\n  try {\n    \$x = 1;\n  } catch (\\Exception \$e) {\n    return false;\n  }\n}\n");
        $this->assertCount(1, $violations);
    }

    // ──────────────────────────────────────────────────────
    // Detection: safe patterns (NOT flagged)
    // ──────────────────────────────────────────────────────

    /** @test */
    public function it_does_not_flag_catch_with_throw(): void
    {
        $violations = $this->scan("<?php\ntry {\n  \$x = 1;\n} catch (\\Exception \$e) {\n  throw \$e;\n}\n");
        $this->assertCount(0, $violations);
    }

    /** @test */
    public function it_does_not_flag_catch_with_log_error(): void
    {
        $violations = $this->scan("<?php\nuse Illuminate\\Support\\Facades\\Log;\ntry {\n  \$x = 1;\n} catch (\\Exception \$e) {\n  Log::error('Failed', ['e' => \$e]);\n}\n");
        $this->assertCount(0, $violations);
    }

    /** @test */
    public function it_does_not_flag_catch_with_log_warning(): void
    {
        $violations = $this->scan("<?php\ntry {\n  \$x = 1;\n} catch (\\Exception \$e) {\n  Log::warning('warn');\n}\n");
        $this->assertCount(0, $violations);
    }

    /** @test */
    public function it_does_not_flag_catch_with_report(): void
    {
        $violations = $this->scan("<?php\ntry {\n  \$x = 1;\n} catch (\\Exception \$e) {\n  report(\$e);\n}\n");
        $this->assertCount(0, $violations);
    }

    /** @test */
    public function it_does_not_flag_catch_with_return_response(): void
    {
        $violations = $this->scan("<?php\nfunction foo() {\n  try {\n    \$x = 1;\n  } catch (\\Exception \$e) {\n    return response()->json(['error' => \$e->getMessage()]);\n  }\n}\n");
        $this->assertCount(0, $violations);
    }

    /** @test */
    public function it_does_not_flag_catch_with_logservice(): void
    {
        $violations = $this->scan("<?php\ntry {\n  \$x = 1;\n} catch (\\Exception \$e) {\n  LogService::error('msg', [], \$e);\n}\n");
        $this->assertCount(0, $violations);
    }

    // ──────────────────────────────────────────────────────
    // Integration: scanner excludes rule from test paths
    // ──────────────────────────────────────────────────────

    /** @test */
    public function it_is_not_report_only_false_for_ast_violations(): void
    {
        $tmpFile = sys_get_temp_dir() . '/silent_catch_test_' . uniqid() . '.php';
        file_put_contents($tmpFile, "<?php\ntry { \$x = 1; } catch (\\Exception \$e) {}\n");

        $registry = new GovernanceAstRuleRegistry();
        $scanner = new AstScannerService($registry);
        $violations = $scanner->scanFile($tmpFile);
        unlink($tmpFile);

        $this->assertNotEmpty($violations);
        foreach ($violations as $v) {
            $this->assertTrue($v['is_report_only'], 'All AST violations must be report-only in P3C.');
        }
    }

    // ──────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────

    private function scan(string $code): array
    {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $violations = [];
        $rule = $this->rule;

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class($rule, $violations) extends NodeVisitorAbstract {
            public function __construct(
                private SilentCatchAstRule $rule,
                private array &$violations
            ) {}

            public function enterNode(Node $node)
            {
                $result = $this->rule->analyze($node);
                if ($result !== null) {
                    $this->violations[] = ['rule' => $this->rule->getRuleId(), ...$result];
                }
                return null;
            }
        });

        $traverser->traverse($ast);
        return $violations;
    }
}
