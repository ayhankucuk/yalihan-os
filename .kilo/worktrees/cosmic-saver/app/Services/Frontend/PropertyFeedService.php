<?php

namespace App\Services\Frontend;

use App\Enums\IlanDurumu;

use App\Models\Ilan;
use App\Services\CurrencyConversionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyFeedService
{
    public function __construct(
        private readonly CurrencyConversionService $currencyConversionService
    ) {}

    public function getFeatured(int $limit = 6, ?string $currency = null): Collection
    {
        $currency = $this->resolveCurrency($currency);
        $cacheKey = 'frontend:properties:featured:' . $limit . ':' . $currency;
        $cacheTTL = 300; // 5 dakika

        $cacheService = app(\App\Services\Cache\CacheService::class);
        return $cacheService->rememberWithPrefix('frontend-properties', $cacheKey, $cacheTTL, function () use ($limit, $currency) {
            return $this->baseQuery()
                ->latest()
                ->limit($limit)
                ->get()
                ->map(fn(Ilan $ilan) => $this->transform($ilan, $currency));
        });
    }

    public function paginate(array $filters = [], int $perPage = 12, ?string $currency = null): LengthAwarePaginator|Paginator
    {
        $currency = $this->resolveCurrency($currency);

        $query = $this->applyFilters($this->baseQuery(), $filters);

        $paginator = $query->paginate($perPage);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator->through(function (Ilan $ilan) use ($currency) {
            return $this->transform($ilan, $currency);
        });

        return $paginator;
    }

    public function find(int $id, ?string $currency = null): ?array
    {
        $currency = $this->resolveCurrency($currency);
        $cacheKey = 'frontend:properties:' . $id . ':' . $currency;
        $cacheTTL = 600; // 10 dakika

        $cacheService = app(\App\Services\Cache\CacheService::class);
        return $cacheService->rememberWithPrefix('frontend-properties', $cacheKey, $cacheTTL, function () use ($id, $currency) {
            $ilan = $this->baseQuery()->find($id);
            return $ilan ? $this->transform($ilan, $currency) : null;
            });
    }

    protected function transform(Ilan $ilan, string $currency): array
    {
        // Use the standardized Mobile Resource Logic
        $resource = new \App\Http\Resources\Mobile\IlanListResource($ilan);
        $data = $resource->resolve(); // Get the array

        // Add extra fields needed for frontend list view if any specific ones are missing from mobile resource
        // The mobile resource is already lightweight and standard.
        // We might need to map 'price_display' to 'formatted' if frontend expects it specifically.

        return $data;
    }

    private function buildPhotosPayload(Ilan $ilan): Collection
    {
        return $ilan->fotograflar
            ->sortByDesc('kapak_fotografi')
            ->sortBy('display_order')
            ->map(fn($photo) => [
                'url' => Storage::exists($photo->dosya_yolu)
                    ? Storage::url($photo->dosya_yolu)
                    : $this->placeholderImage(),
                'alt' => $photo->alt_text ?: $ilan->baslik,
            ])
            ->values();
    }

    private function buildLocationParts(Ilan $ilan): Collection
    {
        return collect([
            optional($ilan->mahalle)->mahalle_adi,
            optional($ilan->ilce)->ilce_adi,
            optional($ilan->il)->il_adi,
        ])->filter()->unique();
    }

    private function buildPricingPayload(Ilan $ilan, string $currency): array
    {
        $convertedPriceData = $this->currencyConversionService->convert(
            $ilan->fiyat,
            $ilan->para_birimi,
            $currency
        );

        $convertedPrice = $convertedPriceData['amount'] ?? $ilan->fiyat;
        $priceDisplay = $this->formatCurrency($convertedPrice, $currency);
        $pricePeriod = $this->determinePricePeriod($ilan);

        return [
            'amount' => $convertedPrice,
            'currency' => $currency,
            'formatted' => $priceDisplay,
            'period' => $pricePeriod,
            'original' => [
                'amount' => $ilan->fiyat,
                'currency' => $ilan->para_birimi,
            ],
        ];
    }

    private function buildModalDetail(Ilan $ilan, string $location, string $price): array
    {
        return [
            'title' => $ilan->baslik,
            'location' => $location,
            'price' => $price,
            'price_period' => $this->determinePricePeriod($ilan),
            'description' => Str::limit(strip_tags((string) $ilan->aciklama), 280),
            'features' => $this->propertyFeatures($ilan),
            'link' => $this->getShareUrl($ilan),
        ];
    }

    private function buildContactPayload(Ilan $ilan, string $location): array
    {
        return [
            'id' => $ilan->id,
            'title' => $ilan->baslik,
            'location' => $location,
            'contact_url' => url('/iletisim'),
            'danisman' => $ilan->danisman ? [
                'name' => $ilan->danisman->name,
                'phone' => $ilan->danisman->phone_number,
                'office_phone' => $ilan->danisman->office_phone,
                'email' => $ilan->danisman->email,
            ] : null,
        ];
    }

    private function buildMapPayload(Ilan $ilan, string $location, $lat, $lng): array
    {
        return [
            'lat' => $lat,
            'lng' => $lng,
            'title' => $ilan->baslik,
            'content' => $location,
        ];
    }

    private function buildMediaPayload(Ilan $ilan, Collection $photos, string $coverImage): array
    {
        return [
            'cover' => [
                'url' => $coverImage,
                'alt' => $ilan->baslik,
            ],
            'gallery' => $photos->all(),
            'virtual_tour' => $ilan->sanal_tur_url ?? $ilan->youtube_video_url,
            'video' => $ilan->youtube_video_url,
        ];
    }

    private function buildLinksPayload(Ilan $ilan, $lat, $lng): array
    {
        $selfLink = Route::has('api.frontend.properties.show')
            ? route('api.frontend.properties.show', $ilan->id)
            : null;

        return [
            'self' => $selfLink,
            'detail_page' => $this->getShareUrl($ilan),
            'contact_page' => url('/iletisim'),
            'nearby' => ($lat && $lng && Route::has('api.location.nearby'))
                ? route('api.location.nearby', ['lat' => $lat, 'lng' => $lng])
                : null,
        ];
    }

    private function buildLocationDetailPayload(Ilan $ilan, $lat, $lng): array
    {
        return [
            'neighborhood' => optional($ilan->mahalle)->mahalle_adi,
            'district' => optional($ilan->ilce)->ilce_adi,
            'city' => optional($ilan->il)->il_adi,
            'coordinates' => [
                'lat' => $lat,
                'lng' => $lng,
            ],
        ];
    }

    private function getShareUrl(Ilan $ilan): string
    {
        return Route::has('yalihan.property.detail')
            ? route('yalihan.property.detail', $ilan->id)
            : url('/portfolio/' . $ilan->id);
    }

    protected function baseQuery(): Builder
    {
        return Ilan::query()
            ->whereIn('yayin_durumu', [IlanDurumu::YAYINDA->value, 'yayinda'])
            ->select([
                'id',
                'baslik',
                'aciklama',
                'fiyat',
                'para_birimi',
                'slug',
                'il_id',
                'ilce_id',
                'mahalle_id',
                'ana_kategori_id',
                'alt_kategori_id',
                'oda_sayisi',
                'banyo_sayisi',
                'brut_m2',
                'sanal_tur_url',
                'youtube_video_url',
                'latitude',
                'longitude',
                'lat',
                'lng',
                'gunluk_fiyat',
                'haftalik_fiyat',
                'aylik_fiyat',
                'islem_tipi',
                'created_at',
            ])
            ->with([
                'il:id,il_adi',
                'ilce:id,ilce_adi',
                'mahalle:id,mahalle_adi',
                'anaKategori:id,name,slug',
                'fotograflar:id,ilan_id,dosya_yolu,kapak_fotografi,display_order,alt_text',
                'danisman:id,name,phone_number,office_phone,email',
            ]);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if ($category = $filters['category'] ?? null) {
            $query->where('ana_kategori_id', $category);
        }

        if ($minPrice = $filters['min_price'] ?? null) {
            $query->where('fiyat', '>=', $minPrice);
        }

        if ($maxPrice = $filters['max_price'] ?? null) {
            $query->where('fiyat', '<=', $maxPrice);
        }

        if ($district = $filters['district'] ?? null) {
            $query->where('ilce_id', $district);
        }

        if ($neighborhood = $filters['neighborhood'] ?? null) {
            $query->where('mahalle_id', $neighborhood);
        }

        return $query;
    }

    protected function formatCurrency(float $amount, string $currency): string
    {
        $formatted = number_format($amount, 0, ',', '.');

        return match ($currency) {
            'TRY' => "₺{$formatted}",
            'USD' => "\${$formatted}",
            'EUR' => "€{$formatted}",
            default => $formatted . ' ' . $currency,
        };
    }

    protected function determinePricePeriod(Ilan $ilan): ?string
    {
        return match (true) {
            ! is_null($ilan->gunluk_fiyat) => '/gün',
            ! is_null($ilan->haftalik_fiyat) => '/hafta',
            ! is_null($ilan->aylik_fiyat) => '/ay',
            default => null,
        };
    }

    protected function determineBadge(Ilan $ilan): array
    {
        $badge = ['value' => 'sale', 'label' => 'Satılık'];

        if (Str::contains(Str::lower($ilan->baslik . ' ' . $ilan->aciklama), ['kiralık', 'kiralik'])) {
            $badge = ['value' => 'rent', 'label' => 'Kiralık'];
        }

        if ($ilan->anaKategori && Str::contains(Str::lower($ilan->anaKategori->name), ['yazlık', 'villa'])) {
            $badge = ['value' => 'featured', 'label' => 'Öne Çıkan'];
        }

        return $badge;
    }

    protected function propertyFeatures(Ilan $ilan): array
    {
        return collect([
            ['label' => 'Oda', 'value' => $ilan->oda_sayisi],
            ['label' => 'Banyo', 'value' => $ilan->banyo_sayisi],
            ['label' => 'Brüt m²', 'value' => $ilan->brut_m2],
            ['label' => 'Kategori', 'value' => optional($ilan->anaKategori)->name],
        ])
            ->filter(fn($feature) => filled($feature['value']))
            ->values()
            ->all();
    }

    protected function placeholderImage(): string
    {
        return 'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1200&q=80';
    }

    protected function resolveCurrency(?string $currency): string
    {
        $resolved = $currency ?: session('currency', $this->currencyConversionService->getDefault());

        return strtoupper($resolved);
    }
}
