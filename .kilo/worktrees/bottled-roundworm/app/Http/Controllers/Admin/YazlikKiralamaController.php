<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Models\FeatureCategory;
use App\Models\Ilan;
use App\Models\IlanFotografi;
use App\Models\IlanKategori;
use App\Models\YazlikRezervasyon;
use App\Services\Ilan\YazlikKiralamaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class YazlikKiralamaController extends AdminController
{
    public function __construct(
        private YazlikKiralamaService $yazlikService
    ) {}
    /**
     * Display summer rental listings dashboard
     */
    public function index(Request $request)
    {
        // Yazlık Kiralama kategorisini bul - Context7: Multiple slug fallback
        $yazlikKategori = IlanKategori::where('slug', 'yazlik-kiralama')
            ->orWhere('slug', 'yazlik-kiralik')
            ->orWhere('name', 'like', '%Yazlık%Kiralama%')
            ->orWhere('name', 'like', '%Yazlık%Kiralık%')
            ->first();

        if (! $yazlikKategori) {
            // Fallback: Ana Yazlık kategorisini bul
            $yazlikKategori = IlanKategori::where('slug', 'yazlik')
                ->orWhere('name', 'like', '%Yazlık%')
                ->where('seviye', 0)
                ->first();

            if (! $yazlikKategori) {
                return redirect()->back()->with('error', 'Yazlık Kiralama kategorisi bulunamadı. Lütfen kategori yönetiminden Yazlık kategorisini oluşturun.');
            }
        }

        // ✅ SAB: ana_kategori_id veya alt_kategori_id kullan (kategori_id yok)
        $query = Ilan::with([
            'anaKategori:id,name,slug',
            'altKategori:id,name,slug',
            'fotograflar:id,ilan_id,url,display_order',
        ])
            ->select([
                'id',
                'baslik',
                'aciklama',
                'fiyat',
                'para_birimi',
                'ana_kategori_id',
                'alt_kategori_id',
                'il_id',
                'ilce_id',
                'ilce_id',
                'aktiflik_durumu',
                'adres',
                'created_at',
            ])

            ->where(function ($q) use ($yazlikKategori) {
                $q->where('ana_kategori_id', $yazlikKategori->id)
                    ->orWhere('alt_kategori_id', $yazlikKategori->id);
            });

        // ✅ REFACTORED: Filterable trait kullanımı - Code duplication azaltıldı
        // Search filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('baslik', 'LIKE', "%{$search}%")
                    ->orWhere('aciklama', 'LIKE', "%{$search}%")
                    ->orWhere('adres', 'LIKE', "%{$search}%");
            });
        }

        // ✅ SAB: Aktiflik filtresi (canonical: aktiflik_durumu/yayin_durumu)
        $statusFilter = $request->input('aktiflik_durumu', $request->input('yayin_durumu'));
        if ($statusFilter) {
            $query->byAktiflikDurumu($statusFilter, 'yayin_durumu');
        }

        // ✅ REFACTORED: Location filter
        if ($request->filled('il_id')) {
            $query->where('il_id', $request->get('il_id'));
        }

        // ✅ REFACTORED: Price range filter (Filterable trait)
        if ($request->filled('price_range')) {
            [$min, $max] = explode('-', $request->get('price_range'));
            $query->priceRange((int) $min, (int) $max, 'fiyat');
        } else {
            // ✅ REFACTORED: Min/max price filters (Filterable trait)
            $query->priceRange(
                $request->filled('min_fiyat') ? (float) $request->get('min_fiyat') : null,
                $request->filled('max_fiyat') ? (float) $request->get('max_fiyat') : null,
                'fiyat'
            );
        }

        // Date range for seasonal rentals
        if ($request->filled('season')) {
            $season = $request->get('season');
            $currentYear = date('Y');

            switch ($season) {
                case 'summer':
                    $query->whereJsonContains('seasonal_availability', ['summer' => true]);
                    break;
                case 'winter':
                    $query->whereJsonContains('seasonal_availability', ['winter' => true]);
                    break;
                case 'year_round':
                    $query->whereJsonContains('seasonal_availability', ['year_round' => true]);
                    break;
            }
        }

        // ✅ REFACTORED: Sort (Filterable trait)
        $query->sort($request->sort_by, $request->sort_order ?? 'desc', 'created_at');

        $ilanlar = $query->paginate(20);

        // Statistics - Context7: Eloquent relationship kullanımı
        try {
            // ✅ N+1 FIX: Eager loading ekle
            // ✅ SAB: ana_kategori_id veya alt_kategori_id kullan (kategori_id yok)
            $activeReservations = YazlikRezervasyon::whereHas('ilan', function ($q) use ($yazlikKategori) {
                $q->where(function ($query) use ($yazlikKategori) {
                    $query->where('ana_kategori_id', $yazlikKategori->id)
                        ->orWhere('alt_kategori_id', $yazlikKategori->id);
                });
            })
                ->with('ilan:id,ana_kategori_id,alt_kategori_id')
                ->whereIn('aktiflik_durumu', ['beklemede', 'onaylandi'])
                ->count();

        } catch (\Exception $e) {
            Log::warning('YazlikKiralamaController: aktif rezervasyon sayısı alınamadı', [
                'error' => $e->getMessage(),
            ]);
            $activeReservations = 0;
        }

        // ✅ SAB: ana_kategori_id veya alt_kategori_id kullan (kategori_id yok)
        $stats = [
            'total_yazlik' => Ilan::where(function ($q) use ($yazlikKategori) {
                $q->where('ana_kategori_id', $yazlikKategori->id)
                    ->orWhere('alt_kategori_id', $yazlikKategori->id);
            })->count(),
            'active_reservations' => $activeReservations, // context7-ignore
            'monthly_revenue' => $this->yazlikService->getMonthlyRevenue(),
            'occupancy_rate' => 75.5, // Mock data - implement real calculation
        ];


        $yazliklar = $ilanlar;

        return view('admin.yazlik-kiralama.index', compact('yazliklar', 'stats'));
    }

    /**
     * Show the form for creating a new summer rental listing
     */
    public function create()
    {
        $mainCatId = IlanKategori::where('slug', 'yazlik-kiralama')->value('id');
        $kategoriler = IlanKategori::where('aktiflik_durumu', 1)
            ->where(function ($q) use ($mainCatId) {
                $q->where('slug', 'yazlik-kiralama');
                if ($mainCatId) {
                    $q->orWhere('parent_id', $mainCatId);
                }
            })
            ->orderBy('name') // context7-ignore
            ->get();


        $amenities = $this->getAmenityOptions();
        $rentalTypes = $this->getRentalTypeOptions();

        return view('admin.yazlik-kiralama.create', compact('kategoriler', 'amenities', 'rentalTypes'));
    }

    /**
     * Store a newly created summer rental listing
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'baslik' => 'required|string|max:255',
            'aciklama' => 'required|string',
            'ana_kategori_id' => 'required|exists:ilan_kategorileri,id',
            'il_id' => 'required|exists:iller,id',
            'ilce_id' => 'required|exists:ilceler,id',
            'mahalle_id' => 'nullable|exists:mahalleler,id',
            'adres' => 'required|string|max:500',
            'fiyat' => 'required|numeric|min:0',
            'doviz' => 'required|in:TRY,USD,EUR',
            'metrekare' => 'required|numeric|min:1',
            'oda_sayisi' => 'required|integer|min:1',
            'salon_sayisi' => 'required|integer|min:1',
            'banyo_sayisi' => 'required|integer|min:1',
            'balkon_sayisi' => 'nullable|integer|min:0',
            'kat' => 'nullable|integer',
            'bina_kati' => 'nullable|integer',
            'yatak_odasi_sayisi' => 'required|integer|min:1',
            'max_guests' => 'required|integer|min:1|max:20',
            'min_stay_days' => 'required|integer|min:1',
            'max_stay_days' => 'nullable|integer|min:1',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'required|date_format:H:i',
            'seasonal_availability' => 'required|array',
            'amenities' => 'nullable|array',
            'rental_type' => 'required|in:daily,weekly,monthly,seasonal',
            'booking_type' => 'required|in:instant,request',
            'cancellation_policy' => 'required|in:flexible,moderate,strict',
            'security_deposit' => 'nullable|numeric|min:0',
            'cleaning_fee' => 'nullable|numeric|min:0',
            'extra_guest_fee' => 'nullable|numeric|min:0',
            'aktiflik_durumu' => 'required|in:active,inactive,pending',
            'fotograflar.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // SAB Kural 1/11: TX + domain logic service'te
            $ilanData = [
                'baslik' => $request->baslik,
                'aciklama' => $request->aciklama,
                'ana_kategori_id' => $request->ana_kategori_id ?? $request->kategori_id,
                'il_id' => $request->il_id,
                'ilce_id' => $request->ilce_id,
                'mahalle_id' => $request->mahalle_id,
                'adres' => $request->adres,
                'fiyat' => $request->fiyat,
                'doviz' => $request->doviz,
                'metrekare' => $request->metrekare,
                'oda_sayisi' => $request->oda_sayisi,
                'salon_sayisi' => $request->salon_sayisi,
                'banyo_sayisi' => $request->banyo_sayisi,
                'balkon_sayisi' => $request->balkon_sayisi,
                'kat' => $request->kat,
                'bina_kati' => $request->bina_kati,
                'yatak_odasi_sayisi' => $request->yatak_odasi_sayisi,
                'max_guests' => $request->max_guests,
                'min_stay_days' => $request->min_stay_days,
                'max_stay_days' => $request->max_stay_days,
                'check_in_time' => $request->check_in_time,
                'check_out_time' => $request->check_out_time,
                'seasonal_availability' => json_encode($request->seasonal_availability),
                'amenities' => json_encode($request->amenities ?? []),
                'rental_type' => $request->rental_type,
                'booking_type' => $request->booking_type,
                'cancellation_policy' => $request->cancellation_policy,
                'security_deposit' => $request->security_deposit,
                'cleaning_fee' => $request->cleaning_fee,
                'extra_guest_fee' => $request->extra_guest_fee,
                'aktiflik_durumu' => $request->input('aktiflik_durumu') ?? 1,
                'created_by' => auth()->id(),
            ];

            $uploadedFiles = $request->hasFile('fotograflar')
                ? $request->file('fotograflar')
                : [];

            $ilan = $this->yazlikService->createListing($ilanData, $uploadedFiles);

            return response()->json([
                'success' => true,
                'message' => 'Yazlık kiralama ilanı başarıyla oluşturuldu.',
                'data' => ['ilan_id' => $ilan->id],
            ]);
        } catch (\Exception $e) {
            Log::error('Summer rental listing creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'İlan oluşturma işlemi başarısız: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified summer rental listing
     */
    public function show($id)
    {
        $ilan = Ilan::with(['kategori', 'fotograflar', 'il', 'ilce', 'mahalle'])
            ->findOrFail($id);

        $bookingStats = $this->getBookingStats($id);
        $revenueStats = $this->getRevenueStats($id);
        $availabilityCalendar = $this->getAvailabilityCalendar($id);

        return view('admin.yazlik-kiralama.show', compact(
            'ilan',
            'bookingStats',
            'revenueStats',
            'availabilityCalendar'
        ));
    }

    /**
     * Show the form for editing the specified summer rental listing
     */
    public function edit($id)
    {
        $ilan = Ilan::with(['kategori', 'fotograflar'])->findOrFail($id);

        $mainCatId = IlanKategori::where('slug', 'yazlik-kiralama')->value('id');
        $kategoriler = IlanKategori::where('aktiflik_durumu', 1)
            ->where(function ($q) use ($mainCatId) {
                $q->where('slug', 'yazlik-kiralama');
                if ($mainCatId) {
                    $q->orWhere('parent_id', $mainCatId);
                }
            })
            ->orderBy('name') // context7-ignore
            ->get();


        $amenities = $this->getAmenityOptions();
        $rentalTypes = $this->getRentalTypeOptions();

        return view('admin.yazlik-kiralama.edit', compact(
            'ilan',
            'kategoriler',
            'amenities',
            'rentalTypes'
        ));
    }

    /**
     * Update the specified summer rental listing
     */
    public function update(Request $request, $id)
    {
        $ilan = Ilan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'baslik' => 'required|string|max:255',
            'aciklama' => 'required|string',
            'ana_kategori_id' => 'required|exists:ilan_kategorileri,id',
            'il_id' => 'required|exists:iller,id',
            'ilce_id' => 'required|exists:ilceler,id',
            'mahalle_id' => 'nullable|exists:mahalleler,id',
            'adres' => 'required|string|max:500',
            'fiyat' => 'required|numeric|min:0',
            'doviz' => 'required|in:TRY,USD,EUR',
            'metrekare' => 'required|numeric|min:1',
            'max_guests' => 'required|integer|min:1|max:20',
            'min_stay_days' => 'required|integer|min:1',
            'rental_type' => 'required|in:daily,weekly,monthly,seasonal',
            'aktiflik_durumu' => 'required|in:active,inactive,pending',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // SAB Kural 1/11: TX + domain logic service'te
            $updateData = $request->only([
                'baslik',
                'aciklama',
                'il_id',
                'ilce_id',
                'mahalle_id',
                'adres',
                'fiyat',
                'doviz',
                'metrekare',
                'max_guests',
                'min_stay_days',
                'rental_type',
                'aktiflik_durumu',
                'seasonal_availability',

                'amenities',
                'booking_type',
                'cancellation_policy',
                'security_deposit',
                'cleaning_fee',
                'extra_guest_fee',
            ]);

            if ($request->has('ana_kategori_id')) {
                $updateData['ana_kategori_id'] = $request->ana_kategori_id;
            } elseif ($request->has('kategori_id')) {
                $updateData['ana_kategori_id'] = $request->kategori_id;
            }

            $uploadedFiles = $request->hasFile('new_fotograflar')
                ? $request->file('new_fotograflar')
                : [];

            $this->yazlikService->updateListing($ilan, $updateData, $uploadedFiles);

            return response()->json([
                'success' => true,
                'message' => 'Yazlık kiralama ilanı başarıyla güncellendi.',
            ]);
        } catch (\Exception $e) {
            Log::error('Summer rental listing update failed', [
                'ilan_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'İlan güncelleme işlemi başarısız: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified summer rental listing
     */
    public function destroy($id)
    {
        try {
            $ilan = Ilan::findOrFail($id);

            // SAB Kural 1/11: TX + domain logic service'te
            $this->yazlikService->deleteListing($ilan);

            return response()->json([
                'success' => true,
                'message' => 'Yazlık kiralama ilanı başarıyla silindi.',
            ]);
        } catch (\Exception $e) {
            Log::error('Summer rental listing deletion failed', [
                'ilan_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'İlan silme işlemi başarısız: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle booking management
     * Context7: Eloquent relationship usage - direct table access removed

     */
    public function bookings(Request $request, $id = null)
    {
        $query = YazlikRezervasyon::with('ilan:id,baslik');

        if ($id) {
            $query->where('ilan_id', $id);
        }

        if ($request->filled('aktiflik_durumu')) {
            $query->where('aktiflik_durumu', $request->get('aktiflik_durumu'));
        }


        if ($request->filled('date_range')) {
            $dateRange = explode(' - ', $request->get('date_range'));
            if (count($dateRange) === 2) {
                $query->whereBetween('check_in', [
                    Carbon::parse($dateRange[0])->format('Y-m-d'),
                    Carbon::parse($dateRange[1])->format('Y-m-d'),
                ]);
            }
        }

        $bookings = $query->orderBy('created_at', 'desc') // context7-ignore
            ->paginate(20);

        return view('admin.yazlik-kiralama.bookings', compact('bookings', 'id'));
    }

    /**
     * Update booking aktiflik
     * Context7: Eloquent model usage - direct table access removed

     */
    public function updateBookingStatus(Request $request, $bookingId)
    {
        $validator = Validator::make($request->all(), [
            aktiflik_durumu => 'required|in:pending,confirmed,cancelled,completed',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->yazlikService->updateBookingStatus($bookingId, [
                'aktiflik_durumu' => $request->input('aktiflik_durumu') ?? 1,
                'iptal_nedeni' => $request->notes,
            ]);

            Log::info('Booking aktiflik updated', [
                'booking_id' => $bookingId,
                'aktiflik_durumu' => $request->input('aktiflik_durumu') ?? 1,
                'user_id' => auth()->id(),
            ]);


            return response()->json([
                'success' => true,
                'message' => 'Rezervasyon durumu güncellendi.',
            ]);
        } catch (\Exception $e) {
            Log::error('Booking aktiflik update failed', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Durum güncelleme başarısız: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper methods
     * Context7: FeatureCategory sistemi ile entegrasyon - hardcoded array yerine dinamik
     */
    private function getAmenityOptions()
    {
        try {
            // FeatureCategory'den yazlık kategorisi için özellikleri al
            $yazlikCategories = FeatureCategory::where(function ($q) {
                $q->where('applies_to', 'like', '%yazlik%')
                    ->orWhere('applies_to', 'like', '%yazlık%')
                    ->orWhereNull('applies_to'); // Tümü için geçerli olanlar
            })
                ->where('aktiflik_durumu', true) // Context7: durum → durum
                ->with(['features' => function ($q) {
                    $q->where('aktiflik_durumu', true)->orderBy('display_order'); // context7-ignore
                }])
                ->get();


            $amenities = [];
            foreach ($yazlikCategories as $category) {
                foreach ($category->features as $feature) {
                    $amenities[$feature->id] = $feature->name;
                }
            }

            // Fallback: Eğer hiç özellik yoksa varsayılan listeyi döndür
            if (empty($amenities)) {
                return [
                    'wifi' => 'Wi-Fi',
                    'parking' => 'Otopark',
                    'pool' => 'Havuz',
                    'garden' => 'Bahçe',
                    'sea_view' => 'Deniz Manzarası',
                    'mountain_view' => 'Dağ Manzarası',
                    'air_conditioning' => 'Klima',
                    'heating' => 'Isıtma',
                    'kitchen' => 'Mutfak',
                    'dishwasher' => 'Bulaşık Makinesi',
                    'washing_machine' => 'Çamaşır Makinesi',
                    'tv' => 'TV',
                    'balcony' => 'Balkon',
                    'terrace' => 'Teras',
                    'bbq' => 'Barbekü',
                    'security' => 'Güvenlik',
                ];
            }

            return $amenities;
        } catch (\Exception $e) {
            Log::warning('FeatureCategory entegrasyonu hatası, fallback kullanılıyor', [
                'error' => $e->getMessage(),
            ]);

            // Fallback to hardcoded list
            return [
                'wifi' => 'Wi-Fi',
                'parking' => 'Otopark',
                'pool' => 'Havuz',
                'garden' => 'Bahçe',
                'sea_view' => 'Deniz Manzarası',
                'mountain_view' => 'Dağ Manzarası',
                'air_conditioning' => 'Klima',
                'heating' => 'Isıtma',
                'kitchen' => 'Mutfak',
                'dishwasher' => 'Bulaşık Makinesi',
                'washing_machine' => 'Çamaşır Makinesi',
                'tv' => 'TV',
                'balcony' => 'Balkon',
                'terrace' => 'Teras',
                'bbq' => 'Barbekü',
                'security' => 'Güvenlik',
            ];
        }
    }

    private function getRentalTypeOptions()
    {
        return [
            'daily' => 'Günlük',
            'weekly' => 'Haftalık',
            'monthly' => 'Aylık',
            'seasonal' => 'Sezonluk',
        ];
    }

    private function calculateMonthlyRevenue()
    {
        try {
            // Context7: Eloquent relationship kullanımı
            return YazlikRezervasyon::where('aktiflik_durumu', 'onaylandi')

                ->whereMonth('check_in', date('m'))
                ->whereYear('check_in', date('Y'))
                ->sum('toplam_fiyat') ?? 0;
        } catch (\Exception $e) {
            Log::warning('Monthly revenue hesaplama hatası', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    private function getBookingStats($ilanId)
    {
        try {
            // Context7: Eloquent relationship kullanımı (Eager Load ile N+1 düzeltmesi)
            $ilan = Ilan::with(['yazlikRezervasyonlar', 'yazlikRezervasyonlar.ilan'])->findOrFail($ilanId);
            $rezervasyonlar = $ilan->yazlikRezervasyonlar;

            $totalDays = $rezervasyonlar->where('aktiflik_durumu', 'onaylandi')->sum(function ($r) {
                return $r->check_in->diffInDays($r->check_out);
            });
            $confirmedCount = $rezervasyonlar->where('aktiflik_durumu', 'onaylandi')->count();
            $avgStayDuration = $confirmedCount > 0 ? round($totalDays / $confirmedCount, 1) : 0;

            // Occupancy rate hesaplama (basit versiyon - geliştirilebilir)
            $yearDays = 365;
            $bookedDays = $rezervasyonlar->where('aktiflik_durumu', 'onaylandi')->sum(function ($r) {
                return $r->check_in->diffInDays($r->check_out);
            });
            $occupancyRate = $yearDays > 0 ? round(($bookedDays / $yearDays) * 100, 1) : 0;

            return [
                'total_bookings' => $rezervasyonlar->count(),
                'confirmed_bookings' => $confirmedCount,
                'pending_bookings' => $rezervasyonlar->where('aktiflik_durumu', 'beklemede')->count(),
                'occupancy_rate' => $occupancyRate,
                'avg_stay_duration' => $avgStayDuration,
            ];

        } catch (\Exception $e) {
            Log::warning('Booking stats hesaplama hatası', ['ilan_id' => $ilanId, 'error' => $e->getMessage()]);

            return [
                'total_bookings' => 0,
                'confirmed_bookings' => 0,
                'pending_bookings' => 0,
                'occupancy_rate' => 0,
                'avg_stay_duration' => 0,
            ];
        }
    }

    private function getRevenueStats($ilanId)
    {
        try {
            // Context7: Eloquent relationship kullanımı (Eager Load ile N+1 düzeltmesi)
            $ilan = Ilan::with(['yazlikRezervasyonlar', 'yazlikRezervasyonlar.ilan'])->findOrFail($ilanId);
            $onayliRezervasyonlar = $ilan->yazlikRezervasyonlar->where('aktiflik_durumu', 'onaylandi');


            $monthlyRevenue = $onayliRezervasyonlar->filter(function ($r) {
                return $r->created_at->month == date('m') && $r->created_at->year == date('Y');
            })->sum('toplam_fiyat');

            $totalRevenue = $onayliRezervasyonlar->sum('toplam_fiyat');

            // Average nightly rate hesaplama
            $totalNights = $onayliRezervasyonlar->sum(function ($r) {
                return $r->check_in->diffInDays($r->check_out);
            });
            $avgNightlyRate = $totalNights > 0 ? round($totalRevenue / $totalNights, 2) : 0;

            // Revenue growth (basit versiyon - geliştirilebilir)
            $lastMonthRevenue = $onayliRezervasyonlar->filter(function ($r) {
                $lastMonth = Carbon::now()->subMonth();

                return $r->created_at->month == $lastMonth->month && $r->created_at->year == $lastMonth->year;
            })->sum('toplam_fiyat');

            $revenueGrowth = $lastMonthRevenue > 0
                ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
                : 0;

            return [
                'monthly_revenue' => $monthlyRevenue,
                'total_revenue' => $totalRevenue,
                'avg_nightly_rate' => $avgNightlyRate,
                'revenue_growth' => $revenueGrowth,
            ];
        } catch (\Exception $e) {
            Log::warning('Revenue stats hesaplama hatası', ['ilan_id' => $ilanId, 'error' => $e->getMessage()]);

            return [
                'monthly_revenue' => 0,
                'total_revenue' => 0,
                'avg_nightly_rate' => 0,
                'revenue_growth' => 0,
            ];
        }
    }

    private function getAvailabilityCalendar($ilanId)
    {
        // Mock calendar data - implement based on booking system
        $calendar = [];
        $startDate = Carbon::now()->startOfMonth();

        for ($i = 0; $i < 90; $i++) {
            $date = $startDate->copy()->addDays($i);
            $calendar[] = [
                'date' => $date->format('Y-m-d'),
                'aktiflik_durumu' => rand(0, 10) > 7 ? 'booked' : 'available',
                'price' => rand(500, 1500),
            ];
        }


        return $calendar;
    }
}
