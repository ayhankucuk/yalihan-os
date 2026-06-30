<?php

namespace App\Services\Category;

/**
 * @sab-ignore-catch
 */

use App\Models\FeatureCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Feature Category Service
 *
 * Context7 Standardı: C7-FEATURE-CATEGORY-SERVICE-2025-12-06
 *
 * Feature categories query logic'ini merkezi olarak yönetir.
 * Kategori bazlı feature categories işlemleri.
 *
 * @package App\Services\Category
 */
class FeatureCategoryService
{
    /**
     * Arsa için mantıksız feature slug'ları
     * Bu özellikler arsa kategorisi için gösterilmemeli
     * ✨ UPDATED: 5 Ocak 2026 - Daha kapsamlı filtre
     */
    private array $arsaDisallowedFeatureSlugs = [
        // ⚠️ Konut İç Mekan Özellikleri
        'oda-sayisi', 'banyo-sayisi', 'salon-sayisi', 'balkon-sayisi',
        'oda', 'banyo', 'salon', 'balkon', 'yatak',
        'ankastre', 'klima', 'wifi', 'tv',
        'ebeveyn-banyosu', 'dusakabin', 'depo', 'kiler',
        'isitma-tipi', 'kalorifer',

        // ⚠️ Bina/Site Özellikleri
        'asansor', 'jenerator', 'hidrofor', 'su-deposu',
        'yangin-merdiveni', 'otopark', 'kapali-otopark',
        'havuz', 'fitness', 'guvenlik', 'kamerali-guvenlik',
        'cocuk-oyun', 'basketbol',

        // ⚠️ İç Dekorasyon
        'mutfak', 'teras', 'buzdolabi', 'camasir',
        'bulasik', 'firin', 'ocak', 'aspirator',
        'dus', 'klozet', 'lavabo', 'dolap', 'gardrop',
        'koltuk', 'masa', 'sandalye', 'perde', 'hali',
        'parke', 'seramik', 'fayans', 'boya',
        'duvar', 'tavan', 'zemin', 'kapi', 'pencere',
        'cam', 'kasa', 'kilit', 'zil', 'interkom',
        'merdiven', 'koridor', 'hol', 'giris', 'cikis',
        'garaj', 'mahzen',
        'bodrum',
        'cati',
        'catı',
        'oda-sayisi',
        'salon-sayisi',
        'banyo-sayisi',
        'yatak-sayisi',
        'balkon-sayisi',
        // Yazlık özellikleri
        'gunluk-fiyat',
        'haftalik-fiyat',
        'aylik-fiyat',
        'sezonluk-fiyat',
        'min-konaklama',
        'max-misafir',
        'check-in',
        'check-out',
        'iptal-politikasi',
        'depozito',
        'temizlik-ucreti',
        'havuz-kullanim',
        'bebek-uygun',
        'cocuk-uygun',
        'pet-friendly',
    ];

    /**
     * Kategori için feature categories getir
     *
     * @param string $kategoriSlug Kategori slug
     * @param array $allowedNames İzin verilen kategori isimleri
     * @return Collection
     */
    public function getCategoriesForKategori(string $kategoriSlug, array $allowedNames): Collection
    {
        $query = FeatureCategory::where(function ($q) use ($allowedNames, $kategoriSlug) {
            $q->whereIn('name', $allowedNames);
            // ✅ SAB: applies_to removed - use FeatureAssignment instead

            // ⚠️ ARSA İÇİN: Konut/Yazlık/İşyeri kategorilerini tamamen kaldır
            if (in_array($kategoriSlug, ['arsa', 'arsa-arazi'])) {
                $q->where('name', '!=', 'Konut Özellikleri');
                $q->where('name', '!=', 'Site Özellikleri');
                $q->where('name', '!=', 'Bina Özellikleri');
                $q->where('name', '!=', 'Konaklama ve Tarih Bilgileri');
                $q->where('name', '!=', 'Proje Tipi');
                $q->where('name', '!=', 'İşyeri Özellikleri');
            }

            // ⚠️ YAZLIK İÇİN: Arsa/İşyeri/İnşaat kategorilerini kaldır
            if (in_array($kategoriSlug, ['yazlik-kiralama'])) {
                $q->where('name', '!=', 'Arsa Özellikleri');
                $q->where('name', '!=', 'İşyeri Özellikleri');
                $q->where('name', '!=', 'İnşaat Teknikleri');
            }
        })
            ->where('aktiflik_durumu', true)
            ->with(['features' => function ($q) use ($kategoriSlug) {
                $q->where('aktiflik_durumu', true)
                    ->orderBy('display_order') // context7-ignore
                    ->orderBy('name'); // context7-ignore

                // ✅ Arsa için mantıksız özellikleri filtrele (ek güvenlik)
                // ⚠️ FIX: 'arsa-arazi' slug'ını kontrol et
                if (in_array($kategoriSlug, ['arsa', 'arsa-arazi'])) {
                    $q->where(function ($subQ) {
                        foreach ($this->arsaDisallowedFeatureSlugs as $disallowedSlug) {
                            $subQ->where('slug', 'not like', "%{$disallowedSlug}%");
                        }
                    });
                }
            }])
            ->orderBy('display_order') // context7-ignore
            ->orderBy('name'); // context7-ignore

        $featureCategories = $query->get();

        // Fallback: Eğer kategori bazlı bulunamazsa tüm aktifleri getir
        if ($featureCategories->isEmpty()) {
            $query = FeatureCategory::where('aktiflik_durumu', true);

            // ⚠️ ARSA İÇİN: Konut/Site/Bina/Konaklama/Proje/İşyeri kategorilerini kaldır
            if (in_array($kategoriSlug, ['arsa', 'arsa-arazi'])) {
                $query->where('name', '!=', 'Konut Özellikleri')
                      ->where('name', '!=', 'Site Özellikleri')
                      ->where('name', '!=', 'Bina Özellikleri')
                      ->where('name', '!=', 'Konaklama ve Tarih Bilgileri')
                      ->where('name', '!=', 'Proje Tipi')
                      ->where('name', '!=', 'İşyeri Özellikleri');
            }

            // ⚠️ YAZLIK İÇİN: Arsa/İşyeri/İnşaat kategorilerini kaldır
            if (in_array($kategoriSlug, ['yazlik-kiralama'])) {
                $query->where('name', '!=', 'Arsa Özellikleri')
                      ->where('name', '!=', 'İşyeri Özellikleri')
                      ->where('name', '!=', 'İnşaat Teknikleri');
            }

            $featureCategories = $query
                ->with(['features' => function ($q) use ($kategoriSlug) {
                    $q->where('aktiflik_durumu', true)
                        ->orderBy('display_order') // context7-ignore
                        ->orderBy('name'); // context7-ignore

                    // ✅ Arsa için mantıksız özellikleri filtrele
                    // ⚠️ FIX: 'arsa-arazi' slug'ını kontrol et
                    if (in_array($kategoriSlug, ['arsa', 'arsa-arazi'])) {
                        $q->where(function ($subQ) {
                            foreach ($this->arsaDisallowedFeatureSlugs as $disallowedSlug) {
                                $subQ->where('slug', 'not like', "%{$disallowedSlug}%");
                            }
                        });
                    }
                }])
                ->orderBy('display_order') // context7-ignore
                ->orderBy('name') // context7-ignore
                ->get();
        }

        return $this->filterActiveFeatures($featureCategories, $kategoriSlug);
    }

    /**
     * Aktif feature'ları filtrele
     *
     * @param Collection $categories
     * @param string $kategoriSlug Kategori slug (arsa için ek filtreleme için)
     * @return Collection
     */
    public function filterActiveFeatures(Collection $categories, string $kategoriSlug = ''): Collection
    {
        return $categories
            ->map(function ($category) use ($kategoriSlug) {
                $category->features = $category->features->filter(function ($feature) use ($kategoriSlug) {
                    // Status kontrolü
                    if ($feature->aktiflik_durumu !== true) {
                        return false;
                    }

                    // ✅ Arsa için ek filtreleme
                    // ⚠️ FIX: 'arsa-arazi' slug'ını kontrol et
                    if (in_array($kategoriSlug, ['arsa', 'arsa-arazi'])) {
                        $featureSlug = strtolower($feature->slug ?? '');
                        $featureName = strtolower($feature->name ?? '');

                        foreach ($this->arsaDisallowedFeatureSlugs as $disallowedSlug) {
                            if (str_contains($featureSlug, $disallowedSlug) || str_contains($featureName, $disallowedSlug)) {
                                return false;
                            }
                        }
                    }

                    return true;
                });
                return $category;
            })
            ->filter(fn($category) => $category->features->isNotEmpty());
    }

    /**
     * 🆕 PHASE 3: Kategori + Yayın Tipi bazında feature categories getir
     *
     * Context7 Standard: C7-FEATURE-YAYIN-TIPI-2026-01-05
     *
     * Bu metod kategori VE yayın tipi kombinasyonuna göre akıllı filtreleme yapar.
     * Örnek: Arsa-Satılık farklı, Konut-Günlük-Kiralık farklı özellikler gösterir.
     *
     * @param string $kategoriSlug Kategori slug (arsa-arazi, konut, yazlik-kiralama)
     * @param string $yayinTipiSlug Yayın tipi slug (satilik, kiralik, gunluk)
     * @param array $allowedNames İzin verilen kategori isimleri
     * @return Collection
     */
    public function getCategoriesForYayinTipi(
        string $kategoriSlug,
        string $yayinTipiSlug,
        array $allowedNames
    ): Collection {
        // 1. Kategori bazlı temel filtreleme (mevcut mantık)
        $query = FeatureCategory::where(function ($q) use ($allowedNames, $kategoriSlug) {
            $q->whereIn('name', $allowedNames);

            // Kategori bazlı kategori filtreleme
            if (in_array($kategoriSlug, ['arsa', 'arsa-arazi'])) {
                $q->where('name', '!=', 'Konut Özellikleri')
                  ->where('name', '!=', 'Site Özellikleri')
                  ->where('name', '!=', 'Bina Özellikleri')
                  ->where('name', '!=', 'Konaklama ve Tarih Bilgileri')
                  ->where('name', '!=', 'Proje Tipi')
                  ->where('name', '!=', 'İşyeri Özellikleri');
            }

            if (in_array($kategoriSlug, ['yazlik-kiralama'])) {
                $q->where('name', '!=', 'Arsa Özellikleri')
                  ->where('name', '!=', 'İşyeri Özellikleri')
                  ->where('name', '!=', 'İnşaat Teknikleri');
            }
        })
        ->where('aktiflik_durumu', true);

        // 2. ⭐ YENİ: Yayın tipi bazında feature slug filtreleme
        $disallowedFeatures = $this->getDisallowedFeaturesForYayinTipi($kategoriSlug, $yayinTipiSlug);

        $query->with(['features' => function ($q) use ($kategoriSlug, $disallowedFeatures) {
            $q->where('aktiflik_durumu', true)
              ->orderBy('display_order') // context7-ignore
              ->orderBy('name'); // context7-ignore

            // Kategori bazlı filtreleme (backward compatibility)
            if (in_array($kategoriSlug, ['arsa', 'arsa-arazi'])) {
                $q->where(function ($subQ) {
                    foreach ($this->arsaDisallowedFeatureSlugs as $disallowedSlug) {
                        $subQ->where('slug', 'not like', "%{$disallowedSlug}%");
                    }
                });
            }

            // ⭐ YENİ: Yayın tipi bazlı filtreleme
            if (!empty($disallowedFeatures)) {
                $q->where(function ($subQ) use ($disallowedFeatures) {
                    foreach ($disallowedFeatures as $disallowedSlug) {
                        $subQ->where('slug', '!=', $disallowedSlug)
                             ->where('slug', 'not like', "%{$disallowedSlug}%");
                    }
                });
            }
        }])
        ->orderBy('display_order') // context7-ignore
        ->orderBy('name'); // context7-ignore

        $featureCategories = $query->get();

        // Fallback logic (eğer yayın tipi bazlı bulunamazsa kategori bazlı dön)
        if ($featureCategories->isEmpty()) {
            return $this->getCategoriesForKategori($kategoriSlug, $allowedNames);
        }

        return $this->filterActiveFeatures($featureCategories, $kategoriSlug);
    }

    /**
     * 🆕 PHASE 3: Yayın tipi bazında yasaklı feature slug'ları getir
     *
     * Context7 Standard: C7-FEATURE-ASSIGNMENT-RULES-2026-01-05
     *
     * Config dosyasından (config/feature-assignment-rules.php) kuralları okur.
     * Örnek: arsa-arazi + satilik → ['oda-sayisi', 'banyo-sayisi', ...]
     *
     * @param string $kategoriSlug Kategori slug
     * @param string $yayinTipiSlug Yayın tipi slug
     * @return array Yasaklı feature slug'ları
     */
    protected function getDisallowedFeaturesForYayinTipi(
        string $kategoriSlug,
        string $yayinTipiSlug
    ): array {
        $rules = config('feature-assignment-rules', []);

        // Config'den ilgili kuralı çek
        $categoryRules = $rules[$kategoriSlug] ?? $rules['default'] ?? [];

        // Yayın tipi bazlı kuralı çek
        return $categoryRules[$yayinTipiSlug] ?? [];
    }

    /**
     * 🆕 PHASE 3: Feature Assignment için önerilen özellikler getir
     *
     * Context7 Standard: C7-FEATURE-ASSIGNMENT-SUGGESTIONS-2026-01-05
     *
     * Belirli bir kategori + yayın tipi için ZORUNLU ve ÖNERİLEN özellikleri döner.
     * Admin UI'da danışmanlara rehberlik eder.
     *
     * @param string $kategoriSlug
     * @param string $yayinTipiSlug
     * @return array ['required' => [...], 'suggested' => [...]]
     */
    public function getSuggestedFeaturesForYayinTipi(
        string $kategoriSlug,
        string $yayinTipiSlug
    ): array {
        $suggestions = [
            'arsa-arazi' => [
                'satilik' => [
                    'required' => ['imar-durumu', 'alan-m2', 'tapu-durumu'],
                    'suggested' => ['kaks', 'taks', 'ada-no', 'parsel-no', 'gabari', 'emsal'],
                ],
                'kiralik' => [
                    'required' => ['alan-m2', 'tapu-durumu'],
                    'suggested' => ['imar-durumu', 'kaks', 'taks'],
                ],
            ],
            'yazlik-kiralama' => [
                'gunluk' => [
                    'required' => ['gunluk-fiyat', 'oda-sayisi', 'banyo-sayisi', 'max-misafir'],
                    'suggested' => ['havuz', 'denize-mesafe', 'check-in', 'check-out', 'wifi', 'klima'],
                ],
                'haftalik' => [
                    'required' => ['haftalik-fiyat', 'oda-sayisi', 'banyo-sayisi', 'max-misafir'],
                    'suggested' => ['havuz', 'denize-mesafe', 'min-konaklama', 'depozito'],
                ],
            ],
            'konut' => [
                'satilik' => [
                    'required' => ['alan-m2', 'oda-sayisi', 'banyo-sayisi', 'bina-yasi', 'kat'],
                    'suggested' => ['asansor', 'otopark', 'isitma-tipi', 'balkon-sayisi'],
                ],
                'kiralik' => [
                    'required' => ['aylik-kira', 'oda-sayisi', 'banyo-sayisi', 'depozito', 'kat'],
                    'suggested' => ['aidat', 'asansor', 'esyali', 'otopark'],
                ],
            ],
        ];

        $categoryRules = $suggestions[$kategoriSlug] ?? [];
        $yayinRules = $categoryRules[$yayinTipiSlug] ?? [];

        return [
            'required' => $yayinRules['required'] ?? [],
            'suggested' => $yayinRules['suggested'] ?? [],
        ];
    }

    /**
     * 🆕 SMART FORMS: Yayın tipine göre özellikleri filtrele
     *
     * Context7 Standard: C7-SMART-FORM-FILTERING-2026-01-06
     * Muhakeme: "Satılık" için "Depozito" mantıksız, "Kiralık" için "Tapu" gereksiz
     *
     * Bu metod, feature_assignments tablosundaki visibility_rules JSON kolonunu okuyarak
     * ilgili yayın tipi için görünmez/gizli olan özellikleri filtreler.
     *
     * @param int $kategoriId Kategori ID
     * @param int $yayinTipiId Yayın Tipi ID
     * @param array $baseFeatureCategories İsteğe bağlı base categories (performans için)
     * @return Collection Filtered feature categories with features
     */
    public function getFeaturesByPublicationType(
        int $kategoriId,
        int $yayinTipiId,
        array $baseFeatureCategories = []
    ): Collection {
        // Base categories varsa kullan, yoksa tümünü çek
        if (empty($baseFeatureCategories)) {
            try {
                // Kategori slug'ını al
                $kategori = \App\Models\IlanKategori::find($kategoriId);
                $kategoriSlug = $kategori->slug ?? 'default';

                // Slug boşsa kategori adından slug oluştur
                if (empty($kategoriSlug) || $kategoriSlug === 'default') {
                    $kategoriSlug = strtolower(str_replace(' ', '-', $kategori->name ?? 'default'));
                }

                $baseCategories = $this->getCategoriesForKategori($kategoriSlug, []);
            } catch (\Exception $e) {
                // Hata durumunda default kullan
                $baseCategories = $this->getCategoriesForKategori('default', []);
            }
        } else {
            $baseCategories = collect($baseFeatureCategories);
        }

        // Yayın tipi için gizli feature'ları al
        $hiddenFeatureIds = \App\Models\FeatureAssignment::where('assignable_type', \App\Models\YayinTipiSablonu::class)
            ->where('assignable_id', $yayinTipiId)
            ->where('is_visible', false)
            ->pluck('feature_id')
            ->toArray();

        // Zorunlu feature'ları al
        $requiredFeatureIds = \App\Models\FeatureAssignment::where('assignable_type', \App\Models\YayinTipiSablonu::class)
            ->where('assignable_id', $yayinTipiId)
            ->where('is_required', true)
            ->pluck('feature_id')
            ->toArray();

        // Feature categories'i filtrele
        return $baseCategories->map(function ($category) use ($hiddenFeatureIds, $requiredFeatureIds) {
            // Features relationship'i yüklü değilse yükle
            if (!$category->relationLoaded('features')) {
                $category->load(['features' => function ($q) {
                    $q->where('aktiflik_durumu', true)
                      ->orderBy('display_order', 'asc'); // context7-ignore
                }]);
            }

            // Features'ı filtrele
            $filteredFeatures = $category->features->filter(function ($feature) use ($hiddenFeatureIds) {
                // Gizli feature'ları kaldır
                return !in_array($feature->id, $hiddenFeatureIds);
            })->map(function ($feature) use ($requiredFeatureIds) {
                // Zorunlu mu belirt
                $feature->is_required_for_publication = in_array($feature->id, $requiredFeatureIds);
                return $feature;
            });

            $category->setRelation('features', $filteredFeatures);
            return $category;
        })->filter(function ($category) {
            // Boş kategorileri kaldır
            return $category->features->isNotEmpty();
        });
    }

    /**
     * 🔧 UTILITY: Yayın tipi için visibility summary
     *
     * Debug ve API response için özet bilgi döner.
     *
     * @param int $yayinTipiId
     * @return array
     */
    public function getVisibilitySummary(int $yayinTipiId): array
    {
        // Yayın tipi 0 ise boş array döndür
        if ($yayinTipiId <= 0) {
            return [];
        }

        try {
            $assignments = \App\Models\FeatureAssignment::join('features', 'features.id', '=', 'feature_assignments.feature_id')
                ->where('assignable_type', \App\Models\YayinTipiSablonu::class)
                ->where('assignable_id', $yayinTipiId)
                ->select([
                    'features.id',
                    'features.name',
                    'features.slug',
                    'is_visible',
                    'is_required',
                    'visibility_rules'
                ])
                ->get();
        } catch (\Exception $e) {
            // Hata durumunda boş array döndür
            return [];
        }

        return [
            'yayin_tipi_id' => $yayinTipiId,
            'total_features' => $assignments->count(),
            'visible_count' => $assignments->where('is_visible', true)->count(),
            'hidden_count' => $assignments->where('is_visible', false)->count(),
            'required_count' => $assignments->where('is_required', true)->count(),
            'hidden_features' => $assignments->where('is_visible', false)->pluck('slug')->toArray(),
            'required_features' => $assignments->where('is_required', true)->pluck('slug')->toArray(),
        ];
    }

    /**
     * Get assignment matrix for a category and its related publication types.
     */
    public function getAssignmentMatrix(int $kategoriId, Collection $yayinTipleri, Collection $features): array
    {
        $matrix = [];

        $assignments = \App\Models\FeatureAssignment::whereIn('feature_id', $features->pluck('id'))
            ->where('assignable_type', \App\Models\YayinTipiSablonu::class)
            ->whereIn('assignable_id', $yayinTipleri->pluck('id'))
            ->get();

        foreach ($features as $feature) {
            $matrix[$feature->id] = [];
            foreach ($yayinTipleri as $yayinTipi) {
                $assignment = $assignments->where('feature_id', $feature->id)
                    ->where('assignable_id', $yayinTipi->id)
                    ->first();

                $matrix[$feature->id][$yayinTipi->id] = [
                    'is_visible' => $assignment ? (bool) $assignment->is_visible : true,
                    'is_required' => $assignment ? (bool) $assignment->is_required : false
                ];
            }
        }

        return $matrix;
    }
}

