<?php

namespace App\Services\Governance\Ast;

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;
use Illuminate\Support\Facades\Log;

class AstScannerService
{
    private GovernanceAstRuleRegistry $registry;

    public function __construct(GovernanceAstRuleRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Scans a file and returns an array of violations.
     * 
     * @return array
     */
    public function scanFile(string $filePath): array
    {
        if (!file_exists($filePath) || !str_ends_with($filePath, '.php')) {
            return [];
        }

        $code = file_get_contents($filePath);
        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        try {
            $ast = $parser->parse($code);
        } catch (\PhpParser\Error $error) {
            Log::warning("AST Parse error in {$filePath}: {$error->getMessage()}");
            return [];
        }

        $violations = [];

        // Only use enabled rules; apply per-rule + config path exclusions
        $enabledRules = array_filter(
            $this->registry->getEnabled(),
            function (GovernanceAstRuleInterface $rule) use ($filePath): bool {
                // Merge rule's own excluded paths with config-driven extra exclusions
                $excludedPaths = array_unique(array_merge(
                    $rule->getExcludedPaths(),
                    $this->registry->getExcludedPathsFor($rule->getRuleId())
                ));
                foreach ($excludedPaths as $fragment) {
                    if (str_contains($filePath, $fragment)) {
                        return false;
                    }
                }
                return true;
            }
        );

        if (empty($enabledRules)) {
            return [];
        }

        $traverser = new NodeTraverser();
        $visitor = new class($enabledRules, $violations, $this->registry) extends NodeVisitorAbstract {
            private array $rules;
            public array $violations;
            private GovernanceAstRuleRegistry $registry;

            public function __construct(array $rules, array &$violations, GovernanceAstRuleRegistry $registry)
            {
                $this->rules = $rules;
                $this->violations = &$violations;
                $this->registry = $registry;
            }

            public function enterNode(Node $node)
            {
                foreach ($this->rules as $rule) {
                    $result = $rule->analyze($node);
                    if ($result !== null) {
                        // Config severity override takes precedence over rule default
                        $severity = $this->registry->getSeverityFor($rule->getRuleId()) ?? $rule->getSeverity();
                        $this->violations[] = [
                            'rule'          => $rule->getRuleId(),
                            'severity'      => $severity,
                            'line'          => $node->getStartLine(),
                            'message'       => $result['message'],
                            'is_report_only' => $this->registry->isGlobalReportOnly(),
                            'type'          => $rule->getRuleId(),
                        ];
                    }
                }
                return null;
            }
        };

        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->violations;
    }
}
