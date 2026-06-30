<?php

namespace App\Services\Governance\Ast\Rules;

use App\Services\Governance\Ast\GovernanceAstRuleInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;

/**
 * EnvUsageAstRule — Wave 2 (Architecture & Standards)
 *
 * Detects direct env() calls in the 'app/' directory.
 * Rationale: env() should ONLY be used in config/ files to allow config:cache.
 */
class EnvUsageAstRule implements GovernanceAstRuleInterface
{
    public function getRuleId(): string
    {
        return 'EnvUsageAST';
    }

    public function getSeverity(): string
    {
        return 'HIGH';
    }

    public function getDescription(): string
    {
        return 'Direct env() call detected. Use config() instead to ensure compatibility with config:cache.';
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function getExcludedPaths(): array
    {
        return [
            'config/',
            'tests/',
            'vendor/',
        ];
    }

    public function analyze(Node $node): ?array
    {
        if (!($node instanceof FuncCall)) {
            return null;
        }

        if (!($node->name instanceof Name)) {
            return null;
        }

        $funcName = $node->name->toString();

        if ($funcName === 'env') {
            return [
                'line' => $node->getLine(),
                'message' => $this->getDescription(),
                'severity' => $this->getSeverity(),
            ];
        }

        return null;
    }
}
