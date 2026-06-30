<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;

/**
 * Category-Specific Field Validator Service
 *
 * Context7 Standard: C7-CATEGORY-VALIDATION-2025-11-20
 *
 * Provides dynamic validation rules based on property category (Arsa/Konut/Kiralık)
 */
class CategoryFieldValidator
{
    /**
     * Get validation rules for a specific category
     *
     * @param  string|null  $kategoriSlug  Category slug (arsa, konut, vb.)
     * @param  string|null  $yayinTipi  Publication type slug (kiralik, satilik)
     * @return array Validation rules
     */
    public function getRules(?string $kategoriSlug = null, ?string $yayinTipi = null): array
    {
        $rules = [];

        // Base rules (always applied)
        $rules['baslik'] = 'required|string|max:255';
        $rules['fiyat'] = 'nullable|numeric|min:0';
        $rules['para_birimi'] = 'required|in:TRY,USD,EUR';

        // Category-specific rules
        if ($kategoriSlug) {
            $kategoriSlug = strtolower($kategoriSlug);

            switch ($kategoriSlug) {
                case 'arsa':
                case 'land':
                    $rules = array_merge($rules, $this->getArsaRules());
                    break;

                case 'konut':
                case 'residential':
                case 'daire':
                case 'villa':
                    $rules = array_merge($rules, $this->getKonutRules());
                    break;

                case 'isyeri':
                case 'commercial':
                    $rules = array_merge($rules, $this->getIsyeriRules());
                    break;

                case 'yazlık':
                case 'yazlik':
                case 'rental':
                case 'vacation':
                case 'holiday':
                    $rules = array_merge($rules, $this->getYazlikKiralamaRules());
                    break;
            }
        }

        // Publication type specific rules
        if ($yayinTipi) {
            $yayinTipi = strtolower($yayinTipi);

            if ($yayinTipi === 'kiralik' || $yayinTipi === 'rental') {
                $rules = array_merge($rules, $this->getKiralikRules());
            }
        }

        // Feature-specific rules (dynamic)
        $rules = array_merge($rules, $this->getFeatureRules($kategoriSlug, $yayinTipi));

        return $rules;
    }

    /**
     * Get Arsa (Land) specific validation rules
     *
     * Context7: JSON-based validation rules
     * Source: docs/ai/GEMINI_COMPLETE_SYSTEM_DATA.json
     */
    protected function getArsaRules(): array
    {
        // ✅ Yeni Sistem: Önce Feature options kullan (Wizard ile tam uyumlu),
        // fallback olarak ConfigOptionHelper ve son çare olarak hard-coded değerler
        $imarStatusuValues = [];

        // 1. Feature options (primary source)
        $imarStatusuFeature = \App\Models\Feature::where('slug', 'imar_durumu')
            ->where('aktiflik_durumu', 1)
            ->first();

        if ($imarStatusuFeature && is_array($imarStatusuFeature->options) && ! empty($imarStatusuFeature->options)) {
            foreach ($imarStatusuFeature->options as $key => $option) {
                if (is_array($option) && isset($option['label'])) {
                    $imarStatusuValues[] = $option['label'];
                } elseif (is_string($option)) {
                    $imarStatusuValues[] = $option;
                } else {
                    $imarStatusuValues[] = $key;
                }
            }
        }

        // 2. Fallback: Feature system via ConfigOptionHelper
        if (empty($imarStatusuValues)) {
            // ✅ MIGRATED: Using Feature system instead of config file
            $imarStatusuOptions = \App\Helpers\ConfigOptionHelper::get(
                'imar_durumu',
                null,
                null,
                [] // Removed config('yali_options.imar_durumu') - now using Feature system
            );

            foreach ($imarStatusuOptions as $key => $option) {
                if (is_array($option) && isset($option['label'])) {
                    $imarStatusuValues[] = $option['label'];
                } elseif (is_string($option)) {
                    $imarStatusuValues[] = $option;
                } else {
                    $imarStatusuValues[] = $key;
                }
            }
        }

        // 3. Son fallback: hard-coded defaults (güvenlik için)
        if (empty($imarStatusuValues)) {
            $imarStatusuValues = ['İmarlı', 'İmarsız', 'Tarla', 'Konut İmarlı', 'Ticari İmarlı'];
        }

        return [
            // Context7: satis_fiyati zorunlu (Arsa × Satılık)
            'satis_fiyati' => 'required|numeric|min:0',
            'fiyat' => 'required|numeric|min:0', // Fallback for general price field

            // Arsa özel field'ları
            'ada_no' => 'nullable|string|max:50',
            'parsel_no' => 'nullable|string|max:50',

            // İmar statusu - Config'den çekilen seçenekler
            'imar_durumu' => 'nullable|string|in:' . implode(',', $imarStatusuValues),

            // Sayısal alanlar
            'kaks' => 'nullable|numeric|min:0|max:10',
            'taks' => 'nullable|numeric|min:0|max:1',
            'gabari' => 'nullable|numeric|min:0',
            'alan_m2' => 'nullable|numeric|min:0',

            // Legacy feature fields (backward compatibility)
            'features.imar_durumu' => 'nullable|string|in:' . implode(',', $imarStatusuValues),
            'features.ada-no' => 'nullable|string|max:50',
            'features.parsel-no' => 'nullable|string|max:50',
            'features.kaks' => 'nullable|numeric|min:0|max:10',
            'features.taks' => 'nullable|numeric|min:0|max:1',
            'features.gabari' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get Konut (Residential) specific validation rules
     *
     * Context7: C7-KONUT-VALIDATION-2025-11-30
     * Enhanced validation with Net/Brüt m² consistency check
     */
    protected function getKonutRules(): array
    {
        // ✅ MIGRATED: ConfigOptionHelper now uses Feature system (no config fallback)
        $odaSayisiOptions = \App\Helpers\ConfigOptionHelper::get('oda_sayisi_options', null, null, []);
        $odaSayisiValues = [];

        foreach ($odaSayisiOptions as $option) {
            if (is_array($option) && isset($option['value'])) {
                $odaSayisiValues[] = $option['value'];
            } elseif (is_string($option)) {
                $odaSayisiValues[] = $option;
            }
        }

        // Eğer config boşsa default değerler
        if (empty($odaSayisiValues)) {
            $odaSayisiValues = ['1+0', '1+1', '2+1', '3+1', '4+1', '5+1'];
        }

        return [
            'features.oda-sayisi' => 'required|string|in:' . implode(',', $odaSayisiValues),
            'features.brut-metrekare' => 'required|numeric|min:10|max:10000',
            'features.net-metrekare' => 'required|numeric|min:10|max:10000',
            'features.banyo-sayisi' => 'nullable|integer|min:0|max:10',
            'features.bulundugu-kat' => 'nullable|string',
            'features.kat-sayisi' => 'nullable|integer|min:1|max:100',
            'features.bina-yasi' => 'required|numeric',
            'features.isinma-tipi' => 'required|string',
        ];
    }

    /**
     * Validate Konut specific custom rules
     *
     * Custom validation: Net m² cannot be greater than Brüt m²
     *
     * @param  array  $data  Request data
     * @return \Illuminate\Validation\Validator
     */
    public function validateKonut(array $data): \Illuminate\Validation\Validator
    {
        $rules = $this->getKonutRules();

        $validator = Validator::make($data, $rules);

        // Custom validation: Net m² > Brüt m² kontrolü
        $validator->after(function ($validator) use ($data) {
            $netM2 = $data['features']['net-metrekare'] ?? $data['net_m2'] ?? null;
            $brutM2 = $data['features']['brut-metrekare'] ?? $data['brut_m2'] ?? null;

            if ($netM2 && $brutM2 && (float) $netM2 > (float) $brutM2) {
                $validator->errors()->add(
                    'net_metrekare',
                    'Net metrekare, Brüt metrekareden büyük olamaz!'
                );
            }
        });

        return $validator;
    }

    /**
     * Get İşyeri (Commercial) specific validation rules
     */
    protected function getIsyeriRules(): array
    {
        return [
            'features.brut-metrekare' => 'required|numeric|min:10|max:50000',
            'features.net-metrekare' => 'nullable|numeric|min:10|max:50000',
            'features.kat-sayisi' => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * Get Kiralık (Rental) specific validation rules
     */
    protected function getKiralikRules(): array
    {
        return [
            'features.depozito' => 'nullable|numeric|min:0',
            'features.aidat' => 'nullable|numeric|min:0',
            'features.esyali-mi' => 'nullable|string|in:Eşyalı,Eşyasız,Yarı Eşyalı',
            'features.kira-suresi' => 'nullable|string|in:Günlük,Haftalık,Aylık,6 Ay,1 Yıl,2 Yıl,Belirsiz',
        ];
    }

    /**
     * Get Yazlık Kiralama (Vacation Rental) specific validation rules
     */
    protected function getYazlikKiralamaRules(): array
    {
        return [
            'gunluk_fiyat' => 'required|numeric|min:0',
            'haftalik_fiyat' => 'nullable|numeric|min:0',
            'aylik_fiyat' => 'nullable|numeric|min:0',
            'temizlik_ucreti' => 'nullable|numeric|min:0',
            'min_konaklama' => 'required|integer|min:1',
            'max_misafir' => 'nullable|integer|min:1|max:50',
            'sezon_baslangic' => 'nullable|date',
            'sezon_bitis' => 'nullable|date|after_or_equal:sezon_baslangic',
            'havuz' => 'nullable|boolean',
            'elektrik_dahil' => 'nullable|boolean',
            'su_dahil' => 'nullable|boolean',
            'wifi' => 'nullable|boolean',
        ];
    }

    /**
     * Get dynamic feature validation rules
     */
    protected function getFeatureRules(?string $kategoriSlug = null, ?string $yayinTipi = null): array
    {
        $rules = [];

        // Feature rules are dynamically loaded from database
        // This method can be extended to load rules from Feature model
        // For now, we return empty array as base rules are already defined above

        return $rules;
    }

    /**
     * Validate request data with category-specific rules
     *
     * @param  array  $data  Request data
     * @param  string|null  $kategoriSlug  Category slug
     * @param  string|null  $yayinTipi  Publication type slug
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validate(array $data, ?string $kategoriSlug = null, ?string $yayinTipi = null)
    {
        $rules = $this->getRules($kategoriSlug, $yayinTipi);

        return Validator::make($data, $rules);
    }

    /**
     * Get validation messages
     */
    public function getMessages(): array
    {
        return [
            'features.imar_durumu.required' => 'İmar durumu seçimi zorunludur.',
            'features.imar_durumu.in' => 'Geçersiz imar durumu seçimi.',
            'features.oda-sayisi.required' => 'Oda sayısı seçimi zorunludur.',
            'features.oda-sayisi.in' => 'Geçersiz oda sayısı seçimi.',
            'features.brut-metrekare.required' => 'Brüt metrekare bilgisi zorunludur.',
            'features.brut-metrekare.numeric' => 'Brüt metrekare sayısal bir değer olmalıdır.',
            'features.brut-metrekare.min' => 'Brüt metrekare en az :min m² olmalıdır.',
            'features.brut-metrekare.max' => 'Brüt metrekare en fazla :max m² olabilir.',
            'features.kaks.numeric' => 'KAKS sayısal bir değer olmalıdır.',
            'features.kaks.max' => 'KAKS en fazla :max olabilir.',
            'features.taks.numeric' => 'TAKS sayısal bir değer olmalıdır.',
            'features.taks.max' => 'TAKS en fazla :max olabilir.',
        ];
    }
}
