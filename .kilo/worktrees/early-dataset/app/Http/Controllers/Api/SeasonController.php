<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Models\Season;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Season Pricing Management API Controller
 * Pure API endpoints for seasonal pricing
 * Context7 compliant (using DB schema as-is)
 */
class SeasonController extends Controller
{
    use ValidatesApiRequests;

    /**
     * Get seasons for ilan
     * GET /api/admin/ilanlar/{id}/seasons
     */
    public function index($ilanId)
    {
        $seasons = Season::where('ilan_id', $ilanId)
            ->orderBy('baslangic_tarihi') // ✅ SAB: Tablodaki gerçek kolon adı // context7-ignore
            ->get()
            ->map(fn ($season) => [
                'id' => $season->id,
                'season_type' => $season->sezon_tipi, // ✅ SAB: Tablodaki gerçek kolon adı
                'start_date' => $season->baslangic_tarihi, // Backward compatibility için accessor kullanılıyor
                'end_date' => $season->bitis_tarihi, // Backward compatibility için accessor kullanılıyor
                'daily_price' => $season->gunluk_fiyat, // Backward compatibility için accessor kullanılıyor
                'weekly_price' => $season->haftalik_fiyat, // Backward compatibility için accessor kullanılıyor
                'monthly_price' => $season->aylik_fiyat, // Backward compatibility için accessor kullanılıyor
                'minimum_stay' => $season->minimum_konaklama, // Backward compatibility için accessor kullanılıyor
                'maximum_stay' => $season->maksimum_konaklama, // Backward compatibility için accessor kullanılıyor
                'yayin_durumu' => $season->yayin_durumu,
            ]);

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success(['seasons' => $seasons], 'Sezonlar başarıyla getirildi');
    }

    /**
     * Create new season
     * POST /api/admin/seasons
     */
    public function store(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'ilan_id' => 'required|exists:ilanlar,id',
            'season_type' => 'required|in:yaz,kis,ara_sezon',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'daily_price' => 'required|numeric|min:0',
            'weekly_price' => 'nullable|numeric|min:0',
            'monthly_price' => 'nullable|numeric|min:0',
            'minimum_stay' => 'nullable|integer|min:1',
            'maximum_stay' => 'nullable|integer|min:1',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        $season = Season::create([
            'ilan_id' => $request->ilan_id,
            'sezon_tipi' => $request->season_type, // ✅ SAB: Tablodaki gerçek kolon adı
            'baslangic_tarihi' => $request->start_date, // ✅ SAB: Tablodaki gerçek kolon adı
            'bitis_tarihi' => $request->end_date, // ✅ SAB: Tablodaki gerçek kolon adı
            'gunluk_fiyat' => $request->daily_price, // ✅ SAB: Tablodaki gerçek kolon adı
            'haftalik_fiyat' => $request->weekly_price, // ✅ SAB: Tablodaki gerçek kolon adı
            'aylik_fiyat' => $request->monthly_price, // ✅ SAB: Tablodaki gerçek kolon adı
            'minimum_konaklama' => $request->minimum_stay ?? 1, // ✅ SAB: Tablodaki gerçek kolon adı
            'maksimum_konaklama' => $request->maximum_stay, // ✅ SAB: Tablodaki gerçek kolon adı
        ]);

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success(['season' => $season], 'Sezon oluşturuldu', 201);
    }

    /**
     * Update season
     * PATCH /api/admin/seasons/{id}
     */
    public function update(Request $request, $id)
    {
        $season = Season::findOrFail($id);

        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'season_type' => 'nullable|in:yaz,kis,ara_sezon',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'daily_price' => 'nullable|numeric|min:0',
            'weekly_price' => 'nullable|numeric|min:0',
            'monthly_price' => 'nullable|numeric|min:0',
            'minimum_stay' => 'nullable|integer|min:1',
            'maximum_stay' => 'nullable|integer|min:1',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        $updateData = [];
        if ($request->has('season_type')) {
            $updateData['sezon_tipi'] = $request->season_type;
        }
        if ($request->has('start_date')) {
            $updateData['baslangic_tarihi'] = $request->start_date;
        }
        if ($request->has('end_date')) {
            $updateData['bitis_tarihi'] = $request->end_date;
        }
        if ($request->has('daily_price')) {
            $updateData['gunluk_fiyat'] = $request->daily_price;
        }
        if ($request->has('weekly_price')) {
            $updateData['haftalik_fiyat'] = $request->weekly_price;
        }
        if ($request->has('monthly_price')) {
            $updateData['aylik_fiyat'] = $request->monthly_price;
        }
        if ($request->has('minimum_stay')) {
            $updateData['minimum_konaklama'] = $request->minimum_stay;
        }
        if ($request->has('maximum_stay')) {
            $updateData['maksimum_konaklama'] = $request->maximum_stay;
        }

        $season->update($updateData);

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success(['season' => $season], 'Sezon güncellendi');
    }

    /**
     * Delete season
     * DELETE /api/admin/seasons/{id}
     */
    public function destroy($id)
    {
        $season = Season::findOrFail($id);
        $season->delete();

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success(null, 'Sezon silindi');
    }

    /**
     * Get price for date range
     * POST /api/admin/seasons/calculate-price
     */
    public function calculatePrice(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'ilan_id' => 'required|exists:ilanlar,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $nights = $checkIn->diffInDays($checkOut);

        // Find applicable season
        $season = Season::where('ilan_id', $request->ilan_id)
            ->where('baslangic_tarihi', '<=', $request->check_in) // ✅ SAB: Tablodaki gerçek kolon adı
            ->where('bitis_tarihi', '>=', $request->check_out) // ✅ SAB: Tablodaki gerçek kolon adı
            ->first();

        if (! $season) {
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::notFound('Bu tarihler için sezon fiyatı bulunamadı');
        }

        $totalPrice = $season->gunluk_fiyat * $nights; // ✅ SAB: Tablodaki gerçek kolon adı

        // Weekly discount
        if ($nights >= 7 && $season->haftalik_fiyat) { // ✅ SAB: Tablodaki gerçek kolon adı
            $weeks = floor($nights / 7);
            $remainingNights = $nights % 7;
            $totalPrice = ($weeks * $season->haftalik_fiyat) + ($remainingNights * $season->gunluk_fiyat);
        }

        // Monthly discount
        if ($nights >= 30 && $season->aylik_fiyat) { // ✅ SAB: Tablodaki gerçek kolon adı
            $months = floor($nights / 30);
            $remainingNights = $nights % 30;
            $totalPrice = ($months * $season->aylik_fiyat) + ($remainingNights * $season->gunluk_fiyat);
        }

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success([
            'season' => $season->sezon_tipi, // ✅ SAB: Tablodaki gerçek kolon adı
            'nights' => $nights,
            'daily_price' => $season->gunluk_fiyat, // Backward compatibility için accessor kullanılıyor
            'total_price' => $totalPrice,
            'currency' => 'TRY',
        ], 'Fiyat hesaplandı');
    }
}
