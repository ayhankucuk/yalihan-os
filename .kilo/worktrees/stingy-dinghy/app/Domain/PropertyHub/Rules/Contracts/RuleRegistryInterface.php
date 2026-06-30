<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Rules\Contracts;

use App\Domain\PropertyHub\Resolution\DTOs\ResolutionContext;
use Illuminate\Support\Collection;

/**
 * Interface for retrieving rules based on context.
 *
 * V3 Rule Registry handles loading, caching, and filtering of rules.
 */
interface RuleRegistryInterface
{
    /**
     * Load all applicable rules for the given context.
     *
     * @param ResolutionContext $context
     * @return Collection Collection of Rule objects (TBD)
     */
    public function getRulesForContext(ResolutionContext $context): Collection;

    /**
     * Get the version hash of the currently active rule set.
     */
    public function getActiveVersionHash(): string;
}
