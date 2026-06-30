<?php

namespace Tests\Unit\Governance\Ast;

use PHPUnit\Framework\TestCase;
use App\Services\Governance\Ast\Rules\LanguageHardcodedArrayAstRule;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

/**
 * Unit tests for LanguageHardcodedArrayAstRule.
 * Runs directly on the rule — no artisan, no DB, no SAB command.
 */
class LanguageHardcodedArrayAstRuleTest extends TestCase
{
    private LanguageHardcodedArrayAstRule $rule;

    protected function setUp(): void
    {
        $this->rule = new LanguageHardcodedArrayAstRule();
    }

    /** @test */
    public function it_has_expected_rule_id_and_severity(): void
    {
        $this->assertEquals('LanguageHardcodeAST', $this->rule->getRuleId());
        $this->assertEquals('HIGH', $this->rule->getSeverity());
    }

    /** @test */
    public function it_detects_full_language_hardcode_array(): void
    {
        $result = $this->analyzeSnippet("<?php \$l = ['en', 'ru', 'ar', 'de', 'fr'];");

        $this->assertCount(1, $result);
        $this->assertEquals('LanguageHardcodeAST', $result[0]['rule']);
    }

    /** @test */
    public function it_detects_partial_forbidden_set_of_three_or_more(): void
    {
        // en + ru + ar = 3 matches => should flag
        $result = $this->analyzeSnippet("<?php \$l = ['en', 'ru', 'ar'];");

        $this->assertCount(1, $result);
    }

    /** @test */
    public function it_does_not_flag_safe_small_arrays(): void
    {
        // Only tr + en = 2 matches => below threshold, safe
        $result = $this->analyzeSnippet("<?php \$l = ['tr', 'en'];");

        $this->assertCount(0, $result);
    }

    /** @test */
    public function it_does_not_flag_non_language_arrays(): void
    {
        $result = $this->analyzeSnippet("<?php \$roles = ['admin', 'editor', 'viewer', 'manager'];");

        $this->assertCount(0, $result);
    }

    /** @test */
    public function it_does_not_flag_single_element_arrays(): void
    {
        $result = $this->analyzeSnippet("<?php \$l = ['en'];");

        $this->assertCount(0, $result);
    }

    /**
     * Parse a PHP snippet and run the rule on all Array_ nodes.
     */
    private function analyzeSnippet(string $code): array
    {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $violations = [];
        $rule = $this->rule;

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class($rule, $violations) extends NodeVisitorAbstract {
            public function __construct(
                private LanguageHardcodedArrayAstRule $rule,
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
