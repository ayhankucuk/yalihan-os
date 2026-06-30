<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Resolution\Engine;

use App\Domain\PropertyHub\Resolution\Contracts\TemplateResolutionEngineInterface;
use App\Domain\PropertyHub\Resolution\DTOs\ResolutionContext;
use App\Domain\PropertyHub\Resolution\DTOs\ResolutionResult;
use App\Domain\PropertyHub\Rules\Contracts\RuleRegistryInterface;
use App\Domain\PropertyHub\Rules\Evaluators\ConditionEvaluator;
use Illuminate\Support\Collection;

/**
 * V3 Template Resolution Engine (Sprint 2 - Minimal)
 *
 * DESIGN:
 * - Linear pipeline (no caching)
 * - Simple priority-based rule sorting
 * - AND-only condition logic
 * - Direct DB load (no preloading)
 */
final class TemplateResolutionEngine implements TemplateResolutionEngineInterface
{
    public function __construct(
        private readonly RuleRegistryInterface $registry,
        private readonly ConditionEvaluator $evaluator,
    ) {}

    /**
     * Resolve template and features for the given context.
     */
    public function resolve(ResolutionContext $context): ResolutionResult
    {
        $trace = [];

        // Step 1: Load active rules
        $rules = $this->registry->getRulesForContext($context);
        $trace[] = "Loaded {$rules->count()} rules from registry";

        // Step 2: Filter by scope (evaluate conditions)
        $matched = $this->filterByScope($rules, $context);
        $trace[] = "Matched {$matched->count()} rules after condition evaluation";

        // Step 3: Sort by priority (lower = higher priority)
        $sorted = $this->sortByPriority($matched);
        $trace[] = "Sorted rules by priority";

        // Step 4: Apply rules to produce output
        $output = $this->applyRules($sorted, $context);
        $trace[] = "Applied rules to produce final output";

        // Step 5: Compute signature
        $result = new ResolutionResult(
            templateId: $output['template_id'],
            features: $output['features'],
            fieldDependencies: $output['field_dependencies'],
            signature: '', // Will be computed after construction
            outputSignature: '',
            trace: $trace,
            meta: [
                'rules_evaluated' => $rules->count(),
                'rules_matched' => $matched->count(),
            ]
        );

        // Compute and validate signature
        $signature = $result->computeSignature();

        return new ResolutionResult(
            templateId: $result->templateId,
            features: $result->features,
            fieldDependencies: $result->fieldDependencies,
            signature: $signature,
            outputSignature: $signature,
            trace: $result->trace,
            meta: $result->meta
        );
    }

    /**
     * Filter rules by evaluating their conditions against the context.
     */
    private function filterByScope(Collection $rules, ResolutionContext $context): Collection
    {
        return $rules->filter(function ($rule) use ($context) {
            $conditions = $rule->getConditions();
            return $this->evaluator->evaluate($conditions, $context);
        });
    }

    /**
     * Sort rules by priority (lower number = higher priority).
     */
    private function sortByPriority(Collection $rules): Collection
    {
        return $rules->sortBy('priority')->values();
    }

    /**
     * Apply rules to produce the final resolved output.
     *
     * Sprint 2: First rule wins for conflicts (rules are already sorted by priority).
     */
    private function applyRules(Collection $rules, ResolutionContext $context): array
    {
        $output = [
            'template_id' => null,
            'features' => [],
            'field_dependencies' => [],
        ];

        foreach ($rules as $rule) {
            $actions = $rule->getActions();

            // Apply template assignment (FIRST RULE WINS)
            if (isset($actions['assign_template']) && $output['template_id'] === null) {
                $output['template_id'] = $actions['assign_template'];
            }

            // Apply feature assignments (merge, first wins for duplicates)
            if (isset($actions['assign_features'])) {
                foreach ($actions['assign_features'] as $feature) {
                    $slug = $feature['slug'] ?? null;
                    if ($slug && !isset($output['features'][$slug])) {
                        $output['features'][$slug] = $feature;
                    }
                }
            }

            // Apply field dependencies (merge, first wins for duplicates)
            if (isset($actions['assign_fields'])) {
                foreach ($actions['assign_fields'] as $field) {
                    $fieldName = $field['field_name'] ?? null;
                    if ($fieldName && !isset($output['field_dependencies'][$fieldName])) {
                        $output['field_dependencies'][$fieldName] = $field;
                    }
                }
            }
        }

        // Convert associative arrays back to indexed arrays
        $output['features'] = array_values($output['features']);
        $output['field_dependencies'] = array_values($output['field_dependencies']);

        return $output;
    }
}
