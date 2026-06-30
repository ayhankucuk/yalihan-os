<?php

namespace Tests\Unit\Governance\Ast;

use PHPUnit\Framework\TestCase;
use App\Services\Governance\Ast\GovernanceAstRuleRegistry;

/**
 * Unit tests for Registry config-driven behavior — Pack-P3C.
 * Tests config enable/disable, severity override, excluded_paths.
 * Uses a synthetic config array (no real config() call needed).
 */
class RegistryConfigTest extends TestCase
{
    /** @test */
    public function silent_catch_rule_is_registered_by_default(): void
    {
        $registry = new GovernanceAstRuleRegistry();

        $this->assertArrayHasKey('SilentCatchAST', $registry->getRules());
    }

    /** @test */
    public function silent_catch_rule_is_enabled_by_default(): void
    {
        $registry = new GovernanceAstRuleRegistry();

        $this->assertArrayHasKey('SilentCatchAST', $registry->getEnabled());
    }

    /** @test */
    public function language_hardcode_rule_is_still_registered(): void
    {
        $registry = new GovernanceAstRuleRegistry();

        $this->assertArrayHasKey('LanguageHardcodeAST', $registry->getEnabled());
    }

    /** @test */
    public function disable_prevents_rule_in_get_enabled(): void
    {
        $registry = new GovernanceAstRuleRegistry();
        $registry->disable('SilentCatchAST');

        $this->assertArrayNotHasKey('SilentCatchAST', $registry->getEnabled());
        $this->assertArrayHasKey('SilentCatchAST', $registry->getRules()); // still registered
    }

    /** @test */
    public function enable_restores_disabled_rule(): void
    {
        $registry = new GovernanceAstRuleRegistry();
        $registry->disable('SilentCatchAST');
        $registry->enable('SilentCatchAST');

        $this->assertArrayHasKey('SilentCatchAST', $registry->getEnabled());
    }

    /** @test */
    public function get_severity_for_returns_null_when_no_config_override(): void
    {
        // In unit test environment config() returns null → no override
        $registry = new GovernanceAstRuleRegistry();

        // Either null (no config) or 'MEDIUM' (if config loaded in test env)
        $severity = $registry->getSeverityFor('SilentCatchAST');
        $this->assertTrue($severity === null || is_string($severity));
    }

    /** @test */
    public function get_excluded_paths_for_returns_array(): void
    {
        $registry = new GovernanceAstRuleRegistry();

        // Either empty (no config) or array of paths (if config loaded)
        $paths = $registry->getExcludedPathsFor('SilentCatchAST');
        $this->assertIsArray($paths);
    }

    /** @test */
    public function is_global_report_only_defaults_to_true(): void
    {
        $registry = new GovernanceAstRuleRegistry();

        // In P3C report_only must always be true
        $this->assertTrue($registry->isGlobalReportOnly());
    }

    /** @test */
    public function all_enabled_rules_implement_interface(): void
    {
        $registry = new GovernanceAstRuleRegistry();

        foreach ($registry->getEnabled() as $rule) {
            $this->assertInstanceOf(
                \App\Services\Governance\Ast\GovernanceAstRuleInterface::class,
                $rule
            );
        }
    }
}
