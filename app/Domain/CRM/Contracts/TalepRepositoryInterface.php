<?php

namespace App\Domain\CRM\Contracts;

use App\Models\Talep;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * TalepRepositoryInterface — Driven Port (Port & Adapter Architecture)
 *
 * Abstracts Talep persistence so the domain use case is independent of ORM details.
 * Write operations are forbidden on this interface — use TalepAuthorityService for mutations.
 */
interface TalepRepositoryInterface
{
    /**
     * Paginated, filtered list of Talepler with ownership scoping applied.
     *
     * @param array{
     *   search?: string,
     *   status?: string,
     *   il_id?: int,
     *   kategori_id?: int,
     *   danisman_id?: int,
     *   tarih_from?: string,
     *   tarih_to?: string
     * } $filters
     */
    public function getTalepler(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Summary statistics for the Talep domain.
     *
     * @return array{toplam: int, aktif: int, beklemede: int, eslesen: int}
     */
    public function getSummaryStats(): array;

    /**
     * Unique statuses present in the system.
     *
     * @return Collection<int, string>
     */
    public function getAvailableStatuses(): Collection;

    /**
     * Find a Talep by ID with ownership scope enforced.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Talep;

    /**
     * Search Talepler by keyword (AJAX endpoint).
     *
     * @return Collection<int, Talep>
     */
    public function search(string $searchQuery, int $limit = 20): Collection;
}
