<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Resolution\Contracts;

use App\Domain\PropertyHub\Resolution\DTOs\ResolutionContext;
use App\Domain\PropertyHub\Resolution\DTOs\ResolutionResult;

/**
 * The Core Contract for the V3 Engine.
 *
 * Responsibilities:
 * 1. Takes an immutable Context.
 * 2. Produces a deterministic Result with Signature.
 * 3. Handles no side-effects (Pure Function-like behavior).
 */
interface TemplateResolutionEngineInterface
{
    /**
     * Resolve the template and features for the given context.
     *
     * @param ResolutionContext $context
     * @return ResolutionResult
     * @throws \App\Domain\PropertyHub\Exceptions\ResolutionException
     */
    public function resolve(ResolutionContext $context): ResolutionResult;
}
