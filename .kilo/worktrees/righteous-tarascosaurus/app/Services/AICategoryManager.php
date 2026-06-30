<?php

namespace App\Services;

use App\Models\IlanKategori;
use App\Models\KategoriYayinTipiFieldDependency;
use Illuminate\Support\Facades\Cache;

class AICategoryManager
{
    private $aiCore;

    private $aiIntegration;

    public function __construct()
    {
        $this->aiCore = app(AICoreSystem::class);
        $this->aiIntegration = app(AISystemIntegration::class);
    }

    /**
     * AI destekli kategori analizi
     */
    public function analyzeCategory($categorySlug)
    {
        $context = "category_analysis_{$categorySlug}";

        // Mevcut kategori verilerini al
        $categoryData = $this->getCategoryData($categorySlug);

        // AI'den analiz iste
        $analysis = $this->aiCore->testAI($context, [
            'category' => $categorySlug,
            'data' => $categoryData,
        ]);

        return $analysis;
    }

    /**
     * AI destekli kategori önerileri
     */
    public function getCategorySuggestions($categorySlug)
    {
        $context = "category_suggestions_{$categorySlug}";

        // Mevcut kategori özelliklerini al
        $features = $this->getCategoryFeatures($categorySlug);

        // AI'den öneri iste
        $suggestions = $this->aiIntegration->generateSuggestions($context, [
            'category' => $categorySlug,
            'features' => $features,
        ]);

        return $suggestions;
    }

    /**
     * AI destekli hibrit sıralama
     */
    public function generateHibritSiralama($categorySlug)
    {
        $context = "hibrit_siralama_{$categorySlug}";

        // Mevcut özellikleri al
        $features = $this->getCategoryFeatures($categorySlug);

        // AI'den hibrit sıralama iste
        $siralama = $this->aiIntegration->generateHibritSiralama($categorySlug, $features);

        return $siralama;
    }

    /**
     * AI destekli form üretimi
     */
    public function generateSmartForm($categorySlug, $publicationType = null)
    {
        $context = "smart_form_{$categorySlug}";

        // AI'den akıllı form iste
        $form = $this->aiIntegration->generateSmartForm($categorySlug, $publicationType);

        return $form;
    }

    /**
     * AI destekli matrix yönetimi
     */
    public function manageMatrix($categorySlug)
    {
        $context = "matrix_management_{$categorySlug}";

        // Mevcut field'ları al
        $fields = $this->getCategoryFields($categorySlug);

        // AI'den matrix yönetimi iste
        $matrix = $this->aiIntegration->manageMatrix($categorySlug, $fields);

        return $matrix;
    }

    /**
     * Kategori verilerini getir
     */
    private function getCategoryData($categorySlug)
    {
        return Cache::remember("category_data_{$categorySlug}", 3600, function () use ($categorySlug) {
            $category = IlanKategori::where('slug', $categorySlug)->first();

            if (! $category) {
                return null;
            }

            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'aktiflik_durumu' => $category->aktiflik_durumu,
                'display_order' => $category->display_order ?? 0,
                'features_count' => $category->features()->count(),
                'ilan_count' => $category->ilanlar()->count(),
                'parent' => $category->parent ? $category->parent->name : null,
                'children_count' => $category->children()->count(),
            ];
        });
    }

    /**
     * Kategori özelliklerini getir
     */
    private function getCategoryFeatures($categorySlug)
    {
        return Cache::remember("category_features_{$categorySlug}", 3600, function () use ($categorySlug) {
            $category = IlanKategori::where('slug', $categorySlug)->first();

            if (! $category) {
                return [];
            }

            return $category->features()->get()->map(function ($feature) {
                return [
                    'id' => $feature->id,
                    'name' => $feature->name,
                    'slug' => $feature->slug,
                    'type' => $feature->veri_tipi, // context7-ignore
                    'required' => $feature->zorunlu,
                    'searchable' => $feature->arama_filtresi,
                    'show_in_card' => $feature->ilan_kartinda_goster,
                    'usage_count' => $feature->kullanim_sayisi ?? 0,
                ];
            })->toArray();
        });
    }

    /**
     * Kategori field'larını getir
     */
    private function getCategoryFields($categorySlug)
    {
        return Cache::remember("category_fields_{$categorySlug}", 3600, function () use ($categorySlug) {
            return KategoriYayinTipiFieldDependency::where('kategori_slug', $categorySlug)
                ->get()
                ->map(function ($field) {
                    return [
                        'field_slug' => $field->field_slug,
                        'field_name' => $field->field_name,
                        'field_type' => $field->field_type,
                        'ai_suggestion' => $field->ai_suggestion,
                        'ai_auto_fill' => $field->ai_auto_fill,
                        'required' => $field->required,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * AI'yi kategori hakkında öğret
     */
    public function teachAICategory($categorySlug, $examples)
    {
        $context = "category_learning_{$categorySlug}";

        foreach ($examples as $example) {
            $this->aiCore->teachAI($context, $example['task'], $example['expected_output']);
        }

        return true;
    }

    /**
     * Kategori AI başarı oranını güncelle
     */
    public function updateCategoryAISuccess($categorySlug, $taskType, $isSuccess)
    {
        $context = "category_{$categorySlug}";

        return $this->aiCore->updateSuccessRate($context, $taskType, $isSuccess);
    }

    /**
     * Tüm kategoriler için AI analizi
     */
    public function analyzeAllCategories()
    {
        $categories = ['konut', 'arsa', 'yazlik', 'isyeri'];
        $results = [];

        foreach ($categories as $category) {
            $results[$category] = [
                'analysis' => $this->analyzeCategory($category),
                'suggestions' => $this->getCategorySuggestions($category),
                'hibrit_siralama' => $this->generateHibritSiralama($category),
            ];
        }

        return $results;
    }
}
