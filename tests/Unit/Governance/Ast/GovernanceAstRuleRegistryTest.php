<?php

namespace Tests\Unit\Governance\Ast;

use PHPUnit\Framework\TestCase;
use App\Services\Governance\Ast\GovernanceAstRuleRegistry;
use App\Services\Governance\Ast\Rules\LanguageHardcodedArrayAstRule;

/**
 * Unit tests for GovernanceAstRuleRegistry hardening (P3B).
 */
class GovernanceAstRuleRegistryTest extends TestCase
{
    /** @test */
    public function it_registers_default_rules_on_construct(): void
    {
        $registry = new GovernanceAstRuleRegistry();

        $this->assertArrayHasKey('LanguageHardcodeAST', $registry->getRules());
    }

    /** @test */
    public function get_enabled_returns_only_enabled_rules(): void
    {
        $registry = new GovernanceAstRuleRegistry();

        $enabled = $registry->getEnabled();

        $this->assertNotEmpty($enabled);
        foreach ($enabled as $rule) {
            $this->assertTrue($rule->isEnabled());
        }
    }

    /** @test */
    public function disable_prevents_rule_from_appearing_in_get_enabled(): void
    {
        $registry = new GovernanceAstRuleRegistry();

        $registry->disable('LanguageHardcodeAST');

        $this->assertArrayNotHasKey('LanguageHardcodeAST', $registry->getEnabled());
    }

    /** @test */
    public function enable_restores_a_disabled_rule(): void
    {
        $registry = new GovernanceAstRuleRegistry();

        $registry->disable('LanguageHardcodeAST');
        $registry->enable('LanguageHardcodeAST');

        $this->assertArrayHasKey('LanguageHardcodeAST', $registry->getEnabled());
    }

    /** @test */
    public function is_disabled_returns_correct_state(): void
    {
        $registry = new GovernanceAstRuleRegistry();

        $this->assertFalse($registry->isDisabled('LanguageHardcodeAST'));

        $registry->disable('LanguageHardcodeAST');

        $this->assertTrue($registry->isDisabled('LanguageHardcodeAST'));
    }

    /** @test */
    public function get_rules_returns_all_including_disabled(): void
    {
        $registry = new GovernanceAstRuleRegistry();
        $registry->disable('LanguageHardcodeAST');

        // getRules() returns ALL, getEnabled() returns only active
        $this->assertArrayHasKey('LanguageHardcodeAST', $registry->getRules());
        $this->assertArrayNotHasKey('LanguageHardcodeAST', $registry->getEnabled());
    }

    /** @test */
    public function language_rule_has_excluded_paths(): void
    {
        $rule = new LanguageHardcodedArrayAstRule();

        $excluded = $rule->getExcludedPaths();

        $this->assertContains('database/seeders', $excluded);
        $this->assertContains('tests/', $excluded);
    }

    /** @test */
    public function language_rule_is_enabled_by_default(): void
    {
        $rule = new LanguageHardcodedArrayAstRule();

        $this->assertTrue($rule->isEnabled());
    }
}
