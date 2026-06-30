<?php

namespace App\Services\Governance\Ast;

use App\Services\Governance\Ast\Rules\LanguageHardcodedArrayAstRule;
use App\Services\Governance\Ast\Rules\SilentCatchAstRule;

/**
 * GovernanceAstRuleRegistry — Pack-P3C
 *
 * Manages AST rule registration, enable/disable, and config-driven loading.
 * Auto-loads rule configuration from config/sab_ast.php when available.
 * Falls back to safe defaults if config is missing (boot-safe).
 */
class GovernanceAstRuleRegistry
{
    /** @var GovernanceAstRuleInterface[] */
    private array $rules = [];

    /** @var string[] — rule IDs explicitly disabled via disable() */
    private array $disabled = [];

    /** @var array — merged config from sab_ast.php */
    private array $config = [];

    public function __construct()
    {
        $this->loadConfig();
        $this->registerDefaultRules();
    }

    /**
     * Load AST config from config/sab_ast.php (graceful — no exception if missing).
     */
    private function loadConfig(): void
    {
        try {
            // Works both in Laravel (config() helper) and plain PHP unit tests
            if (function_exists('config')) {
                $cfg = config('sab_ast');
                if (is_array($cfg)) {
                    $this->config = $cfg;
                    return;
                }
            }
        } catch (\Throwable $e) {
            // Silenced intentionally: config may not be available in pure unit test environments.
            // Log facade kullanılamaz (container boot edilmemiş olabilir) — error_log ile fallback
            error_log('[GovernanceAstRuleRegistry] config load failed: ' . $e->getMessage());
            if (function_exists('report') && function_exists('app') && app()->bound(\Illuminate\Contracts\Debug\ExceptionHandler::class)) {
                report($e);
            }
        }

        $this->config = [];
    }

    /**
     * Register all built-in AST rules.
     * Uses config/sab_ast.php to determine enabled state and severity.
     */
    private function registerDefaultRules(): void
    {
        $this->register(new LanguageHardcodedArrayAstRule());
        $this->register(new SilentCatchAstRule());
        $this->register(new \App\Services\Governance\Ast\Rules\ForbiddenFunctionAstRule());
        $this->register(new \App\Services\Governance\Ast\Rules\EnvUsageAstRule());
        $this->register(new \App\Services\Governance\Ast\Rules\ForbiddenFieldAstRule());
        $this->register(new \App\Services\Governance\Ast\Rules\NamingAuthorityAstRule());
    }

    public function register(GovernanceAstRuleInterface $rule): void
    {
        $this->rules[$rule->getRuleId()] = $rule;

        // If config says this rule is disabled, honour it
        $ruleCfg = $this->getRuleConfig($rule->getRuleId());
        if (isset($ruleCfg['enabled']) && $ruleCfg['enabled'] === false) {
            $this->disabled[] = $rule->getRuleId();
        }
    }

    /**
     * Disable a rule by ID.
     */
    public function disable(string $ruleId): void
    {
        if (!in_array($ruleId, $this->disabled, true)) {
            $this->disabled[] = $ruleId;
        }
    }

    /**
     * Re-enable a previously disabled rule.
     */
    public function enable(string $ruleId): void
    {
        $this->disabled = array_values(array_filter($this->disabled, fn($id) => $id !== $ruleId));
    }

    /**
     * Returns only rules that are enabled (not disabled + isEnabled() === true).
     *
     * @return GovernanceAstRuleInterface[]
     */
    public function getEnabled(): array
    {
        return array_filter(
            $this->rules,
            fn($rule) => $rule->isEnabled() && !in_array($rule->getRuleId(), $this->disabled, true)
        );
    }

    /**
     * Returns all registered rules regardless of enabled state.
     *
     * @return GovernanceAstRuleInterface[]
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Check if a rule ID is currently disabled.
     */
    public function isDisabled(string $ruleId): bool
    {
        return in_array($ruleId, $this->disabled, true);
    }

    /**
     * Get config-driven severity override for a rule (returns rule default if not overridden).
     */
    public function getSeverityFor(string $ruleId): ?string
    {
        $ruleCfg = $this->getRuleConfig($ruleId);
        return $ruleCfg['severity'] ?? null;
    }

    /**
     * Get config-driven extra excluded_paths for a rule.
     *
     * @return string[]
     */
    public function getExcludedPathsFor(string $ruleId): array
    {
        $ruleCfg = $this->getRuleConfig($ruleId);
        return $ruleCfg['excluded_paths'] ?? [];
    }

    /**
     * Whether the global report_only override is active.
     */
    public function isGlobalReportOnly(): bool
    {
        return (bool) ($this->config['report_only'] ?? true);
    }

    private function getRuleConfig(string $ruleId): array
    {
        return $this->config['rules'][$ruleId] ?? [];
    }
}
