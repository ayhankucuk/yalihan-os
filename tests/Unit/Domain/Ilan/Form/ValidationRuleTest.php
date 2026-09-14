<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ilan\Form;

use App\Domain\Ilan\ValueObjects\ValidationRule;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidationRuleTest extends TestCase
{
    public function test_creates_valid_rule(): void
    {
        $rule = ValidationRule::create('select', true, ['1+1', '2+1'], null, null, null, null);

        $this->assertSame('select', $rule->type());
        $this->assertTrue($rule->isRequired());
        $this->assertSame(['1+1', '2+1'], $rule->options());
    }

    public function test_throws_exception_for_invalid_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValidationRule::create('unsupported_type');
    }

    public function test_validates_number_range(): void
    {
        $rule = ValidationRule::create('number', true, [], 10, 500);

        $this->assertTrue($rule->isValid(100));
        $this->assertTrue($rule->isValid('250'));
        $this->assertFalse($rule->isValid(5));
        $this->assertFalse($rule->isValid(600));
        $this->assertFalse($rule->isValid('abc'));
    }

    public function test_validates_select_options(): void
    {
        $rule = ValidationRule::create('select', true, ['1+1', '2+1', '3+1']);

        $this->assertTrue($rule->isValid('2+1'));
        $this->assertFalse($rule->isValid('5+1'));
    }

    public function test_validates_boolean_values(): void
    {
        $rule = ValidationRule::create('boolean', false);

        $this->assertTrue($rule->isValid(true));
        $this->assertTrue($rule->isValid(false));
        $this->assertTrue($rule->isValid(1));
        $this->assertTrue($rule->isValid(0));
        $this->assertTrue($rule->isValid('1'));
        $this->assertTrue($rule->isValid('0'));
    }

    public function test_empty_value_allowed_when_optional(): void
    {
        $rule = ValidationRule::create('text', false);
        $this->assertTrue($rule->isValid(null));
        $this->assertTrue($rule->isValid(''));
    }

    public function test_empty_value_rejected_when_required(): void
    {
        $rule = ValidationRule::create('text', true);
        $this->assertFalse($rule->isValid(null));
        $this->assertFalse($rule->isValid(''));
    }
}
