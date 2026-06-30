<?php

namespace App\Services\CRM;

use App\Models\Talep;
use App\Models\Ulke;
use App\Models\IlanKategori;
use App\Models\Il;
use App\Models\User;
use App\Enums\TalepDurumu;
use App\Enums\YayinTipi;
use App\Services\CRMIntelligenceService;
use App\Services\Matching\DemandMatchingEngine;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Repositories\TalepRepository;

/**
 * 🛰️ TalepOrchestrator
 *
 * Orchestrates the Demand (Talep) domain surfaces.
 * Consolidates filtering, stats, form data, and matching coordination.
 * Now delegates all reads to TalepRepository for Fail-Safe Ownership Scoping.
 *
 * @governance PUBLIC_CORPUS — il_list, talep_kategori_list, ulke_list global lookup cache'leri tenant-agnostic kasıtlı
 */
class TalepOrchestrator
{
    public function __construct(
        private readonly CRMIntelligenceService $intelligenceService,
        private readonly DemandMatchingEngine $matchingEngine,
        private readonly TalepRepository $repository
    ) {}

    /**
     * Get paginated and filtered list of Talepler.
     */
    public function getTalepler(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->getTalepler($filters, $perPage);
    }

    /**
     * Get summary statistics for the Talep domain.
     */
    public function getSummaryStats(): array
    {
        return $this->repository->getSummaryStats();
    }

    /**
     * Get unique statuses available in the system.
     */
    public function getAvailableStatuses(): Collection
    {
        return $this->repository->getAvailableStatuses();
    }

    /**
     * Get all form data required for Create/Edit Talepler.
     */
    public function getFormData(): array
    {
        return [
            'iller'       => $this->getCachedIller(),
            'kategoriler' => $this->getCachedKategoriler(),
            'danismanlar' => $this->getDanismanlar(),
            'ulkeler'     => $this->getCachedUlkeler(),
            'statuslar'   => TalepDurumu::options(),
            'talepTipleri' => $this->getTalepTipleri(),
            'emlakTipleri' => ['Daire', 'Villa', 'Arsa', 'İşyeri', 'Yazlık'],
        ];
    }

    /**
     * Get matches for a specific Talep (Traditional + Semantic).
     */
    public function getMatches(Talep $talep, int $limit = 20): array
    {
        $talep->load(['kisi', 'altKategori']);

        return [
            'eslesenIlanlar'  => $this->matchingEngine->matchDemand($talep, $limit),
            'semanticMatches' => $this->intelligenceService->getRecommendedListings($talep->kisi, 10),
        ];
    }

    // --- Private Helpers (Cached Data) ---

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
        $types = array_map(fn($v) => YayinTipi::from($v)->label(), YayinTipi::values());
        return array_unique(array_merge($types, ['Konut', 'Arsa', 'İşyeri', 'Satılık', 'Kiralık', 'Günlük Kiralık', 'Devren']));
    }
}
