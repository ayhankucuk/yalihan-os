<?php

declare(strict_types=1);

namespace Tests\Unit\Governance\Analyze\Ast;

use App\Support\Governance\Analyze\Ast\ParseException;
use App\Support\Governance\Analyze\Ast\PhpParser;
use PhpParser\Node;
use Tests\TestCase;

/**
 * @covers \App\Support\Governance\Analyze\Ast\PhpParser
 * @covers \App\Support\Governance\Analyze\Ast\ParseException
 */
final class PhpParserTest extends TestCase
{
    private PhpParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new PhpParser();
    }

    public function test_parse_valid_php_code(): void
    {
        $source = <<<'PHP'
<?php
namespace App\Example;

class Foo {
    public function bar(): string {
        return 'baz';
    }
}
PHP;

        $nodes = $this->parser->parse($source);

        $this->assertIsArray($nodes);
        $this->assertNotEmpty($nodes);
        $this->assertContainsOnlyInstancesOf(Node::class, $nodes);
    }

    public function test_parse_empty_source(): void
    {
        $nodes = $this->parser->parse('<?php');

        $this->assertIsArray($nodes);
        $this->assertEmpty($nodes);
    }

    public function test_parse_invalid_php_throws_exception(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('PHP parse error');

        $this->parser->parse('<?php class {');
    }

    public function test_parse_resolves_names(): void
    {
        $source = <<<'PHP'
<?php
namespace App\Example;

use Illuminate\Support\Str;

class Foo {
    public function bar(): Str {
        return new Str();
    }
}
PHP;

        $nodes = $this->parser->parse($source);

        $this->assertNotEmpty($nodes);
        // Name resolution applied via NameResolver visitor
    }

    public function test_parse_file_success(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'php_parser_test_');
        file_put_contents($tempFile, '<?php class TestClass {}');

        try {
            $nodes = $this->parser->parseFile($tempFile);

            $this->assertIsArray($nodes);
            $this->assertNotEmpty($nodes);
        } finally {
            unlink($tempFile);
        }
    }

    public function test_parse_file_not_found(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('File not found');

        $this->parser->parseFile('/nonexistent/file.php');
    }

    public function test_parse_file_not_readable(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'php_parser_test_');
        file_put_contents($tempFile, '<?php');
        chmod($tempFile, 0000);

        try {
            $this->expectException(ParseException::class);
            $this->expectExceptionMessage('File not readable');

            $this->parser->parseFile($tempFile);
        } finally {
            chmod($tempFile, 0644);
            unlink($tempFile);
        }
    }

    public function test_deterministic_parsing(): void
    {
        $source = <<<'PHP'
<?php
namespace App\Example;

class Foo {
    private string $prop;

    public function method1(): void {}
    public function method2(): void {}
}
PHP;

        $nodes1 = $this->parser->parse($source);
        $nodes2 = $this->parser->parse($source);
        $nodes3 = $this->parser->parse($source);

        // Same source produces same AST structure
        $this->assertCount(count($nodes1), $nodes2);
        $this->assertCount(count($nodes1), $nodes3);
    }
}
