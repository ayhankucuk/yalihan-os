<?php

namespace App\Http\Controllers;

/**
 * @sab-ignore-thin
 */

use App\Services\VillaService;
use Illuminate\Http\Request;

/**
 * Public Villa Listing Controller
 * TatildeKirala/Airbnb tarzı villa kiralama
 */
class VillaController extends Controller
{
    public function __construct(protected VillaService $villaService)
    {
    }

    /**
     * Villa listing page (TatildeKirala tarzı)
     * Route: /yazliklar
     */
    public function index(Request $request)
    {
        $yazlikKategori = $this->villaService->getYazlikKategori();

        if (! $yazlikKategori) {
            abort(404, 'Yazlık kiralama kategorisi bulunamadı');
        }

        $villas = $this->villaService->searchVillas(
            $yazlikKategori->id,
            $request->only(['location', 'guests', 'min_price', 'max_price', 'check_in', 'check_out', 'amenities', 'rental_type']),
            $request->get('sort', 'popular'),
            $request->sort_order ?? 'desc'
        );

        $locations = $this->villaService->getFilterLocations($yazlikKategori->id);
        $popularAmenities = $this->getPopularAmenities();

        $stats = [
            'total' => $villas->total(),
            'available_today' => $this->villaService->getAvailableToday($yazlikKategori->id),
        ];

        return view('villas.index', compact('villas', 'locations', 'popularAmenities', 'stats'));
    }

    /**
     * Villa detail page (Airbnb tarzı)
     * Route: /yazliklar/{id}
     */
    public function show($id)
    {
        $villa = $this->villaService->getVillaDetail((int) $id);

        $availabilityCalendar = $this->villaService->getAvailabilityCalendar($villa->id, 3);
        $pricing = $this->villaService->getPricingInfo($villa);
        $similarVillas = $this->villaService->getSimilarVillas($villa, 4);

        return view('villas.show', compact(
            'villa',
            'availabilityCalendar',
            'pricing',
            'similarVillas'
        ));
    }

    /**
     * Rezervasyon formu (AJAX)
     */
    public function checkAvailability(Request $request)
    {
        $request->validate([
            'ilan_id' => 'required|exists:ilanlar,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1',
        ]);

        $result = $this->villaService->checkAvailabilityAndPrice(
            $request->ilan_id,
            $request->check_in,
            $request->check_out
        );

        return response()->json($result);
    }

    /**
     * Helper: Popüler amenities
     */
    private function getPopularAmenities()
    {
        return [
            ['slug' => 'havuz', 'name' => 'Özel Havuz', 'icon' => '🏊'],
            ['slug' => 'deniz-manzarasi', 'name' => 'Deniz Manzarası', 'icon' => '🌅'],
            ['slug' => 'jakuzi', 'name' => 'Jakuzi', 'icon' => '🛁'],
            ['slug' => 'wifi', 'name' => 'WiFi', 'icon' => '📶'],
            ['slug' => 'klima', 'name' => 'Klima', 'icon' => '❄️'],
            ['slug' => 'otopark', 'name' => 'Otopark', 'icon' => '🚗'],
            ['slug' => 'sauna', 'name' => 'Sauna', 'icon' => '🧖'],
            ['slug' => 'cocuk-oyun-alani', 'name' => 'Çocuk Dostu', 'icon' => '👶'],
        ];
    }
}
