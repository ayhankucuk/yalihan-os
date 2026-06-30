<?php

namespace App\Services;

use App\Models\Ozellik;
use App\Models\OzellikKategori;
use Illuminate\Support\Facades\Cache;

/**
 * @deprecated Use App\Services\AI\SmartFieldGenerationService instead (SAB-sealed, governance-compliant)
 * This class exists only for backward compatibility and will be removed in future versions.
 */
class SmartFieldGenerationService
{
    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Kategori bazlı akıllı field önerileri
     */
    public function getSmartFieldsForCategory($kategoriSlug, $yayinTipi = null)
    {
        $cacheKey = "smart_fields_{$kategoriSlug}_{$yayinTipi}";

        return Cache::remember($cacheKey, 3600, function () use ($kategoriSlug, $yayinTipi) {
            // Mevcut özellikleri al
            $existingFields = $this->getExistingFields($kategoriSlug, $yayinTipi);

            // AI ile öneriler al
            $aiSuggestions = $this->aiService->suggestFieldsForCategory($kategoriSlug, $yayinTipi);

            // Mevcut ve AI önerilerini birleştir
            return $this->mergeFieldSuggestions($existingFields, $aiSuggestions);
        });
    }

    /**
     * Mevcut özellikleri getir
     */
    private function getExistingFields($kategoriSlug, $yayinTipi)
    {
        $query = Ozellik::join('ozellik_kategorileri', 'ozellikler.kategori_id', '=', 'ozellik_kategorileri.id')
            ->where('ozellik_kategorileri.slug', $kategoriSlug)
            ->where('ozellikler.aktif_mi', 1);

        if ($yayinTipi) {
            // Yayın tipi bazlı filtreleme (gelecekte implement edilecek)
        }

        return $query->select([
            'ozellikler.id',
            'ozellikler.name',
            'ozellikler.slug',
            'ozellikler.veri_tipi',
            'ozellikler.veri_secenekleri',
            'ozellikler.birim',
            'ozellikler.zorunlu',
            'ozellikler.arama_filtresi',
            'ozellikler.ilan_kartinda_goster',
            'ozellik_kategorileri.name as kategori_name',
            'ozellik_kategorileri.slug as kategori_slug',
        ])->get();
    }

    /**
     * Mevcut ve AI önerilerini birleştir
     */
    private function mergeFieldSuggestions($existingFields, $aiSuggestions)
    {
        $result = [
            'existing_fields' => $existingFields,
            'ai_suggestions' => $aiSuggestions,
            'smart_recommendations' => $this->generateSmartRecommendations($existingFields, $aiSuggestions),
        ];

        return $result;
    }

    /**
     * Akıllı öneriler oluştur
     */
    private function generateSmartRecommendations($existingFields, $aiSuggestions)
    {
        $recommendations = [];

        // Mevcut field'ları analiz et
        foreach ($existingFields as $field) {
            $recommendations[] = [
                'type' => 'existing', // context7-ignore
                'field' => $field,
                'priority' => $this->calculateFieldPriority($field),
                'ai_enhancement' => $this->getAIEnhancement($field),
            ];
        }

        // AI önerilerini ekle
        if (isset($aiSuggestions['suggestions'])) {
            foreach ($aiSuggestions['suggestions'] as $suggestion) {
                $recommendations[] = [
                    'type' => 'ai_suggestion', // context7-ignore
                    'field' => $suggestion,
                    'priority' => $suggestion['importance'] ?? 3,
                    'ai_enhancement' => 'new',
                ];
            }
        }

        // Öncelik sırasına göre sırala
        usort($recommendations, function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        return $recommendations;
    }

    /**
     * Field önceliği hesapla
     */
    private function calculateFieldPriority($field)
    {
        $priority = 1;

        // Zorunlu field'lar yüksek öncelik
        if ($field->zorunlu) {
            $priority += 3;
        }

        // Arama filtresi yüksek öncelik
        if ($field->arama_filtresi) {
            $priority += 2;
        }

        // İlan kartında göster yüksek öncelik
        if ($field->ilan_kartinda_goster) {
            $priority += 1;
        }

        return $priority;
    }

    /**
     * AI geliştirme önerileri
     */
    private function getAIEnhancement($field)
    {
        $enhancements = [];

        // Select field'lar için AI önerileri
        if ($field->veri_tipi === 'select' && $field->veri_secenekleri) {
            $enhancements[] = 'AI ile otomatik seçenek önerisi';
        }

        // Text field'lar için AI önerileri
        if ($field->veri_tipi === 'text' || $field->veri_tipi === 'textarea') {
            $enhancements[] = 'AI ile otomatik içerik üretimi';
        }

        // Number field'lar için AI önerileri
        if ($field->veri_tipi === 'number') {
            $enhancements[] = 'AI ile akıllı değer önerisi';
        }

        return $enhancements;
    }

    /**
     * Kategori bazlı özellik matrisi oluştur
     */
    public function generateCategoryMatrix($kategoriSlug)
    {
        $cacheKey = "category_matrix_{$kategoriSlug}";

        return Cache::remember($cacheKey, 3600, function () use ($kategoriSlug) {
            $matrix = [];

            // Tüm özellik kategorilerini al
            $ozellikKategorileri = OzellikKategori::where('aktiflik_durumu', 1)
                ->orderBy('display_order') // context7-ignore
                ->get();

            foreach ($ozellikKategorileri as $kategori) {
                $ozellikler = Ozellik::where('kategori_id', $kategori->id)
                    ->where('aktiflik_durumu', 1)
                    ->orderBy('sira') // context7-ignore
                    ->get();

                $matrix[$kategori->slug] = [
                    'kategori' => $kategori,
                    'ozellikler' => $ozellikler,
                    'ai_suggestions' => $this->getAISuggestionsForCategory($kategori->slug, $kategoriSlug),
                ];
            }

            return $matrix;
        });
    }

    /**
     * Kategori için AI önerileri
     */
    private function getAISuggestionsForCategory($ozellikKategoriSlug, $kategoriSlug)
    {
        $context = [
            'ozellik_kategori' => $ozellikKategoriSlug,
            'emlak_kategori' => $kategoriSlug,
        ];

        return $this->aiService->suggestFieldsForCategory($kategoriSlug, null, $context);
    }

    /**
     * Akıllı form oluştur
     */
    public function generateSmartForm($kategoriSlug, $yayinTipi = null)
    {
        $cacheKey = "smart_form_{$kategoriSlug}_{$yayinTipi}";

        return Cache::remember($cacheKey, 3600, function () use ($kategoriSlug, $yayinTipi) {
            // Mevcut field'ları al
            $existingFields = $this->getExistingFields($kategoriSlug, $yayinTipi);

            // AI ile form önerisi al
            $aiForm = $this->aiService->generateSmartForm($kategoriSlug, $yayinTipi);

            // Akıllı form oluştur
            return $this->buildSmartForm($existingFields, $aiForm);
        });
    }

    /**
     * Akıllı form oluştur
     */
    private function buildSmartForm($existingFields, $aiForm)
    {
        $form = [
            'sections' => [],
            'ai_enhancements' => [],
            'smart_features' => [],
        ];

        // Kategorilere göre grupla
        $groupedFields = $existingFields->groupBy('kategori_slug');

        foreach ($groupedFields as $kategoriSlug => $fields) {
            $form['sections'][$kategoriSlug] = [
                'title' => $fields->first()->kategori_name,
                'fields' => $fields->map(function ($field) {
                    return [
                        'id' => $field->id,
                        'name' => $field->name,
                        'slug' => $field->slug,
                        'type' => $field->veri_tipi, // context7-ignore
                        'options' => $field->veri_secenekleri ? json_decode($field->veri_secenekleri, true) : null,
                        'unit' => $field->birim,
                        'required' => $field->zorunlu,
                        'searchable' => $field->arama_filtresi,
                        'show_in_card' => $field->ilan_kartinda_goster,
                        'ai_enhancements' => $this->getAIEnhancement($field),
                    ];
                })->toArray(),
            ];
        }

        // AI geliştirmeleri ekle
        $form['ai_enhancements'] = [
            'auto_completion' => true,
            'smart_suggestions' => true,
            'intelligent_validation' => true,
            'context_aware_fields' => true,
        ];

        // Akıllı özellikler
        $form['smart_features'] = [
            'dynamic_required_fields' => true,
            'conditional_display' => true,
            'ai_powered_validation' => true,
            'smart_defaults' => true,
        ];

        return $form;
    }
}
