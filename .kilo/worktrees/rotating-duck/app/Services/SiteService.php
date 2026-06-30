<?php

namespace App\Services;

use App\Models\SiteApartman;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Traits\GuardsAgentWrites;

/**
 * Site/Apartman Hizmeti (Business Logic)
 *
 * L5 Controller Isolation gereği DB işlemlerini barındırır.
 */
class SiteService
{
    use GuardsAgentWrites;
    /**
     * @param string $query
     * @param int|null $ilId
     * @param int|null $ilceId
     * @param int $limit
     * @return Collection
     */
    public function searchSites(string $query, ?int $ilId = null, ?int $ilceId = null, int $limit = 20): Collection
    {
        $sitesQuery = SiteApartman::where('name', 'like', "%{$query}%");

        if ($ilId) {
            $sitesQuery->where('il_id', $ilId);
        }

        if ($ilceId) {
            $sitesQuery->where('ilce_id', $ilceId);
        }

        return $sitesQuery->with(['il:id,name', 'ilce:id,name', 'mahalle:id,name'])
            ->select(['id', 'name', 'blok_adi', 'adres', 'il_id', 'ilce_id', 'mahalle_id'])
            ->limit($limit)
            ->orderBy('name') // context7-ignore
            ->get();
    }

    /**
     * @param array $data
     * @return array
     */
    public function formatSiteForSearch(SiteApartman $site): array
    {
        return [
            'id' => $site->id,
            'name' => $site->name,
            'blok_adi' => $site->blok_adi,
            'adres' => $site->adres,
            'full_address' => $this->buildFullAddress($site),
            'il_id' => $site->il_id,
            'ilce_id' => $site->ilce_id,
            'mahalle_id' => $site->mahalle_id,
            'display_text' => $this->buildDisplayText($site),
        ];
    }

    /**
     * @param array $data
     * @return SiteApartman|null Returns SiteApartman if created, null if duplicate
     */
    public function createSiteObject(array $data): ?SiteApartman
    {
        $this->blockAgentWrite(__FUNCTION__);

        $existingSite = SiteApartman::where('name', $data['name'])
            ->where('il_id', $data['il_id'])
            ->where('ilce_id', $data['ilce_id'])
            ->first();

        if ($existingSite) {
            $existingSite->display_text = $this->buildDisplayText($existingSite);
            return $existingSite;
        }

        $site = SiteApartman::create([
            'name' => $data['name'],
            'blok_adi' => $data['blok_adi'] ?? null,
            'adres' => $data['adres'] ?? null,
            'il_id' => $data['il_id'],
            'ilce_id' => $data['ilce_id'],
            'mahalle_id' => $data['mahalle_id'] ?? null,
            'created_by' => auth()->id(),
        ]);

        $site->load(['il:id,name', 'ilce:id,name', 'mahalle:id,name']);

        return $site;
    }

    /**
     * @param int $id
     * @return SiteApartman
     */
    public function getSiteDetails(int $id): SiteApartman
    {
        return SiteApartman::with(['il:id,name', 'ilce:id,name', 'mahalle:id,name'])
            ->findOrFail($id);
    }

    /**
     * @param SiteApartman $site
     * @return array
     */
    public function buildSiteDetailsResponse(SiteApartman $site): array
    {
        return [
            'id' => $site->id,
            'name' => $site->name,
            'blok_adi' => $site->blok_adi,
            'adres' => $site->adres,
            'full_address' => $this->buildFullAddress($site),
            'il_id' => $site->il_id,
            'ilce_id' => $site->ilce_id,
            'mahalle_id' => $site->mahalle_id,
            'aktiflik_durumu' => $site->aktiflik_durumu,
            'state' => $site->aktiflik_durumu, // context7-ignore
            'display_text' => $this->buildDisplayText($site),
        ];
    }

    /**
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getSitesList(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = SiteApartman::with(['il:id,name', 'ilce:id,name', 'mahalle:id,name']);

        if (!empty($filters['il_id'])) {
            $query->where('il_id', $filters['il_id']);
        }

        if (!empty($filters['ilce_id'])) {
            $query->where('ilce_id', $filters['ilce_id']);
        }

        return $query->orderBy('name')->paginate($perPage); // context7-ignore
    }

    /**
     * @param SiteApartman $site
     * @return array
     */
    public function formatSiteForList(SiteApartman $site): array
    {
        return [
            'id' => $site->id,
            'name' => $site->name,
            'blok_adi' => $site->blok_adi,
            'adres' => $site->adres,
            'full_address' => $this->buildFullAddress($site),
            'aktiflik_durumu' => $site->aktiflik_durumu,
            'state' => $site->aktiflik_durumu, // context7-ignore
            'display_text' => $this->buildDisplayText($site),
            'created_at' => $site->created_at?->format('d.m.Y H:i'),
        ];
    }

    /**
     * Tam adres oluşturma
     */
    public function buildFullAddress(SiteApartman $site): string
    {
        $parts = [];

        if ($site->adres) {
            $parts[] = $site->adres;
        }

        if ($site->mahalle && $site->mahalle->name) {
            $parts[] = $site->mahalle->name;
        }

        if ($site->ilce && $site->ilce->name) {
            $parts[] = $site->ilce->name;
        }

        if ($site->il && $site->il->name) {
            $parts[] = $site->il->name;
        }

        return implode(', ', $parts);
    }

    /**
     * Gösterim metni oluşturma
     */
    public function buildDisplayText(SiteApartman $site): string
    {
        $text = $site->name;

        if ($site->blok_adi) {
            $text .= " ({$site->blok_adi})";
        }

        if ($site->ilce && $site->ilce->name) {
            $text .= " - {$site->ilce->name}";
        }

        if ($site->il && $site->il->name) {
            $text .= ", {$site->il->name}";
        }

        return $text;
    }
}
