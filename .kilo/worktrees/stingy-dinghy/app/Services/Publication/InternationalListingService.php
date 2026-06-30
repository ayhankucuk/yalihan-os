<?php

namespace App\Services\Publication;

use App\Enums\IlanDurumu;

use App\Models\Il;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Ulke;
use App\Services\CurrencyConversionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

/**
 * Service for handling International Portfolio logic.
 * Encapsulates filtering, statistics, and static data for international listings.
 */
class InternationalListingService
{
    public function __construct(
        protected CurrencyConversionService $currencyConversionService
    ) {}

    /**
     * Get filtered international listings query
     */
    public function getFilteredQuery(array $filters): Builder
    {
        $query = Ilan::query()->byYayinDurumu(IlanDurumu::YAYINDA->value);

        if (Schema::hasColumn('ilanlar', 'ulke_id')) {
            $query->whereNotNull('ulke_id');
        }

        $activeTab = $filters['type'] ?? 'sale'; // context7-ignore

        // İşlem tipi filtresi
        if ($activeTab === 'sale') {
            $query->where(function ($q) {
                $q->where('baslik', 'like', '%satılık%')
                    ->orWhere('baslik', 'like', '%satilik%')
                    ->orWhere('aciklama', 'like', '%satılık%')
                    ->orWhere('aciklama', 'like', '%satilik%');
            });
        } elseif ($activeTab === 'rent') {
            $query->where(function ($q) {
                $q->where('baslik', 'like', '%kiralık%')
                    ->orWhere('baslik', 'like', '%kiralik%')
                    ->orWhere('aciklama', 'like', '%kiralık%')
                    ->orWhere('aciklama', 'like', '%kiralik%');
            });
        }

        if ($activeTab === 'seasonal' && Schema::hasColumn('ilanlar', 'gunluk_fiyat')) {
            $query->whereNotNull('gunluk_fiyat');
        }

        if ($activeTab === 'citizenship' && Schema::hasColumn('ilanlar', 'citizenship_eligible')) {
            $query->where('citizenship_eligible', true);
        }

        // Apply advanced filters
        $this->applyAdvancedFilters($query, $filters);

        return $query;
    }

    /**
     * Apply secondary filters like country, city, price, area
     */
    protected function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['country']) && Schema::hasColumn('ilanlar', 'ulke_id')) {
            $country = $filters['country'];
            $query->whereHas('ulke', function ($subQuery) use ($country) {
                $subQuery->where('ulke_kodu', $country)->orWhere('id', $country);
            });
        }

        if (!empty($filters['city']) && Schema::hasColumn('ilanlar', 'il_id')) {
            $query->where('il_id', $filters['city']);
        }

        if (!empty($filters['citizenship']) && Schema::hasColumn('ilanlar', 'citizenship_eligible')) {
            $citizenshipFilter = $filters['citizenship'];
            if ($citizenshipFilter === 'eligible') {
                $query->where('citizenship_eligible', true);
            } elseif ($citizenshipFilter === 'not-eligible') {
                $query->where(function ($subQuery) {
                    $subQuery->whereNull('citizenship_eligible')->orWhere('citizenship_eligible', false);
                });
            }
        }

        if (!empty($filters['min_price'])) {
            $query->where('fiyat', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('fiyat', '<=', $filters['max_price']);
        }

        if (!empty($filters['property_type']) && Schema::hasColumn('ilanlar', 'ana_kategori_id')) {
            $query->where('ana_kategori_id', $filters['property_type']);
        }

        if ((!empty($filters['min_area']) || !empty($filters['max_area'])) && Schema::hasColumn('ilanlar', 'brut_m2')) {
            $query->where(function ($subQuery) use ($filters) {
                if (!empty($filters['min_area'])) {
                    $subQuery->where('brut_m2', '>=', $filters['min_area']);
                    if (Schema::hasColumn('ilanlar', 'net_m2')) {
                        $subQuery->orWhere('net_m2', '>=', $filters['min_area']);
                    }
                }

                if (!empty($filters['max_area'])) {
                    $subQuery->where('brut_m2', '<=', $filters['max_area']);
                    if (Schema::hasColumn('ilanlar', 'net_m2')) {
                        $subQuery->orWhere('net_m2', '<=', $filters['max_area']);
                    }
                }
            });
        }

        // Serbest metin arama (ülke/şehir adı girişi için)
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('baslik', 'like', "%{$term}%")
                  ->orWhere('aciklama', 'like', "%{$term}%")
                  ->orWhereHas('il', fn ($s) => $s->where('il_adi', 'like', "%{$term}%"));
            });
        }
    }

    /**
     * Get filter options (dropdown data)
     */
    public function getFilterOptions(): array
    {
        $countries = Ulke::select(['id', 'ulke_adi', 'ulke_kodu'])
            ->orderBy('ulke_adi') // context7-ignore
            ->get()
            ->map(fn($ulke) => [
                'id' => $ulke->id,
                'name' => $ulke->ulke_adi,
                'code' => $ulke->ulke_kodu,
            ])->values()->all();

        $cities = Il::select(['id', 'il_adi'])
            ->orderBy('il_adi') // context7-ignore
            ->get()
            ->map(fn($il) => [
                'id' => $il->id,
                'name' => $il->il_adi,
                'code' => $il->id,
            ])->values()->all();

        $propertyTypes = IlanKategori::query()
            ->whereNull('parent_id')
            ->orderBy('name') // context7-ignore
            ->limit(8)
            ->get(['id', 'name'])
            ->map(fn($kategori) => [
                'value' => $kategori->id,
                'label' => $kategori->name,
            ])->values()->all();

        return [
            'countries' => $countries,
            'cities' => $cities,
            'types' => $propertyTypes, // context7-ignore
        ];
    }

    /**
     * Calculate statistics for the view
     */
    public function getStatistics(Builder $baseQuery): array
    {
        $statsQuery = clone $baseQuery;

        // Note: Logic copied from controller.
        // Warning: This statistic logic relies on the *filtered* query being passed in?
        // Original controller cloned $query which had ALL filters applied.

        $countQuery = clone $statsQuery;

        return [
            'total' => $countQuery->count(),
            'citizenship' => Schema::hasColumn('ilanlar', 'citizenship_eligible')
                ? (clone $statsQuery)->where('citizenship_eligible', true)->count()
                : 0,
            'countries' => Schema::hasColumn('ilanlar', 'ulke_id')
                ? Ilan::byYayinDurumu(IlanDurumu::YAYINDA->value)->whereNotNull('ulke_id')->distinct('ulke_id')->count('ulke_id')
                : Ilan::byYayinDurumu(IlanDurumu::YAYINDA->value)->distinct('il_id')->count('il_id'),
            'average_price' => optional((clone $statsQuery)->avg('fiyat'), function ($avg) {
                return number_format($avg, 0, ',', '.').' ₺';
            }) ?? '—',
        ];
    }

    /**
     * Get featured international listings with currency conversion
     */
    public function getFeaturedListings(Builder $query, string $targetCurrency = 'TRY'): \Illuminate\Pagination\LengthAwarePaginator
    {
        $featured = (clone $query)
            ->select(['id', 'baslik', 'aciklama', 'fiyat', 'para_birimi', 'il_id', 'ilce_id', 'ana_kategori_id', 'created_at'])
            ->with([
                'il:id,il_adi',
                'ilce:id,ilce_adi',
                'anaKategori:id,name',
                'fotograflar:id,ilan_id,dosya_yolu,kapak_fotografi',
            ])
            ->orderByDesc('created_at') // context7-ignore
            ->paginate(9);

        // Transform collection for currency conversion
        foreach ($featured as $ilan) {
            $ilan->converted_price = $this->currencyConversionService->convert(
                $ilan->fiyat,
                $ilan->para_birimi,
                $targetCurrency
            );
        }

        return $featured;
    }

    /**
     * Get static citizenship programs data
     */
    public function getCitizenshipPrograms(): array
    {
        return [
            [
                'country' => 'Türkiye',
                'processing_time' => '6-8 Ay',
                'min_investment' => '$400.000',
                'highlights' => [
                    'Aile bireyleri için tam vatandaşlık',
                    'Hızlı süreç ve noter destekli belge yönetimi',
                    'Gayrimenkul yatırımıyla pasif gelir fırsatı',
                ],
            ],
            [
                'country' => 'Yunanistan',
                'processing_time' => '3-4 Ay',
                'min_investment' => '€250.000',
                'highlights' => [
                    'Schengen bölgesinde serbest dolaşım',
                    '5 yılda kalıcı oturum ve vatandaşlık başvurusu',
                    'Emlak gelirlerinde vergi avantajı',
                ],
            ],
            [
                'country' => 'Birleşik Arap Emirlikleri',
                'processing_time' => '2-3 Ay',
                'min_investment' => '$544.500',
                'highlights' => [
                    'Golden Visa ile uzun dönem oturum',
                    'Vergi avantajlı yatırım fırsatları',
                    'Yüksek kira getirisi ve dolar endeksi',
                ],
            ],
        ];
    }

    /**
     * Get static FAQ data
     */
    public function getFaqs(): array
    {
        return [
            [
                'question' => 'Vatandaşlık için minimum yatırım tutarı nedir?',
                'answer' => 'Her ülkenin farklı şartları vardır. Türkiye için $400.000, Yunanistan için €250.000 başlangıç tutarı gerekir. Danışmanlarımız detaylı bilgi sunar.',
            ],
            [
                'question' => 'AI rehberi nasıl çalışır?',
                'answer' => 'Bütçe, ülke tercihi, vatandaşlık beklentinizi alır ve global portföydeki ilanları analiz ederek öneri listesi oluşturur. Analiz sonuçları e-posta ile paylaşılır.',
            ],
            [
                'question' => 'Fiyatlar hangi para birimi ile gösteriliyor?',
                'answer' => 'Varsayılan olarak yerel para birimi kullanılır. Üst bardaki para birimi seçicisi ile USD, EUR veya TRY cinsinden güncel kurla görüntüleyebilirsiniz.',
            ],
            [
                'question' => 'Yurtdışı alımlarda hukuki süreç nasıl yönetiliyor?',
                'answer' => 'Partner hukuk bürolarımız tapu, noter, oturum başvuruları ve çeviri süreçlerini yönetir. Her yatırım için özel bir danışman atanır.',
            ],
        ];
    }
}
