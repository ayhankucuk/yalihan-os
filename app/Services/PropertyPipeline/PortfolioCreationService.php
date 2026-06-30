<?php

declare(strict_types=1);

namespace App\Services\PropertyPipeline;

use App\Contracts\IlanCrudServiceInterface;
use App\Models\Ilan;

/**
 * PortfolioCreationService — P01 Sprint 4.1
 *
 * Thin service: translates pipeline input → IlanCrudService::store() call.
 * All writes go through IlanCrudService (SAB write authority).
 */
class PortfolioCreationService
{
    public function __construct(
        private IlanCrudService $crudService,
    ) {}

    /**
     * @param array{title: string, city: string, owner_id: int, tenant_id: int} $input
     * @return Ilan
     */
    public function create(array $input): Ilan
    {
        $ilan = $this->crudService->store($input);
        return $ilan;
    }
}
