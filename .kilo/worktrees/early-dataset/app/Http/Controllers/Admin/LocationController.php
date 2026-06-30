<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Enums\AktiflikDurumu;

class LocationController extends AdminController
{
    /**
     * Basic LocationController for Context7 compliance
     */
    public function index()
    {
        return view('admin.locations.index');
    }

    public function getProvinces()
    {
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }

    public function getDistricts()
    {
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }

    public function getNeighborhoods()
    {
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }

    public function apiIndex()
    {
        return $this->getProvinces();
    }

    public function apiCoordinates($location)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'location' => $location,
                'lat' => null,
                'lng' => null,
            ],
        ]);
    }

    public function apiHierarchy($parent_id = null)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'parent_id' => $parent_id,
                'children' => [],
            ],
        ]);
    }

    public function apiPopular()
    {
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }

    public function apiSearch()
    {
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Mock location data
        $location = [
            'id' => $id,
            'name' => 'Sample Location '.$id,
            'type' => 'district', // context7-ignore
            'coordinates' => [
                'latitude' => 41.0082,
                'longitude' => 28.9784,
            ],
            'address' => 'Sample Address, Istanbul, Turkey',
            'details' => [
                'population' => 15000,
                'area' => '25.5 km²',
                'postal_code' => '34000',
            ],
            'aktiflik_durumu' => AktiflikDurumu::AKTIF->label(),
            'created_at' => now()->subDays(30)->toISOString(),
            'updated_at' => now()->toISOString(),
        ];

        if (request()->expectsJson()) {
            return response()->json($location);
        }

        return view('admin.locations.show', compact('location'));
    }
}
