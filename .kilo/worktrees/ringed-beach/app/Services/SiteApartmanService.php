<?php

namespace App\Services;

use App\Models\SiteApartman;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * 🛡️ SAB SEALED
 * Domain: Real Estate / Property Schema
 * Purpose: Abstract SiteApartman database queries to prevent Controller leakage.
 */
class SiteApartmanService
{
    /**
     * Get paginated list of sites with relationships.
     */
    public function getPaginatedSites(int $perPage = 20): LengthAwarePaginator
    {
        return SiteApartman::with(['il', 'ilce', 'mahalle', 'creator'])
            ->orderBy('name') // context7-ignore
            ->paginate($perPage);
    }

    /**
     * Search sites by name or address.
     */
    public function search(string $query, int $limit = 20): Collection
    {
        return SiteApartman::where('name', 'like', "%{$query}%")
            ->orWhere('adres', 'like', "%{$query}%")
            ->with(['il', 'ilce'])
            ->limit($limit)
            ->get()
            ->map(function ($site) {
                return [
                    'id' => $site->id,
                    'name' => $site->name,
                    'full_address' => $site->full_address,
                    'toplam_daire_sayisi' => $site->toplam_daire_sayisi,
                ];
            });
    }

    /**
     * Create a new SiteApartman.
     */
    public function create(array $data, int $userId): SiteApartman
    {
        return SiteApartman::create([
            'name' => $data['name'],
            'toplam_daire_sayisi' => $data['toplam_daire_sayisi'] ?? null,
            'adres' => $data['adres'] ?? null,
            'il_id' => $data['il_id'] ?? null,
            'ilce_id' => $data['ilce_id'] ?? null,
            'mahalle_id' => $data['mahalle_id'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'site_ozellikleri' => $data['site_ozellikleri'] ?? [],
            'site_durumu' => $data['site_durumu'],
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    /**
     * Update an existing SiteApartman.
     */
    public function update(SiteApartman $site, array $data, int $userId): bool
    {
        return $site->update([
            'name' => $data['name'],
            'toplam_daire_sayisi' => $data['toplam_daire_sayisi'] ?? null,
            'adres' => $data['adres'] ?? null,
            'il_id' => $data['il_id'] ?? null,
            'ilce_id' => $data['ilce_id'] ?? null,
            'mahalle_id' => $data['mahalle_id'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'site_ozellikleri' => $data['site_ozellikleri'] ?? [],
            'site_durumu' => $data['site_durumu'],
            'updated_by' => $userId,
        ]);
    }
}
