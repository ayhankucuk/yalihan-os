<?php

declare(strict_types=1);

namespace App\Support\Governance\Analyze\Ast;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

/**
 * AST Parser for PHP source files
 *
 * Provides deterministic parsing with nikic/php-parser.
 * Used by Pack-P3 analyzers for deeper code inspection.
 *
 * @package App\Support\Governance\Analyze\Ast
 */
final class PhpParser
{
    private \PhpParser\Parser $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
    }

    /**
     * Parse PHP source code into AST nodes
     *
     * @param string $source PHP source code
     * @return Node[]
     * @throws ParseException
     */
    public function parse(string $source): array
    {
        try {
            $stmts = $this->parser->parse($source);

            if ($stmts === null) {
                return [];
            }

            // Apply name resolution for FQN
            $traverser = new NodeTraverser();
            $traverser->addVisitor(new NameResolver());

            return $traverser->traverse($stmts);
        } catch (Error $e) {
            throw new ParseException(
                "PHP parse error: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Parse PHP file into AST nodes
     *
     * @param string $filePath Absolute file path
     * @return Node[]
     * @throws ParseException
     */
    public function parseFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new ParseException("File not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new ParseException("File not readable: {$filePath}");
        }

        $source = file_get_contents($filePath);

        if ($source === false) {
            throw new ParseException("Failed to read file: {$filePath}");
        }

        return $this->parse($source);
    }
}
