<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Rules\Evaluators;

use App\Domain\PropertyHub\Resolution\DTOs\ResolutionContext;

/**
 * Minimal Condition Evaluator (Sprint 2)
 *
 * CONSTRAINTS:
 * - Only AND logic supported
 * - Only equals (=) operator supported
 * - No nested conditions
 *
 * Condition JSON Format:
 * {
 *   "all": [
 *     { "field": "category_id", "operator": "=", "value": 3 },
 *     { "field": "publication_type_id", "operator": "=", "value": 1 }
 *   ]
 * }
 */
final class ConditionEvaluator
{
    /**
     * Evaluate a condition set against the context.
     *
     * @param array $conditions Decoded JSON condition array
     * @param ResolutionContext $context
     * @return bool True if all conditions match
     */
    public function evaluate(array $conditions, ResolutionContext $context): bool
    {
        // Empty conditions = always true (global rule)
        if (empty($conditions)) {
            return true;
        }

        // Extract "all" array (AND logic)
        $allConditions = $conditions['all'] ?? [];

        if (empty($allConditions)) {
            return true;
        }

        // Build context map for evaluation
        $contextMap = [
            'category_id' => $context->categoryId,
            'publication_type_id' => $context->publicationTypeId,
            'sub_category_id' => $context->subCategoryId,
        ];

        // Merge custom attributes
        foreach ($context->attributes as $key => $value) {
            $contextMap[$key] = $value;
        }

        // Evaluate each condition (AND logic)
        foreach ($allConditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $expectedValue = $condition['value'] ?? null;

            if ($field === null) {
                continue; // Skip malformed conditions
            }

            $actualValue = $contextMap[$field] ?? null;

            // Sprint 2: Only equals operator
            if ($operator !== '=') {
                throw new \InvalidArgumentException("Unsupported operator: {$operator}. Only '=' is supported in Sprint 2.");
            }

            // Strict equality check
            if ($actualValue != $expectedValue) {
                return false; // One condition failed = entire AND fails
            }
        }

        return true; // All conditions passed
    }
}
