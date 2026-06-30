<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Rules\Registry;

use App\Domain\PropertyHub\Resolution\DTOs\ResolutionContext;
use App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry;
use App\Domain\PropertyHub\Rules\Contracts\RuleRegistryInterface;
use App\Domain\PropertyHub\Rules\DTOs\Rule;
use Illuminate\Support\Collection;

/**
 * Governed Rule Registry (SSOT)
 *
 * Loads rules from the ACTIVE configuration snapshot via ActiveConfigRegistry.
 * Ensures determinism by using the signed, versioned state instead of live DB.
 */
final class GovernedRuleRegistry implements RuleRegistryInterface
{
    public function __construct(
        private readonly ActiveConfigRegistry $registry
    ) {}

    /**
     * Get all applicable rules for the given context.
     *
     * Sources from the in-memory snapshot of the ACTIVE version.
     */
    public function getRulesForContext(ResolutionContext $context): Collection
    {
        // Get raw rule definitions from the snapshot
        $ruleDefinitions = $this->registry->getGovernedRules();

        if (empty($ruleDefinitions)) {
            return collect();
        }

        // Map to Domain Rule DTOs
        // Note: Snapshot data arrays match the RuleDefinition model structure
        return collect($ruleDefinitions)
            ->where('aktif', true) // Filter active rules (though snapshot should only contain active ones, double check)
            ->map(function ($ruleDef) {
                // Ensure array keys match snapshot structure
                return new Rule(
                    id: $ruleDef['id'],
                    name: $ruleDef['name'],
                    ruleType: $ruleDef['rule_type'],
                    config: is_string($ruleDef['rule_config'])
                        ? json_decode($ruleDef['rule_config'], true)
                        : $ruleDef['rule_config'],
                    priority: $ruleDef['priority'],
                    isActive: (bool) $ruleDef['aktif']
                );
            })
            ->sortBy('priority') // Snapshot might be sorted, but re-sort to be safe
            ->values();
    }

    /**
     * Get the version hash of the currently active rule set.
     */
    public function getActiveVersionHash(): string
    {
        $version = $this->registry->getActiveVersion();
        return $version ? $version->version_hash : 'NO_ACTIVE_VERSION';
    }
}
