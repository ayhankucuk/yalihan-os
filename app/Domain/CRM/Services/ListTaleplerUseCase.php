<?php

namespace App\Domain\CRM\Services;

use App\Domain\CRM\Contracts\TalepRepositoryInterface;
use App\Domain\CRM\DTOs\TalepListCriteria;
use App\Models\IlanKategori;
use App\Models\Il;
use App\Models\Ulke;
use App\Models\User;
use App\Repositories\TalepRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * ListTaleplerUseCase — Application Service (Domain Layer)
 *
 * Orchestrates read operations: paginated listing, stats, form data, and search.
 * Implements Port & Adapter pattern — depends only on TalepRepositoryInterface.
 *
 * Strangler Fig: enable via config('crm.use_domain_talep', false)
 */
class ListTaleplerUseCase
{
    public function __construct(
        private readonly TalepRepositoryInterface $repository
    ) {}

    /**
     * Paginated list with optional filters.
     */
    public function execute(TalepListCriteria|array $criteria): LengthAwarePaginator
    {
        $filters = $criteria instanceof TalepListCriteria
            ? $criteria->toArray()
            : $criteria;

        return $this->repository->getTalepler($filters, $filters['per_page'] ?? 20);
    }

    /**
     * Summary statistics for the Talep dashboard.
     *
     * @return array{toplam: int, aktif: int, beklemede: int, eslesen: int}
     */
    public function getSummaryStats(): array
    {
        return $this->repository->getSummaryStats();
    }

    /**
     * Unique statuses available in the system.
     *
     * @return Collection<int, string>
     */
    public function getAvailableStatuses(): Collection
    {
        return $this->repository->getAvailableStatuses();
    }

    /**
     * All form data required for Create/Edit Talep forms.
     * Cached lookups are intentionally tenant-agnostic (PUBLIC_CORPUS).
     *
     * @return array{
     *   iller: Collection,
     *   kategoriler: Collection,
     *   danismanlar: Collection,
     *   ulkeler: Collection,
     *   statuslar: array,
     *   talepTipleri: array,
     *   emlakTipleri: array
     * }
     */
    public function getFormData(): array
    {
        return [
            'iller'        => $this->getCachedIller(),
            'kategoriler'  => $this->getCachedKategoriler(),
            'danismanlar'  => $this->getDanismanlar(),
            'ulkeler'      => $this->getCachedUlkeler(),
            'statuslar'    => \App\Enums\TalepDurumu::options(),
            'talepTipleri' => $this->getTalepTipleri(),
            'emlakTipleri' => $this->getTalepTipleri(),
        ];
    }

    /**
     * Find a single Talep by ID (ownership-scoped).
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): \App\Models\Talep
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * AJAX search for Talepler.
     *
     * @return Collection<int, \App\Models\Talep>
     */
    public function search(string $query, int $limit = 20): Collection
    {
        return $this->repository->search($query, $limit);
    }

    // ─── Private Cached Helpers ───────────────────────────────────────────

    private function getCachedIller(): Collection
    {
        return Cache::remember('il_list', 7200, fn() => Il::select(['id', 'il_adi'])->orderBy('il_adi')->get());
    }

    private function getCachedKategoriler(): Collection
    {
        return Cache::remember('talep_kategori_list', 3600, function () {
            return IlanKategori::whereNull('parent_id')
                ->select(['id', 'name', 'slug'])
                ->orderBy('name')
                ->get();
        });
    }

    private function getCachedUlkeler(): Collection
    {
        return Cache::remember('ulke_list', 7200, fn() => Ulke::select(['id', 'ulke_adi', 'ulke_kodu'])->orderBy('ulke_adi')->get());
    }

    private function getDanismanlar(): Collection
    {
        return User::whereHas('roles', fn($q) => $q->where('name', 'danisman'))
            ->select(['id', 'name', 'email'])
            ->get();
    }

    private function getTalepTipleri(): array
    {
        // Mirrors TalepOrchestrator::getTalepTipleri() exactly
        $types = array_map(
            fn($v) => \App\Enums\YayinTipi::from($v)->label(),
            \App\Enums\YayinTipi::values()
        );
        return array_unique(array_merge($types, [
            'Konut', 'Arsa', 'İşyeri', 'Satılık', 'Kiralık', 'Günlük Kiralık', 'Devren',
        ]));
    }
}
