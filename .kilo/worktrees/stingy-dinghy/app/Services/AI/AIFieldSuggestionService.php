<?php

namespace App\Services\AI;

use App\Services\Cache\CacheHelper;
use App\Models\KategoriYayinTipiFieldDependency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AIFieldSuggestionService
{
    protected AIPromptBuilder $promptBuilder;

    public function __construct(AIPromptBuilder $promptBuilder)
    {
        $this->promptBuilder = $promptBuilder;
    }

    /**
     * Konut özellikleri hibrit sıralama sistemi
     *
     * @param  string  $kategoriSlug  Kategori (konut, arsa, yazlik)
     * @param  array  $context  Ek bağlam
     * @return array Hibrit sıralama verileri
     */
    public function getKonutHibritSiralama(string $kategori_slug = 'konut', array $context = []): array
    {
        return CacheHelper::remember(
            'ai',
            'konut_hibrit_siralama',
            'medium', // 1 hour
            function () {
                if (!class_exists('App\\Models\\KonutOzellikHibritSiralama')) {
                    return [];
                }

                $modelClass = 'App\\Models\\KonutOzellikHibritSiralama';
                return $modelClass::active()
                    ->ordered() // context7-ignore
                    ->get()
                    ->toArray();
            },
            ['kategori' => $kategori_slug]
        );
    }

    /**
     * Hibrit skor hesaplama
     *
     * @param  int  $kullanimSikligi  Kullanım sıklığı
     * @param  float  $aiOneri  AI öneri yüzdesi
     * @param  float  $kullaniciTercih  Kullanıcı tercih yüzdesi
     * @return float Hibrit skor
     */
    public function calculateHibritSkor(int $kullanimSikligi, float $aiOneri, float $kullaniciTercih): float
    {
        $normalizedKullanim = min(100, ($kullanimSikligi / 6)); // 600 kullanım = 100 puan
        $hibritSkor = ($normalizedKullanim * 0.4) + ($aiOneri * 0.3) + ($kullaniciTercih * 0.3);
        return round($hibritSkor, 2);
    }

    /**
     * Önem seviyesi belirleme
     *
     * @param  float  $hibritSkor  Hibrit skor
     * @return string Önem seviyesi
     */
    public function determineOnemSeviyesi(float $hibritSkor): string
    {
        if ($hibritSkor >= 80) {
            return 'cok_onemli';
        }
        if ($hibritSkor >= 60) {
            return 'onemli';
        }
        if ($hibritSkor >= 40) {
            return 'orta_onemli';
        }

        return 'dusuk_onemli';
    }

    /**
     * AI ile özellik önerisi
     *
     * @param  string  $kategoriSlug  Kategori
     * @param  array  $mevcutOzellikler  Mevcut özellikler
     * @return array AI önerileri
     */
    public function suggestKonutOzellikleri(string $kategori_slug = 'konut', array $mevcutOzellikler = []): array
    {
        $hibritSiralama = $this->getKonutHibritSiralama($kategori_slug);

        // Mevcut olmayan özellikleri filtrele
        $oneriOzellikleri = array_filter($hibritSiralama, function ($ozellik) use ($mevcutOzellikler) {
            $ozellikSlug = is_object($ozellik) ? $ozellik->ozellik_slug : ($ozellik['ozellik_slug'] ?? null);
            return !in_array($ozellikSlug, $mevcutOzellikler);
        });

        // Hibrit skoruna göre sırala
        usort($oneriOzellikleri, function ($a, $b) {
            $skorA = is_object($a) ? $a->hibrit_skor : ($a['hibrit_skor'] ?? 0);
            $skorB = is_object($b) ? $b->hibrit_skor : ($b['hibrit_skor'] ?? 0);
            return $skorB <=> $skorA;
        });

        return array_slice($oneriOzellikleri, 0, 5); // İlk 5 öneri
    }

    /**
     * AI ile tek field için öneri
     */
    public function suggestFieldValue(
        string $kategori_slug,
        string $yayin_tipi,
        string $field_slug,
        array $context,
        callable $requestMaker
    ): mixed {
        $cacheKey = "ai_field_suggest_{$kategori_slug}_{$yayin_tipi}_{$field_slug}_" . md5(json_encode($context));

        return Cache::remember($cacheKey, 3600, function () use ($kategori_slug, $yayin_tipi, $field_slug, $context, $requestMaker) {
            $prompt = $this->promptBuilder->buildFieldSuggestionPrompt($kategori_slug, $yayin_tipi, $field_slug, $context);

            return $requestMaker('suggest_field', $prompt, compact('kategori_slug', 'yayin_tipi', 'field_slug', 'context'));
        });
    }

    /**
     * AI ile tüm field'ları otomatik doldur
     */
    public function autoFillFields(
        string $kategori_slug,
        string $yayin_tipi,
        array $existingData,
        callable $requestMaker
    ): array {
        $registry = app(\App\Contracts\Settings\ConfigurationRegistryInterface::class);
        $aiEnabled = $registry->get('ai_auto_fill', false);
        
        if (!$aiEnabled) {
            return [];
        }

        $aiFields = KategoriYayinTipiFieldDependency::where('kategori_slug', $kategori_slug)
            ->where('yayin_tipi', $yayin_tipi)
            ->where('ai_auto_fill', 1)
            ->where('aktiflik_durumu', 1) // ✅ SAB: durumu canonical
            ->get();

        $suggestions = [];

        foreach ($aiFields as $field) {
            try {
                $value = $this->suggestFieldValue($kategori_slug, $yayin_tipi, $field->field_slug, $existingData, $requestMaker);
                $suggestions[$field->field_slug] = $value;
            } catch (\Exception $e) {
                Log::warning("AI auto-fill failed for {$field->field_slug}: " . $e->getMessage());
            }
        }

        return $suggestions;
    }

    /**
     * AI ile akıllı hesaplama
     */
    public function smartCalculate(
        string $source_field,
        mixed $source_value,
        string $target_field,
        array $context,
        callable $requestMaker
    ): mixed {
        $prompt = "
Hesaplama Görevi:
- Kaynak Field: {$source_field} = {$source_value}
- Hedef Field: {$target_field}
- Context: " . json_encode($context) . '

Türkiye emlak sektörü standartlarına göre hesapla.

Örnekler:
- Günlük fiyat 500 TL → Haftalık fiyat = 500 × 7 × 0.85 (haftalık indirim) = 2,975 TL
- Günlük fiyat 500 TL → Aylık fiyat = 500 × 30 × 0.70 (aylık indirim) = 10,500 TL
- Yaz sezonu 500 TL → Ara sezon = 500 × 0.70 (-%30) = 350 TL
- Yaz sezonu 500 TL → Kış sezonu = 500 × 0.50 (-%50) = 250 TL
- Satış fiyatı 1,000,000 TL + Alan 100 m² → m² fiyatı = 10,000 TL/m²

Sadece hesaplanan sayısal değeri döndür (birim olmadan).
';

        try {
            $result = $requestMaker('calculate', $prompt, compact('source_field', 'source_value', 'target_field', 'context'));

            return $result['value'] ?? null;
        } catch (\Exception $e) {
            Log::error('SMART_CALCULATE_ERROR', [
                'exception' => $e->getMessage(),
                'trace_id' => request()?->header('X-Trace-Id'),
            ]);
            Log::warning('AI smart calculate failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * AI-Powered Smart Field Generation
     */
    public function suggestFieldsForCategory(
        string $kategori_slug,
        ?string $yayin_tipi,
        array $context,
        callable $requestMaker
    ): mixed {
        $cacheKey = "ai_suggest_fields_{$kategori_slug}_{$yayin_tipi}";

        return Cache::remember($cacheKey, 3600, function () use ($kategori_slug, $yayin_tipi, $context, $requestMaker) {
            $prompt = "Kategori: {$kategori_slug}, Yayın Tipi: {$yayin_tipi}\nContext: " . json_encode($context) . "\nBu ilan için uygun özellikleri (field listesi) öner.";

            return $requestMaker('suggest-fields', $prompt, $context);
        });
    }

    /**
     * AI-Powered Smart Form Generation
     */
    public function generateSmartForm(
        string $kategori_slug,
        string $yayin_tipi,
        array $context,
        callable $requestMaker
    ): mixed {
        $cacheKey = "ai_smart_form_{$kategori_slug}_{$yayin_tipi}";

        return Cache::remember($cacheKey, 3600, function () use ($kategori_slug, $yayin_tipi, $context, $requestMaker) {
            $prompt = $this->promptBuilder->buildSmartFormPrompt($kategori_slug, $yayin_tipi, $context);

            return $requestMaker('generate-form', $prompt, $context);
        });
    }
}
