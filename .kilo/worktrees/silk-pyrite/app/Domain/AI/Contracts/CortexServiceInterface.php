<?php

namespace App\Domain\AI\Contracts;

use App\Application\AI\DTOs\CortexRequestData;
use App\Application\AI\DTOs\CortexResponseData;
use App\Domain\AI\Enums\CortexCapability;

interface CortexServiceInterface
{
    public function execute(CortexRequestData $request): CortexResponseData;

    public function supports(CortexCapability $capability): bool;

    public function providerName(): string;
}
