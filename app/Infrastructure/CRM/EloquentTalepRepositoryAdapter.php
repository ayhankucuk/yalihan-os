<?php

namespace App\Infrastructure\CRM;

use App\Domain\CRM\Contracts\TalepRepositoryInterface;
use App\Domain\CRM\DTOs\TalepListCriteria;
use App\Models\Talep;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

/**
 * EloquentTalepRepositoryAdapter — Infrastructure Adapter
 *
 * Wraps the legacy TalepRepository and implements TalepRepositoryInterface.
 * Write operations delegate to TalepAuthorityService (existing behavior).
 */
class EloquentTalepRepositoryAdapter implements TalepRepositoryInterface
{
    public function __construct(
        private readonly \App\Repositories\TalepRepository $repository
    ) {}

    public function getTalepler(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $criteria = TalepListCriteria::fromArray($filters);
        $legacyFilters = $criteria->toArray();

        // Map 'search' (interface contract) → 'q' (legacy repository contract)
        if (array_key_exists('search', $legacyFilters)) {
            $legacyFilters['q'] = $legacyFilters['search'];
            unset($legacyFilters['search']);
        }

        // Map 'status' (interface contract) → 'talep_durumu' (legacy repository contract)
        if (array_key_exists('status', $legacyFilters)) {
            $legacyFilters['talep_durumu'] = $legacyFilters['status'];
            unset($legacyFilters['status']);
        }

        // Extract il_id for post-filtering (legacy repo doesn't support it)
        $ilId = $legacyFilters['il_id'] ?? null;
        unset($legacyFilters['il_id']);

        // Extract danisman_id for post-filtering (legacy repo doesn't support it)
        $danismanId = $legacyFilters['danisman_id'] ?? null;
        unset($legacyFilters['danisman_id']);

        // Extract tarih_from / tarih_to for post-filtering (legacy repo doesn't support it)
        $tarihFrom = $legacyFilters['tarih_from'] ?? null;
        $tarihTo   = $legacyFilters['tarih_to'] ?? null;
        unset($legacyFilters['tarih_from'], $legacyFilters['tarih_to']);

        // kategori_id → alt_kategori_id mapping for legacy repo
        if (isset($legacyFilters['kategori_id'])) {
            $legacyFilters['alt_kategori_id'] = $legacyFilters['kategori_id'];
            unset($legacyFilters['kategori_id']);
        }

        $paginated = $this->repository->getTalepler($legacyFilters, $perPage);

        // Domain-level post-filtering (legacy repo doesn't support these filters)
        $items = $paginated->getCollection();

        if ($ilId !== null) {
            $items = $items->filter(fn($t) => (int) $t->il_id === (int) $ilId)->values();
        }

        if ($danismanId !== null) {
            $items = $items->filter(fn($t) => (int) $t->danisman_id === (int) $danismanId)->values();
        }

        if ($tarihFrom !== null) {
            $from = \Carbon\Carbon::parse($tarihFrom)->startOfDay();
            $items = $items->filter(fn($t) => $t->created_at && $t->created_at->gte($from))->values();
        }

        if ($tarihTo !== null) {
            $to = \Carbon\Carbon::parse($tarihTo)->endOfDay();
            $items = $items->filter(fn($t) => $t->created_at && $t->created_at->lte($to))->values();
        }

        return new Paginator($items, $perPage, $paginated->currentPage(), [
            'total' => $items->count(),
        ]);
    }

    public function getSummaryStats(): array
    {
        return $this->repository->getSummaryStats();
    }

    public function getAvailableStatuses(): Collection
    {
        return $this->repository->getAvailableStatuses();
    }

    public function findOrFail(int $id): Talep
    {
        return $this->repository->findOrFail($id);
    }

    public function search(string $searchQuery, int $limit = 20): Collection
    {
        return $this->repository->search($searchQuery, $limit);
    }
}
