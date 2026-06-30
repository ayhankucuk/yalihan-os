<?php

namespace Tests\Unit\Governance\Ast;

use PHPUnit\Framework\TestCase;
use App\Services\Governance\Ast\Rules\ForbiddenFunctionAstRule;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

/**
 * Unit tests for ForbiddenFunctionAstRule — Pack-P3D.
 */
class ForbiddenFunctionAstRuleTest extends TestCase
{
    private ForbiddenFunctionAstRule $rule;

    protected function setUp(): void
    {
        $this->rule = new ForbiddenFunctionAstRule();
    }

    /** @test */
    public function it_has_expected_rule_id_and_severity(): void
    {
        $this->assertEquals('ForbiddenFunctionAST', $this->rule->getRuleId());
        $this->assertEquals('HIGH', $this->rule->getSeverity());
    }

    /** @test */
    public function it_is_enabled_by_default(): void
    {
        $this->assertTrue($this->rule->isEnabled());
    }

    /** @test */
    public function it_flags_system_calls(): void
    {
        $violations = $this->scan("<?php\nsystem('ls');\n");
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('forbidden function \'system()\'', $violations[0]['message']);
    }

    /** @test */
    public function it_flags_eval_calls(): void
    {
        $violations = $this->scan("<?php\neval('\$x = 1;');\n");
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('eval()', $violations[0]['message']);
    }

    /** @test */
    public function it_flags_shell_exec_calls(): void
    {
        $violations = $this->scan("<?php\nshell_exec('whoami');\n");
        $this->assertCount(1, $violations);
    }

    /** @test */
    public function it_does_not_flag_allowed_functions(): void
    {
        $violations = $this->scan("<?php\necho strtolower('HELLO');\n\$count = count([1,2,3]);\n");
        $this->assertCount(0, $violations);
    }

    /** @test */
    public function it_is_case_insensitive(): void
    {
        $violations = $this->scan("<?php\nSYSTEM('ls');\n");
        $this->assertCount(1, $violations);
    }

    /** @test */
    public function it_ignores_variable_function_calls(): void
    {
        // Variable function calls are harder to analyze statically without data flow
        $violations = $this->scan("<?php\n\$func = 'system';\n\$func('ls');\n");
        $this->assertCount(0, $violations);
    }

    private function scan(string $code): array
    {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $violations = [];
        $rule = $this->rule;

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class($rule, $violations) extends NodeVisitorAbstract {
            public function __construct(
                private ForbiddenFunctionAstRule $rule,
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
