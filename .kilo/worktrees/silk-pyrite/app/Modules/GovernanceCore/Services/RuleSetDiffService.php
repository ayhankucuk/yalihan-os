<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore\Services;

use App\Models\PropertyConfigVersion;
use Illuminate\Support\Collection;

/**
 * RuleSet Diff Engine
 *
 * Compares two versions of RuleDefinitions deterministically.
 */
class RuleSetDiffService
{
    /**
     * Compare two versions and return structured diff.
     */
    public function compare(PropertyConfigVersion $from, PropertyConfigVersion $to): array
    {
        $fromRules = $from->rules()->get()->keyBy('name');
        $toRules = $to->rules()->get()->keyBy('name');

        $added = [];
        $removed = [];
        $modified = [];

        // Identify added and modified
        foreach ($toRules as $name => $toRule) {
            if (!$fromRules->has($name)) {
                $added[] = $this->formatRule($toRule);
                continue;
            }

            $fromRule = $fromRules->get($name);
            if ($this->hasChanged($fromRule, $toRule)) {
                $modified[] = [
                    'name' => $name,
                    'before' => $this->formatRule($fromRule),
                    'after' => $this->formatRule($toRule),
                ];
            }
        }

        // Identify removed
        foreach ($fromRules as $name => $fromRule) {
            if (!$toRules->has($name)) {
                $removed[] = $this->formatRule($fromRule);
            }
        }

        return [
            'added' => $this->sortByName($added),
            'removed' => $this->sortByName($removed),
            'modified' => $this->sortByName($modified),
        ];
    }

    /**
     * Check if rule configuration or priority has changed.
     */
    private function hasChanged($ruleA, $ruleB): bool
    {
        return json_encode($ruleA->rule_config) !== json_encode($ruleB->rule_config)
            || $ruleA->priority !== $ruleB->priority
            || $ruleA->rule_type !== $ruleB->rule_type;
    }

    /**
     * Format rule model for diff.
     */
    private function formatRule($rule): array
    {
        return [
            'name' => $rule->name,
            'type' => $rule->rule_type,
            'config' => $rule->rule_config,
            'priority' => $rule->priority,
        ];
    }

    /**
     * Sort collection by name for deterministic results.
     */
    private function sortByName(array $items): array
    {
        usort($items, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $items;
    }
}
