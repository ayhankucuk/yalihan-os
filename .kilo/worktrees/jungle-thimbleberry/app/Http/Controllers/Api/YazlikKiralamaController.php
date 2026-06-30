<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Models\YazlikFiyatlandirma;
use App\Models\YazlikRezervasyon;
use App\Services\Response\ResponseService;
use App\Services\YazlikKiralamaService;
use App\Traits\ValidatesApiRequests;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Yazlık Kiralama API Controller
 *
 * Context7 Standardı: C7-YAZLIK-KIRALAMA-API-2025-12-06
 *
 * Endpoint'ler:
 * - GET /api/v1/yazlik-kiralama/takvim/{ilan} - Takvim görünümü
 * - POST /api/v1/yazlik-kiralama/fiyat-hesapla - Fiyat hesaplama
 * - POST /api/v1/yazlik-kiralama/musaitlik-kontrol - Müsaitlik kontrolü
 * - POST /api/v1/yazlik-kiralama/rezervasyon - Rezervasyon oluştur
 * - GET /api/v1/yazlik-kiralama/fiyatlandirma/{ilan} - Fiyatlandırma listesi
 * - POST /api/v1/yazlik-kiralama/fiyatlandirma - Fiyatlandırma oluştur
 * - PUT /api/v1/yazlik-kiralama/fiyatlandirma/{id} - Fiyatlandırma güncelle
 * - DELETE /api/v1/yazlik-kiralama/fiyatlandirma/{id} - Fiyatlandırma sil
 */
class YazlikKiralamaController extends Controller
{
    use ValidatesApiRequests;

    protected YazlikKiralamaService $yazlikKiralamaService;

    public function __construct(YazlikKiralamaService $yazlikKiralamaService)
    {
        $this->yazlikKiralamaService = $yazlikKiralamaService;
    }

    /**
     * Takvim görünümü
     *
     * GET /api/v1/yazlik-kiralama/takvim/{ilan}
     */
    public function getCalendar(Request $request, $ilanId): JsonResponse
    {
        $ilan = Ilan::findOrFail($ilanId);

        $startDate = Carbon::parse($request->get('start_date', now()->startOfMonth()));
        $endDate = Carbon::parse($request->get('end_date', now()->copy()->addMonths(2)->endOfMonth()));

        $calendar = $this->yazlikKiralamaService->generateCalendar($ilan, $startDate, $endDate);

        return ResponseService::success([
            'ilan_id' => $ilan->id,
            'ilan_baslik' => $ilan->baslik,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'calendar' => $calendar,
        ], 'Takvim verileri başarıyla getirildi');
    }

    /**
     * Fiyat hesaplama
     *
     * POST /api/v1/yazlik-kiralama/fiyat-hesapla
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $validated = $this->validateRequestWithResponse($request, [
            'ilan_id' => 'required|exists:ilanlar,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'misafir_sayisi' => 'nullable|integer|min:1',
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $ilan = Ilan::findOrFail($validated['ilan_id']);

        $checkIn = Carbon::parse($validated['check_in']);
        $checkOut = Carbon::parse($validated['check_out']);

        $validation = $this->yazlikKiralamaService->validateMinimumStay($ilan, $checkIn, $checkOut);
        if (! $validation['valid']) {
            return ResponseService::error($validation['message'], 400);
        }

        $priceData = $this->yazlikKiralamaService->calculatePrice($ilan, $checkIn, $checkOut);

        return ResponseService::success(array_merge([
            'ilan_id' => $ilan->id,
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
            'currency' => $ilan->para_birimi ?? 'TRY',
        ], $priceData), 'Fiyat hesaplaması başarıyla tamamlandı');
    }

    /**
     * Müsaitlik kontrolü
     *
     * POST /api/v1/yazlik-kiralama/musaitlik-kontrol
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validated = $this->validateRequestWithResponse($request, [
            'ilan_id' => 'required|exists:ilanlar,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'exclude_reservation_id' => 'nullable|exists:yazlik_rezervasyonlar,id',
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $ilan = Ilan::findOrFail($validated['ilan_id']);

        $checkIn = Carbon::parse($validated['check_in']);
        $checkOut = Carbon::parse($validated['check_out']);

        $isAvailable = $this->yazlikKiralamaService->isAvailable(
            $ilan->id,
            $checkIn->format('Y-m-d'),
            $checkOut->format('Y-m-d'),
            $validated['exclude_reservation_id'] ?? null
        );

        $conflictingReservations = $this->yazlikKiralamaService->checkReservationConflict(
            $ilan->id,
            $checkIn->format('Y-m-d'),
            $checkOut->format('Y-m-d'),
            $validated['exclude_reservation_id'] ?? null
        );

        return ResponseService::success([
            'ilan_id' => $ilan->id,
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
            'is_available' => $isAvailable,
            'conflicting_reservations' => $conflictingReservations->map(fn($rez) => [
                'id' => $rez->id,
                'check_in' => $rez->check_in->format('Y-m-d'),
                'check_out' => $rez->check_out->format('Y-m-d'),
                'rezervasyon_durumu' => $rez->rezervasyon_durumu,
            ]),
        ], $isAvailable ? 'Tarih aralığı müsait' : 'Tarih aralığında çakışan rezervasyonlar var');
    }

    /**
     * Rezervasyon oluştur
     *
     * POST /api/v1/yazlik-kiralama/rezervasyon
     */
    public function createReservation(Request $request): JsonResponse
    {
        $validated = $this->validateRequestWithResponse($request, [
            'ilan_id' => 'required|exists:ilanlar,id',
            'kisi_adi' => 'required|string|max:255',
            'kisi_telefon' => 'required|string|max:50',
            'kisi_email' => 'nullable|email|max:255',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'misafir_sayisi' => 'required|integer|min:1',
            'cocuk_sayisi' => 'nullable|integer|min:0|default:0',
            'pet_sayisi' => 'nullable|integer|min:0|default:0',
            'ozel_istekler' => 'nullable|string|max:1000',
            'kapora_tutari' => 'nullable|numeric|min:0',
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        try {
            $ilan = Ilan::findOrFail($validated['ilan_id']);

            $checkIn = Carbon::parse($validated['check_in']);
            $checkOut = Carbon::parse($validated['check_out']);

            $reservation = $this->yazlikKiralamaService->createReservation($ilan, array_merge($validated, [
                'check_in' => $checkIn->format('Y-m-d'),
                'check_out' => $checkOut->format('Y-m-d'),
            ]));

            Log::info('Yazlık rezervasyon oluşturuldu', [
                'reservation_id' => $reservation->id,
                'ilan_id' => $ilan->id,
            ]);

            return ResponseService::success([
                'reservation' => $reservation->load('ilan:id,baslik'),
            ], 'Rezervasyon başarıyla oluşturuldu');
        } catch (\Exception $e) {
            Log::error('Yazlık rezervasyon oluşturma hatası', [
                'error' => $e->getMessage(),
                'ilan_id' => $validated['ilan_id'] ?? null,
            ]);

            return ResponseService::error('Rezervasyon oluşturulurken bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Fiyatlandırma listesi
     *
     * GET /api/v1/yazlik-kiralama/fiyatlandirma/{ilan}
     */
    public function getPricing(Request $request, $ilanId): JsonResponse
    {
        $ilan = Ilan::findOrFail($ilanId);

        $pricings = YazlikFiyatlandirma::where('ilan_id', $ilan->id)
            ->orderBy('baslangic_tarihi') // context7-ignore
            ->get();

        return ResponseService::success([
            'ilan_id' => $ilan->id,
            'pricings' => $pricings,
        ], 'Fiyatlandırma listesi başarıyla getirildi');
    }

    /**
     * Fiyatlandırma oluştur
     *
     * POST /api/v1/yazlik-kiralama/fiyatlandirma
     */
    public function createPricing(Request $request): JsonResponse
    {
        $validated = $this->validateRequestWithResponse($request, [
            'ilan_id' => 'required|exists:ilanlar,id',
            'sezon_tipi' => 'required|in:yaz,ara_sezon,kis',
            'baslangic_tarihi' => 'required|date',
            'bitis_tarihi' => 'required|date|after:baslangic_tarihi',
            'gunluk_fiyat' => 'nullable|numeric|min:0',
            'haftalik_fiyat' => 'nullable|numeric|min:0',
            'aylik_fiyat' => 'nullable|numeric|min:0',
            'minimum_konaklama' => 'nullable|integer|min:1',
            'maksimum_konaklama' => 'nullable|integer|min:1',
            'ozel_gunler' => 'nullable|array',
            'aktiflik_durumu' => 'nullable|boolean',
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        try {
            $pricing = YazlikFiyatlandirma::create([
                'ilan_id' => $validated['ilan_id'],
                'sezon_tipi' => $validated['sezon_tipi'],
                'baslangic_tarihi' => $validated['baslangic_tarihi'],
                'bitis_tarihi' => $validated['bitis_tarihi'],
                'gunluk_fiyat' => $validated['gunluk_fiyat'] ?? null,
                'haftalik_fiyat' => $validated['haftalik_fiyat'] ?? null,
                'aylik_fiyat' => $validated['aylik_fiyat'] ?? null,
                'minimum_konaklama' => $validated['minimum_konaklama'] ?? 1,
                'maksimum_konaklama' => $validated['maksimum_konaklama'] ?? null,
                'ozel_gunler' => $validated['ozel_gunler'] ?? null,
                'aktiflik_durumu' => $validated['aktiflik_durumu'] ?? true,
            ]);

            return ResponseService::success([
                'pricing' => $pricing,
            ], 'Fiyatlandırma başarıyla oluşturuldu');
        } catch (\Exception $e) {
            Log::error('Fiyatlandırma oluşturma hatası', [
                'error' => $e->getMessage(),
                'ilan_id' => $validated['ilan_id'] ?? null,
            ]);

            return ResponseService::error('Fiyatlandırma oluşturulurken bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Fiyatlandırma güncelle
     *
     * PUT /api/v1/yazlik-kiralama/fiyatlandirma/{id}
     */
    public function updatePricing(Request $request, $id): JsonResponse
    {
        $pricing = YazlikFiyatlandirma::findOrFail($id);

        $validated = $this->validateRequestWithResponse($request, [
            'sezon_tipi' => 'sometimes|in:yaz,ara_sezon,kis',
            'baslangic_tarihi' => 'sometimes|date',
            'bitis_tarihi' => 'sometimes|date|after:baslangic_tarihi',
            'gunluk_fiyat' => 'nullable|numeric|min:0',
            'haftalik_fiyat' => 'nullable|numeric|min:0',
            'aylik_fiyat' => 'nullable|numeric|min:0',
            'minimum_konaklama' => 'nullable|integer|min:1',
            'maksimum_konaklama' => 'nullable|integer|min:1',
            'ozel_gunler' => 'nullable|array',
            'aktiflik_durumu' => 'nullable|boolean',
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        try {
            $pricing->update($validated);

            return ResponseService::success([
                'pricing' => $pricing->fresh(),
            ], 'Fiyatlandırma başarıyla güncellendi');
        } catch (\Exception $e) {
            Log::error('Fiyatlandırma güncelleme hatası', [
                'error' => $e->getMessage(),
                'pricing_id' => $id,
            ]);

            return ResponseService::error('Fiyatlandırma güncellenirken bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Fiyatlandırma sil
     *
     * DELETE /api/v1/yazlik-kiralama/fiyatlandirma/{id}
     */
    public function deletePricing($id): JsonResponse
    {
        try {
            $pricing = YazlikFiyatlandirma::findOrFail($id);
            $pricing->delete();

            return ResponseService::success(null, 'Fiyatlandırma başarıyla silindi');
        } catch (\Exception $e) {
            Log::error('Fiyatlandırma silme hatası', [
                'error' => $e->getMessage(),
                'pricing_id' => $id,
            ]);

            return ResponseService::error('Fiyatlandırma silinirken bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }
}
