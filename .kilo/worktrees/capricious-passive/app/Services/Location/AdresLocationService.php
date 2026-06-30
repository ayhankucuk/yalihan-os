<?php

namespace App\Services\Location;

use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use App\Models\Ulke;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Collection;

/**
 * 🛡️ SAB SEALED
 * Domain: Real Estate / Location Data
 * Purpose: Abstract database queries for administrative location boundaries to prevent Controller leakage.
 */
class AdresLocationService
{
    use GuardsAgentWrites;
    /**
     * Get provinces with their district counts.
     */
    public function getProvincesWithDistrictCounts(): Collection
    {
        return Il::select('id', 'il_adi', 'api_id')
            ->withCount('ilceler')
            ->get()
            ->map(function ($province) {
                $province->districts_count = $province->ilceler_count;
                return $province;
            });
    }

    /**
     * Get districts for a specific province by API ID.
     */
    public function getDistrictsByProvinceApiId(int $provinceApiId): Collection
    {
        $province = Il::where('api_id', $provinceApiId)->first();

        if (!$province) {
            return collect();
        }

        return Ilce::where('il_id', $province->id)
            ->select('id', 'ilce_adi', 'api_id')
            ->withCount('mahalleler')
            ->get()
            ->map(function ($district) {
                $district->neighborhoods_count = $district->mahalleler_count;
                return $district;
            });
    }

    /**
     * Get neighborhoods for a specific district by API ID.
     */
    public function getNeighborhoodsByDistrictApiId(int $districtApiId): Collection
    {
        $district = Ilce::where('api_id', $districtApiId)->first();

        if (!$district) {
            return collect();
        }

        return Mahalle::where('ilce_id', $district->id)
            ->select('id', 'mahalle_adi', 'api_id', 'lat', 'lng')
            ->get();
    }

    /**
     * Get an Il by ID optimizing N+1 with select.
     */
    public function getIl(int $id): Il
    {
        return Il::select(['id', 'il_adi'])->findOrFail($id);
    }

    /**
     * Get district count by Il ID.
     */
    public function getIlceCountByIlId(int $ilId): int
    {
        return Ilce::where('il_id', $ilId)->count();
    }

    /**
     * Get an Ilce by ID with eager loaded Il data.
     */
    public function getIlceWithIl(int $id): Ilce
    {
        return Ilce::select(['id', 'il_id', 'ilce_adi'])
            ->with('il:id,il_adi')
            ->findOrFail($id);
    }

    /**
     * Get neighborhood count by Ilce ID.
     */
    public function getMahalleCountByIlceId(int $ilceId): int
    {
        return Mahalle::where('ilce_id', $ilceId)->count();
    }

    /**
     * Get a Mahalle by ID with eager loaded parent data.
     */
    public function getMahalleWithParents(int $id): Mahalle
    {
        return Mahalle::select(['id', 'ilce_id', 'mahalle_adi'])
            ->with(['ilce:id,il_id,ilce_adi', 'ilce.il:id,il_adi'])
            ->findOrFail($id);
    }
    /**
     * Delete an Ulke by ID.
     */
    public function deleteUlke(int $id): bool
    {
        $this->blockAgentWrite('deleteUlke');

        return Ulke::where('id', $id)->delete() > 0;
    }

    /**
     * Delete an Il by ID.
     */
    public function deleteIl(int $id): bool
    {
        $this->blockAgentWrite('deleteIl');

        return Il::where('id', $id)->delete() > 0;
    }

    /**
     * Delete an Ilce by ID.
     */
    public function deleteIlce(int $id): bool
    {
        $this->blockAgentWrite('deleteIlce');

        return Ilce::where('id', $id)->delete() > 0;
    }

    /**
     * Delete a Mahalle by ID.
     */
    public function deleteMahalle(int $id): bool
    {
        $this->blockAgentWrite('deleteMahalle');

        return Mahalle::where('id', $id)->delete() > 0;
    }

    /**
     * Update a Mahalle's coordinates (lat/lng).
     */
    public function updateMahalleCoordinates(int $id, ?float $lat, ?float $lng): Mahalle
    {
        $this->blockAgentWrite('updateMahalleCoordinates');

        $mahalle = $this->getMahalleWithParents($id);
        $mahalle->lat = $lat;
        $mahalle->lng = $lng;
        $mahalle->save();

        return $mahalle;
    }

    /**
     * Update an Ulke's name.
     */
    public function updateUlke(int $id, string $name): Ulke
    {
        $this->blockAgentWrite('updateUlke');

        $ulke = Ulke::findOrFail($id);
        $ulke->ulke_adi = $name;
        $ulke->save();

        return $ulke;
    }

    /**
     * Update an Il's name.
     */
    public function updateIl(int $id, string $name): Il
    {
        $this->blockAgentWrite('updateIl');

        $il = Il::findOrFail($id);
        $il->il_adi = $name;
        $il->save();

        return $il;
    }

    /**
     * Update an Ilce's name.
     *
     * @return array{ilce: Ilce, old_il_id: int}
     */
    public function updateIlce(int $id, string $name): array
    {
        $this->blockAgentWrite('updateIlce');

        $ilce = Ilce::findOrFail($id);
        $oldIlId = $ilce->il_id;
        $ilce->ilce_adi = $name;
        $ilce->save();

        return ['ilce' => $ilce, 'old_il_id' => $oldIlId];
    }

    /**
     * Update a Mahalle's name.
     *
     * @return array{mahalle: Mahalle, old_ilce_id: int}
     */
    public function updateMahalle(int $id, string $name): array
    {
        $this->blockAgentWrite('updateMahalle');

        $mahalle = Mahalle::findOrFail($id);
        $oldIlceId = $mahalle->ilce_id;
        $mahalle->mahalle_adi = $name;
        $mahalle->save();

        return ['mahalle' => $mahalle, 'old_ilce_id' => $oldIlceId];
    }

    /**
     * Get all Iller with API IDs.
     */
    public function getIllerWithApiIds(): Collection
    {
        return Il::whereNotNull('api_id')->get();
    }

    /**
     * Get all Ilceler with API IDs by Il ID.
     */
    public function getIlcelerWithApiIdsByIlId(int $ilId): Collection
    {
        return Ilce::where('il_id', $ilId)->whereNotNull('api_id')->get();
    }

    /**
     * Get all Ilceler by Il ID.
     */
    public function getIlcelerByIlId(int $ilId): Collection
    {
        return Ilce::where('il_id', $ilId)->get();
    }

    /**
     * Get all Mahalleler by Ilce ID.
     */
    public function getMahallelerByIlceId(int $ilceId): Collection
    {
        return Mahalle::where('ilce_id', $ilceId)->get();
    }

    /**
     * Create a new Ulke.
     */
    public function createUlke(string $name): Ulke
    {
        $this->blockAgentWrite('createUlke');

        return Ulke::create(['ulke_adi' => $name]);
    }

    /**
     * Create a new Il.
     */
    public function createIl(string $name): Il
    {
        $this->blockAgentWrite('createIl');

        return Il::create(['il_adi' => $name]);
    }

    /**
     * Create a new Ilce.
     */
    public function createIlce(int $ilId, string $name): Ilce
    {
        $this->blockAgentWrite('createIlce');

        return Ilce::create([
            'il_id' => $ilId,
            'ilce_adi' => $name
        ]);
    }

    /**
     * Create a new Mahalle.
     */
    public function createMahalle(int $ilceId, string $name): Mahalle
    {
        $this->blockAgentWrite('createMahalle');

        return Mahalle::create([
            'ilce_id' => $ilceId,
            'mahalle_adi' => $name
        ]);
    }
}
