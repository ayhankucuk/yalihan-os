<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Rules\Registry;

use App\Domain\PropertyHub\Resolution\DTOs\ResolutionContext;
use App\Domain\PropertyHub\Rules\Contracts\RuleRegistryInterface;
use App\Domain\PropertyHub\Rules\DTOs\Rule;
use App\Models\PropertyConfigVersion;
use App\Models\RuleDefinition;
use Illuminate\Support\Collection;

/**
 * Database-backed Rule Registry (Sprint 2 - No Cache)
 *
 * CRITICAL RULES:
 * - Only loads rules from ACTIVE version
 * - No DRAFT, REVIEW, or APPROVED rules
 * - Direct DB query (no caching in Sprint 2)
 */
final class DatabaseRuleRegistry implements RuleRegistryInterface
{
    /**
     * Get all applicable rules for the given context.
     *
     * STRICT: Only returns rules from the ACTIVE version.
     */
    public function getRulesForContext(ResolutionContext $context): Collection
    {
        // Get the ACTIVE version (enforced by DB constraint)
        $activeVersion = PropertyConfigVersion::where('governance_state', 'ACTIVE')->first();

        if (!$activeVersion) {
            // No ACTIVE version = no rules (system not initialized)
            return collect();
        }

        // Load all active rules from the ACTIVE version
        $ruleDefinitions = RuleDefinition::query()
            ->where('version_id', $activeVersion->id)
            ->where('aktif', true)
            ->orderBy('priority', 'asc') // Pre-sort by priority
            ->get();

        // Map to Domain Rule DTOs
        return $ruleDefinitions->map(function ($ruleDef) {
            return new Rule(
                id: $ruleDef->id,
                name: $ruleDef->name,
                ruleType: $ruleDef->rule_type,
                config: $ruleDef->rule_config, // Already decoded by Eloquent cast
                priority: $ruleDef->priority,
                isActive: $ruleDef->aktif,
            );
        });
    }

    /**
     * Get the version hash of the currently active rule set.
     */
    public function getActiveVersionHash(): string
    {
        $activeVersion = PropertyConfigVersion::where('governance_state', 'ACTIVE')->first();

        if (!$activeVersion) {
            return 'NO_ACTIVE_VERSION';
        }

        return $activeVersion->version_hash;
    }
}
