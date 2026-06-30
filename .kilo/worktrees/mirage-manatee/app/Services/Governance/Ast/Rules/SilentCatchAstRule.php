<?php

namespace App\Services\Governance\Ast\Rules;

use App\Services\Governance\Ast\GovernanceAstRuleInterface;
use PhpParser\Node;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Throw_;
use PhpParser\Node\Stmt\Return_;

/**
 * SilentCatchAstRule — Pack-P3C (Report-Only)
 *
 * Detects catch blocks that silently swallow exceptions:
 *  - Empty catch blocks (no statements at all)
 *  - Catch blocks without any of: throw / Log:: / report() / return response
 *
 * This is the AST-based counterpart to the grep-based SILENT_CATCH_GUARD_V3.
 * It is more precise because it understands PHP structure rather than regex-matching lines.
 *
 * Allowed patterns (NOT flagged):
 *  - throw $e / throw new ...
 *  - Log::error() / Log::warning() / Log::info() / Log::critical()
 *  - LogService::*()
 *  - report($e)
 *  - return response(...) / return $this->error(...)
 *  - Any return inside a catch that also logs
 */
class SilentCatchAstRule implements GovernanceAstRuleInterface
{
    public function getRuleId(): string
    {
        return 'SilentCatchAST';
    }

    public function getSeverity(): string
    {
        return 'MEDIUM';
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function getExcludedPaths(): array
    {
        return [
            'database/seeders',
            'database/migrations',
            'tests/',
            'config/',
            'vendor/',
        ];
    }

    /**
     * We analyze TryCatch nodes (the full try/catch statement).
     */
    public function analyze(Node $node): ?array
    {
        if (!$node instanceof TryCatch) {
            return null;
        }

        // Respect @sab-ignore-catch
        $docComment = $node->getDocComment();
        if ($docComment && str_contains($docComment->getText(), '@sab-ignore-catch')) {
            return null;
        }

        foreach ($node->catches as $catch) {
            // Also check catch-level ignore
            $catchDoc = $catch->getDocComment();
            if ($catchDoc && str_contains($catchDoc->getText(), '@sab-ignore-catch')) {
                continue;
            }

            $result = $this->analyzeCatch($catch);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    private function analyzeCatch(Catch_ $catch): ?array
    {
        $stmts = $catch->stmts;

        // Empty catch block
        if (empty($stmts)) {
            return ['message' => 'AST Detected: Empty catch block — exception is swallowed silently.'];
        }

        // Check if any statement is a meaningful handler
        if (!$this->hasMeaningfulHandler($stmts)) {
            return ['message' => 'AST Detected: Catch block has no throw, Log::*, LogService::*, report() or return — exception may be swallowed.'];
        }

        return null;
    }

    /**
     * @param Node[] $stmts
     */
    private function hasMeaningfulHandler(array $stmts): bool
    {
        foreach ($stmts as $stmt) {
            // throw $e / throw new ...
            if ($stmt instanceof Throw_) {
                return true;
            }

            // Expression statements: Log::*, report(), throw (if expr)
            if ($stmt instanceof \PhpParser\Node\Stmt\Expression) {
                $expr = $stmt->expr;

                // throw $e (as expression)
                if ($expr instanceof \PhpParser\Node\Expr\Throw_) {
                    return true;
                }

                // Static call: Log::error(), LogService::error(), etc.
                if ($expr instanceof StaticCall) {
                    $className = $this->resolveClassName($expr->class);
                    if (in_array($className, ['Log', 'LogService'], true)) {
                        return true;
                    }
                }

                // Method call: $this->error(), $this->warn(), $this->info(), etc. (for Commands)
                if ($expr instanceof \PhpParser\Node\Expr\MethodCall) {
                    $name = $this->resolveFuncName($expr->name);
                    if (in_array($name, ['error', 'warn', 'info', 'comment', 'line', 'report', 'log'], true)) {
                        return true;
                    }
                }

                // report($e) — Laravel's global helper
                if ($expr instanceof FuncCall) {
                    $name = $this->resolveFuncName($expr->name);
                    if ($name === 'report') {
                        return true;
                    }
                }
            }

            // return response()->json(...) / return redirect() / return $this->error(...)
            // Only counts if the return value is a method call or specific function call
            if ($stmt instanceof Return_ && $stmt->expr !== null) {
                $expr = $stmt->expr;
                
                // return response()->json(...) / return $this->error(...)
                if ($expr instanceof \PhpParser\Node\Expr\MethodCall) {
                    return true;
                }
                
                // return response() / return redirect()
                if ($expr instanceof FuncCall) {
                    $name = $this->resolveFuncName($expr->name);
                    if (in_array($name, ['response', 'redirect'], true)) {
                        return true;
                    }
                }
                
                // Scalar returns (false, null, 0, []) are NOT considered meaningful
            }
        }

        return false;
    }

    private function resolveClassName(mixed $class): string
    {
        if ($class instanceof \PhpParser\Node\Name) {
            return $class->getLast();
        }
        return '';
    }

    private function resolveFuncName(mixed $name): string
    {
        if ($name instanceof \PhpParser\Node\Name) {
            return $name->getLast();
        }
        if ($name instanceof \PhpParser\Node\Identifier) {
            return $name->name;
        }
        return '';
    }
}
