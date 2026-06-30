<?php

namespace App\Services\Governance\Ast\Rules;

use App\Services\Governance\Ast\GovernanceAstRuleInterface;
use PhpParser\Node;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;

class CognitiveGuardianRule implements GovernanceAstRuleInterface
{
    public function getRuleId(): string
    {
        return 'AP-COGNITIVE-001';
    }

    public function getSeverity(): string
    {
        return 'BLOCKING';
    }

    public function getExcludedPaths(): array
    {
        return ['vendor', 'tests', 'config'];
    }

    public function analyze(Node $node): ?array
    {
        // AP-002: Silent Exception Swallow Detection
        if ($node instanceof Catch_) {
            if (empty($node->stmts)) {
                return [
                    'message' => 'AP-002: Silent Exception Swallow detected. Catch block must not be empty.',
                ];
            }
        }

        // AP-003: Forbidden Controller Env Usage
        if ($node instanceof FuncCall && $node->name instanceof Name) {
            if ($node->name->toString() === 'env') {
                // In a real scenario, we'd check the parent class context here.
                // For this implementation, we block env() in any app/ file except config/
                return [
                    'message' => 'AP-003: Forbidden env() usage detected. Use config() instead for architectural compliance.',
                ];
            }
        }

        return null;
    }
}
