<?php

namespace App\Services\Governance\Ast\Rules;

use App\Services\Governance\Ast\GovernanceAstRuleInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;

/**
 * ForbiddenFunctionAstRule — Pack-P3D (Report-Only)
 *
 * Detects calls to potentially dangerous or project-forbidden functions.
 * Common examples: eval, passthru, system, shell_exec, exec, proc_open, popen.
 */
class ForbiddenFunctionAstRule implements GovernanceAstRuleInterface
{
    /**
     * Default list of forbidden functions.
     * Can be overridden via config/sab_ast.php
     */
    private array $forbiddenFunctions = [
        'eval',
        'passthru',
        'system',
        'shell_exec',
        'exec',
        'proc_open',
        'popen',
    ];

    public function getRuleId(): string
    {
        return 'ForbiddenFunctionAST';
    }

    public function getSeverity(): string
    {
        return 'HIGH';
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function getExcludedPaths(): array
    {
        return [
            'vendor/',
            'tests/',
            'database/migrations/',
        ];
    }

    /**
     * Analyze Function Call and Eval nodes.
     */
    public function analyze(Node $node): ?array
    {
        // Handle eval() which is an Expr\Eval_ node, not a FuncCall
        if ($node instanceof \PhpParser\Node\Expr\Eval_) {
            return [
                'message' => "AST Detected: Call to forbidden language construct 'eval()'. This is a high-security violation.",
            ];
        }

        if (!$node instanceof FuncCall) {
            return null;
        }

        $funcName = $this->resolveFuncName($node->name);

        if (empty($funcName)) {
            return null;
        }

        // Check against forbidden list
        if (in_array(strtolower($funcName), $this->getForbiddenList(), true)) {
            return [
                'message' => "AST Detected: Call to forbidden function '{$funcName}()'. This may be a security or architectural violation.",
            ];
        }

        return null;
    }

    /**
     * Set forbidden functions (for testing or manual override).
     */
    public function setForbiddenFunctions(array $functions): void
    {
        $this->forbiddenFunctions = $functions;
    }

    /**
     * Merge default list with config if available.
     */
    private function getForbiddenList(): array
    {
        // Try to get from config if helper exists and is bootstrapped
        if (function_exists('config')) {
            try {
                $configList = config('sab_ast.rules.ForbiddenFunctionAST.forbidden_functions');
                if (is_array($configList)) {
                    return array_unique(array_merge($this->forbiddenFunctions, $configList));
                }
            } catch (\Throwable $e) {
                // Graceful fallback: config() not available in pure unit test environments.
                // Log facade kullanılamaz (container boot edilmemiş olabilir) — error_log ile fallback
                error_log('[ForbiddenFunctionAstRule] config load failed: ' . $e->getMessage());
            }
        }

        return $this->forbiddenFunctions;
    }

    private function resolveFuncName(mixed $name): string
    {
        if ($name instanceof Name) {
            return $name->getLast();
        }

        // Handle variable function calls: $func() — currently ignored by this rule
        return '';
    }
}
