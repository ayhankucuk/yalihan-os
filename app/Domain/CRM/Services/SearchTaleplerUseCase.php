<?php

namespace App\Domain\CRM\Services;

use App\Domain\CRM\Contracts\TalepRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * SearchTaleplerUseCase — Application Service (Domain Layer)
 *
 * Handles keyword-based AJAX search for Talepler via Driven Port.
 */
class SearchTaleplerUseCase
{
    public function __construct(
        private readonly TalepRepositoryInterface $talepRepository,
    ) {}

    /**
     * Search Talepler by query string.
     *
     * @return Collection<\App\Models\Talep>
     */
    public function execute(string $searchQuery, int $limit = 20): Collection
    {
        return $this->talepRepository->search($searchQuery, $limit);
    }
}
