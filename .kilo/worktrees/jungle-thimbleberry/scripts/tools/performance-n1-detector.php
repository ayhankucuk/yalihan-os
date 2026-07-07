<?php
/**
 * Performance N+1 Query Detector
 *
 * Purpose: Deep AST-based analysis for N+1 query patterns
 * Author: WenOX AI (Yalıhan Bekçi Performance Module)
 * Version: 1.0.0
 * Created: 2026-05-20
 *
 * Usage: php scripts/tools/performance-n1-detector.php [--path=app/] [--format=json]
 */

require __DIR__ . '/../../vendor/autoload.php';

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class N1QueryDetector extends NodeVisitorAbstract
{
    private array $issues = [];
    private string $currentFile = '';
    private array $eagerLoadedRelations = [];
    private bool $inForeach = false;
    private int $foreachDepth = 0;

    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
        $this->eagerLoadedRelations = [];
        $this->inForeach = false;
        $this->foreachDepth = 0;
    }

    public function getIssues(): array
    {
        return $this->issues;
    }

    public function enterNode(Node $node)
    {
        // Track foreach loops
        if ($node instanceof Node\Stmt\Foreach_) {
            $this->inForeach = true;
            $this->foreachDepth++;
        }

        // Detect eager loading (with, load)
        if ($node instanceof Node\Expr\MethodCall) {
            if ($node->name instanceof Node\Identifier) {
                $methodName = $node->name->toString();

                // Track eager loading
                if (in_array($methodName, ['with', 'load'])) {
                    $this->trackEagerLoading($node);
                }

                // Detect relationship access in foreach
                if ($this->inForeach && $this->isRelationshipAccess($node)) {
                    $this->detectN1InForeach($node);
                }
            }
        }

        // Detect queries without eager loading
        if ($node instanceof Node\Expr\StaticCall) {
            if ($node->class instanceof Node\Name) {
                $className = $node->class->toString();
                if ($node->name instanceof Node\Identifier) {
                    $methodName = $node->name->toString();

                    // Check for query methods without eager loading
                    if (in_array($methodName, ['where', 'find', 'findOrFail', 'all', 'get'])) {
                        $this->detectQueryWithoutEagerLoading($node, $className, $methodName);
                    }
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        // Exit foreach loop
        if ($node instanceof Node\Stmt\Foreach_) {
            $this->foreachDepth--;
            if ($this->foreachDepth === 0) {
                $this->inForeach = false;
            }
        }

        return null;
    }

    private function trackEagerLoading(Node\Expr\MethodCall $node): void
    {
        // Extract relation names from with(['relation1', 'relation2'])
        if (!empty($node->args)) {
            $arg = $node->args[0]->value;

            if ($arg instanceof Node\Expr\Array_) {
                foreach ($arg->items as $item) {
                    if ($item && $item->value instanceof Node\Scalar\String_) {
                        $this->eagerLoadedRelations[] = $item->value->value;
                    }
                }
            } elseif ($arg instanceof Node\Scalar\String_) {
                $this->eagerLoadedRelations[] = $arg->value;
            }
        }
    }

    private function isRelationshipAccess(Node\Expr\MethodCall $node): bool
    {
        // Common relationship names
        $commonRelations = [
            'user', 'users', 'kisi', 'kisiler', 'ilan', 'ilanlar',
            'danisman', 'danismanlar', 'kategori', 'kategoriler',
            'il', 'iller', 'ilce', 'ilceler', 'mahalle', 'mahalleler',
            'fotograflar', 'features', 'assignments', 'roles'
        ];

        if ($node->name instanceof Node\Identifier) {
            $methodName = $node->name->toString();
            return in_array($methodName, $commonRelations);
        }

        return false;
    }

    private function detectN1InForeach(Node\Expr\MethodCall $node): void
    {
        $relationName = $node->name->toString();

        // Check if this relation was eager loaded
        if (!in_array($relationName, $this->eagerLoadedRelations)) {
            $this->issues[] = [
                'type' => 'N+1_IN_FOREACH',
                'severity' => 'HIGH',
                'file' => $this->currentFile,
                'line' => $node->getLine(),
                'message' => "Potential N+1: Accessing relationship '{$relationName}' inside foreach without eager loading",
                'recommendation' => "Add ->with(['{$relationName}']) to the query before foreach",
                'code_snippet' => $this->getCodeSnippet($node)
            ];
        }
    }

    private function detectQueryWithoutEagerLoading(Node\Expr\StaticCall $node, string $className, string $methodName): void
    {
        // Check if this is followed by with() or load()
        $hasEagerLoading = false;

        // Look ahead in the chain
        $current = $node;
        while ($current instanceof Node\Expr\StaticCall || $current instanceof Node\Expr\MethodCall) {
            if ($current instanceof Node\Expr\MethodCall && $current->name instanceof Node\Identifier) {
                $name = $current->name->toString();
                if (in_array($name, ['with', 'load'])) {
                    $hasEagerLoading = true;
                    break;
                }
            }

            // Move to next in chain
            if (isset($current->var)) {
                $current = $current->var;
            } else {
                break;
            }
        }

        if (!$hasEagerLoading && $this->isEloquentModel($className)) {
            $this->issues[] = [
                'type' => 'QUERY_WITHOUT_EAGER_LOADING',
                'severity' => 'MEDIUM',
                'file' => $this->currentFile,
                'line' => $node->getLine(),
                'message' => "Query on {$className}::{$methodName}() without eager loading",
                'recommendation' => "Consider adding ->with(['relation']) if relationships are accessed later",
                'code_snippet' => $this->getCodeSnippet($node)
            ];
        }
    }

    private function isEloquentModel(string $className): bool
    {
        // Common Eloquent model patterns
        $modelPatterns = ['Ilan', 'Kisi', 'Talep', 'User', 'Feature', 'Category'];

        foreach ($modelPatterns as $pattern) {
            if (str_contains($className, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function getCodeSnippet(Node $node): string
    {
        // Return a simple representation
        return "Line {$node->getLine()}";
    }
}

// ============================================================================
// Main Execution
// ============================================================================

$options = getopt('', ['path:', 'format:']);
$scanPath = $options['path'] ?? 'app/';
$format = $options['format'] ?? 'text';

$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
$traverser = new NodeTraverser();
$detector = new N1QueryDetector();

$traverser->addVisitor(new NameResolver());
$traverser->addVisitor($detector);

// Scan PHP files
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($scanPath),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$totalFiles = 0;
$allIssues = [];

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $totalFiles++;
        $detector->setCurrentFile($file->getPathname());

        try {
            $code = file_get_contents($file->getPathname());
            $ast = $parser->parse($code);

            if ($ast) {
                $traverser->traverse($ast);
            }
        } catch (Error $e) {
            // Skip files with parse errors
            continue;
        }
    }
}

$allIssues = $detector->getIssues();

// Output results
if ($format === 'json') {
    echo json_encode([
        'success' => true,
        'scanned_files' => $totalFiles,
        'total_issues' => count($allIssues),
        'issues' => $allIssues,
        'summary' => [
            'n1_in_foreach' => count(array_filter($allIssues, fn($i) => $i['type'] === 'N+1_IN_FOREACH')),
            'query_without_eager' => count(array_filter($allIssues, fn($i) => $i['type'] === 'QUERY_WITHOUT_EAGER_LOADING'))
        ]
    ], JSON_PRETTY_PRINT);
} else {
    echo "N+1 Query Detector Results\n";
    echo str_repeat('=', 60) . "\n\n";
    echo "Scanned Files: {$totalFiles}\n";
    echo "Total Issues: " . count($allIssues) . "\n\n";

    if (empty($allIssues)) {
        echo "✅ No N+1 query issues detected!\n";
    } else {
        foreach ($allIssues as $issue) {
            echo "[{$issue['severity']}] {$issue['type']}\n";
            echo "  File: {$issue['file']}\n";
            echo "  Line: {$issue['line']}\n";
            echo "  Message: {$issue['message']}\n";
            echo "  Fix: {$issue['recommendation']}\n\n";
        }
    }
}

exit(count($allIssues) > 0 ? 1 : 0);
