<?php

namespace App\Http\Requests\Admin\Ilan;

use App\Services\Wizard\DependencyRuleEvaluator;
use App\Services\Wizard\EffectiveListingTypeResolver;
use App\Services\Wizard\EffectiveWizardSchemaResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Store Ilan Request
 *
 * Context7: C7-ILAN-STORE-REQUEST-2025-12-28
 * Validation rules for creating new listings
 */
class StoreIlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Ilan::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $durumValues = \App\Enums\IlanDurumu::values();

        $base = [
            // Basic Information
            'baslik' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'fiyat_gosterim_modu' => 'required|in:exact,starting_from,on_request,hidden',
            'fiyat' => [
                Rule::requiredIf(fn () => $this->input('fiyat_gosterim_modu') === 'exact'),
                'nullable',
                'numeric',
                'min:0',
            ],
            'baslangic_fiyati' => [
                Rule::requiredIf(fn () => $this->input('fiyat_gosterim_modu') === 'starting_from'),
                'nullable',
                'numeric',
                'min:0',
            ],
            'fiyat_notu' => 'nullable|string|max:255',
            'para_birimi' => 'required|string|in:TRY,USD,EUR,GBP',

            // Context7: 3-level category (ana → alt → yayin)
            'ana_kategori_id' => 'required|exists:ilan_kategorileri,id',
            'alt_kategori_id' => 'required|exists:ilan_kategorileri,id',
            'yayin_tipi_id' => 'required|integer|exists:yayin_tipi_sablonlari,id',

            // Owner & Agent
            'ilan_sahibi_id' => 'required|exists:kisiler,id',
            'danisman_id' => 'nullable|exists:users,id',

            // Location
            'il_id' => 'nullable|exists:iller,id',
            'ilce_id' => 'nullable|exists:ilceler,id',
            'mahalle_id' => 'nullable|exists:mahalleler,id',
            'sokak' => 'nullable|string|max:255',
            'cadde' => 'nullable|string|max:255',
            'bulvar' => 'nullable|string|max:255',
            'bina_no' => 'nullable|string|max:20',
            'daire_no' => 'nullable|string|max:20',
            'posta_kodu' => 'nullable|string|max:10',

            // Coordinates & Address
            'lat' => 'nullable|numeric|between:-90,90',    // ✅ SAB: enlem YASAK
            'lng' => 'nullable|numeric|between:-180,180',  // ✅ SAB: boylam YASAK
            'adres' => 'nullable|string|max:500',

            // Status
            'yayin_durumu' => 'required|in:' . implode(',', $durumValues),
            'crm_only' => 'nullable|boolean',

            // GeoJSON & POI Data
            'nearby_distances' => 'nullable|string',
            'boundary_geojson' => 'nullable|string',
            'boundary_area' => 'nullable|numeric|min:0',
            'environment_pois' => 'nullable|string',
            'environment_tags' => 'nullable|array',

            // Property Details (Villa/Daire)
            'oda_sayisi' => 'nullable|integer|min:0|max:20',
            'banyo_sayisi' => 'nullable|integer|min:0|max:10',
            'brut_m2' => 'nullable|numeric|min:0',
            'net_m2' => 'nullable|numeric|min:0',
            'kat' => 'nullable|integer',
            'toplam_kat' => 'nullable|integer',
            'bina_yasi' => 'nullable|integer|min:0',
            'isinma_tipi' => 'nullable|string|in:Doğalgaz,Kombi,Klima,Soba,Merkezi,Yerden Isıtma',
            'site_ozellikleri' => 'nullable|array',
            'site_ozellikleri.*' => 'string|in:Güvenlik,Otopark,Havuz,Spor,Sauna,Oyun Alanı,Asansör',

            // Commercial Property (İşyeri)
            'isyeri_tipi' => 'nullable|string|in:Ofis,Mağaza,Dükkan,Depo,Fabrika,Atölye,Showroom',
            'kira_bilgisi' => 'nullable|string|max:1000',
            'ciro_bilgisi' => 'nullable|numeric|min:0',
            'ruhsat_durumu' => 'nullable|string|in:Var,Yok,Başvuruda',
            'personel_kapasitesi' => 'nullable|integer|min:0',
            'isyeri_cephesi' => 'nullable|integer|min:0',

            // Vacation Rental (Yazlık Kiralama)
            'min_konaklama' => 'nullable|integer|min:1|max:365',
            'sezon_tipi' => 'nullable|string|in:yaz,ara_sezon,kis',
            'max_misafir' => 'nullable|integer|min:1|max:50',
            'temizlik_ucreti' => 'nullable|numeric|min:0',
            'gunluk_fiyat' => 'nullable|numeric|min:0',
            'haftalik_fiyat' => 'nullable|numeric|min:0',
            'aylik_fiyat' => 'nullable|numeric|min:0',
            'sezonluk_fiyat' => 'nullable|numeric|min:0',
            'sezon_baslangic' => 'nullable|date',
            'sezon_bitis' => 'nullable|date|after_or_equal:sezon_baslangic',

            // Amenities
            'havuz' => 'nullable|boolean',
            'havuz_turu' => 'nullable|string|max:100',
            'havuz_boyut' => 'nullable|string|max:50',
            'havuz_derinlik' => 'nullable|string|max:50',
            'elektrik_dahil' => 'nullable|boolean',
            'su_dahil' => 'nullable|boolean',
            'internet_dahil' => 'nullable|boolean',
            'klima_var' => 'nullable|boolean',
            'bahce_var' => 'nullable|boolean',
            'tv_var' => 'nullable|boolean',
            'barbeku_var' => 'nullable|boolean',

            // Land (Arsa)
            'arsa_tipi' => 'nullable|string|max:100',
            'imar_durumu' => 'nullable|string|max:100',
            'ada_no' => 'nullable|string|max:50',
            'parsel_no' => 'nullable|string|max:50',
            'kaks' => 'nullable|numeric|min:0',
            'gabari' => 'nullable|numeric|min:0',
            'taban_alani' => 'nullable|numeric|min:0',

            // NEW VILLA & LAND FEATURES (EtsTur Sync)
            // konum_tipi: DB column removed, form uses this as UI toggle only (site/apartman/mustakil)
            'merkeze_uzaklik' => 'nullable|integer|min:0',
            'denize_uzaklik' => 'nullable|integer|min:0',
            'plaja_uzaklik' => 'nullable|integer|min:0',
            'havuz_var' => 'nullable|boolean',
            'havuz_tipi' => 'nullable|string|in:Özel,Ortak,Infinity,Çocuk',
            'havuz_isitmali' => 'nullable|boolean',
            'bahce_masasi_var' => 'nullable|boolean',
            'sezlong_var' => 'nullable|boolean',
            'manzara_tipleri' => 'nullable|array',
            'deniz_manzarali' => 'nullable|boolean',
            'doga_manzarali' => 'nullable|boolean',
            'dag_manzarali' => 'nullable|boolean',
            'mutfak_tam_donanmli' => 'nullable|boolean',
            'mutfak_bulasik_makinesi' => 'nullable|boolean',
            'mutfak_kahve_makinesi' => 'nullable|boolean',
            'internet_hizi' => 'nullable|integer|min:0',
            'klima_sayisi' => 'nullable|integer|min:0',
            'evcil_hayvan_uygun' => 'nullable|boolean',
            'sigara_icilmez' => 'nullable|boolean',
            'giris_saati' => 'nullable|date_format:H:i',
            'cikis_saati' => 'nullable|date_format:H:i',
            'depozito' => 'nullable|numeric|min:0',

            // Land (Arsa/Arazi Sync)
            'kadastral_yol' => 'nullable|boolean',
            'su_durumu' => 'nullable|string|in:Sondaj,Kanal,Yok',
            'tapu_tipi' => 'nullable|string|in:Müstakil,Hisseli',
            'agac_sayisi' => 'nullable|integer|min:0',
            'agac_yasi' => 'nullable|string|in:Genç,Orta,Asırlık',
            'zeytin_turu' => 'nullable|string|max:100',
            'yillik_rekolte' => 'nullable|integer|min:0',

            // Media
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:10240',

            // Portal IDs
            'sahibinden_id' => 'nullable|string|max:50',
            'emlakjet_id' => 'nullable|string|max:50',
            'hepsiemlak_id' => 'nullable|string|max:50',
            'zingat_id' => 'nullable|string|max:50',
            'hurriyetemlak_id' => 'nullable|string|max:50',

            // ✅ FIX-2 (SAB Sprint 2026-04-15): Pass features array through validation
            // Wizard sends features[slug]=value. Array preserved for Service Layer mapping.
            'features' => 'nullable|array',
            'features.*' => 'nullable',

            // ✅ FIX-3 (SAB Sprint 2026-04-15): Accept adres_detay from wizard
            'adres_detay' => 'nullable|string|max:500',
        ];

        // Schema-driven dependency-aware feature rules
        $kategoriId = (int) ($this->input('alt_kategori_id') ?: $this->input('ana_kategori_id'));
        $yayinTipiIdVal = (int) $this->input('yayin_tipi_id');

        if ($kategoriId && $yayinTipiIdVal) {
            $schemaResolver = app(EffectiveWizardSchemaResolver::class);
            $schema = $schemaResolver->resolve($kategoriId, $yayinTipiIdVal);
            $evaluator = app(DependencyRuleEvaluator::class);
            $form = $this->all();

            foreach ($schema['fields'] as $field) {
                $slug = $field['slug'];

                // Invisible fields are not validated
                if (! $evaluator->isVisible($field, $form)) {
                    continue;
                }

                $fieldRules = [];
                $fieldRules[] = $evaluator->isRequired($field, $form) ? 'required' : 'nullable';

                $fieldRules[] = match ($field['type']) { // context7-ignore
                    'number' => 'numeric',
                    'boolean' => 'boolean',
                    'multiselect' => 'array',
                    default => 'string',
                };

                // Option whitelist for select fields
                if ($field['type'] === 'select' && ! empty($field['options'])) { // context7-ignore
                    $allowed = collect($field['options'])->pluck('value')->toArray();
                    $fieldRules[] = 'in:' . implode(',', $allowed);
                }

                $base[$slug] = $fieldRules;
            }
        }

        return $base;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'baslik.required' => 'İlan başlığı zorunludur.',
            'fiyat.required' => 'Fiyat bilgisi zorunludur.',
            'ana_kategori_id.required' => 'Ana kategori seçimi zorunludur.',
            'alt_kategori_id.required' => 'Alt kategori seçimi zorunludur.',
            'yayin_tipi_id.required' => 'Yayın tipi seçimi zorunludur.',
            'yayin_tipi_id.integer' => 'Geçersiz yayın tipi.',
            'ilan_sahibi_id.required' => 'İlan sahibi seçimi zorunludur.',
        ];
    }

    /**
     * Backend-enforced policy guard: category + yayın tipi combination.
     * EffectiveListingTypeResolver is the SINGLE SOURCE OF TRUTH.
     */
    public function withValidator(Validator $validator): void
    {
        // UPS Policy Guard: validate category + yayın tipi combination
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $mainCategoryId = $this->integer('ana_kategori_id');
            $subCategoryId = $this->filled('alt_kategori_id')
                ? $this->integer('alt_kategori_id')
                : null;
            $listingTypeId = $this->integer('yayin_tipi_id');

            /** @var EffectiveListingTypeResolver $resolver */
            $resolver = app(EffectiveListingTypeResolver::class);

            if (! $resolver->isAllowed(
                mainCategoryId: $mainCategoryId,
                subCategoryId: $subCategoryId,
                yayinTipiId: $listingTypeId
            )) {
                $validator->errors()->add(
                    'yayin_tipi_id',
                    'Seçilen yayın tipi bu kategori için geçerli değil.'
                );
            }
        });

        // Dependency Guard: reject hidden/disabled fields submitted via UI bypass
        $validator->after(function (Validator $validator) {
            $kategoriId = (int) ($this->input('alt_kategori_id') ?: $this->input('ana_kategori_id'));
            $yayinTipiIdVal = (int) $this->input('yayin_tipi_id');

            if (! $kategoriId || ! $yayinTipiIdVal) {
                return;
            }

            $schemaResolver = app(EffectiveWizardSchemaResolver::class);
            $schema = $schemaResolver->resolve($kategoriId, $yayinTipiIdVal);
            $evaluator = app(DependencyRuleEvaluator::class);
            $form = $this->all();

            foreach ($schema['fields'] as $field) {
                $slug = $field['slug'];

                // Hidden field submitted → reject
                if (! $evaluator->isVisible($field, $form) && $this->has($slug)) {
                    $validator->errors()->add($slug, 'Field not allowed in current context.');
                }

                // Disabled field submitted → reject
                if (! $evaluator->isEnabled($field, $form) && $this->has($slug)) {
                    $validator->errors()->add($slug, 'Field disabled.');
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            // ✅ FIX-1 (SAB Sprint 2026-04-04): junction_id → yayin_tipi_id bridge
            // Wizard step-1 form field adı 'junction_id', backend 'yayin_tipi_id' bekliyor.
            // junction_id gönderilirse yayin_tipi_id olarak map et (varsa mevcut değeri koru).
            'yayin_tipi_id' => $this->input('yayin_tipi_id', $this->input('junction_id')),
            'fiyat_gosterim_modu' => $this->input('fiyat_gosterim_modu', $this->input('price_display_mode', 'exact')),
            'fiyat' => $this->input('fiyat', $this->input('price')),
            'baslangic_fiyati' => $this->input('baslangic_fiyati', $this->input('starting_price')),
            'fiyat_notu' => $this->input('fiyat_notu', $this->input('price_note')),
            'para_birimi' => $this->input('para_birimi', $this->input('currency', 'TRY')),
        ]);

        // ✅ FIX-2 (SAB Sprint 2026-04-15): features[] → top-level promotion
        // Wizard sends features[slug]=value, schema validation expects top-level slug keys.
        // Promote each feature to top-level so schema-driven rules validate them.
        if ($this->has('features') && is_array($this->input('features'))) {
            $promotions = [];
            foreach ($this->input('features') as $slug => $value) {
                if (is_string($slug) && !$this->has($slug)) {
                    $promotions[$slug] = $value;
                }
            }
            if (!empty($promotions)) {
                $this->merge($promotions);
            }
        }

        // ✅ FIX-3 (SAB Sprint 2026-04-15): adres_detay → adres bridge
        // Wizard step-4 sends 'adres_detay', backend expects 'adres'.
        if ($this->has('adres_detay') && !$this->filled('adres')) {
            $this->merge(['adres' => $this->input('adres_detay')]);
        }

        // Convert string booleans to actual booleans
        $booleanFields = [
            'crm_only', 'havuz', 'elektrik_dahil', 'su_dahil', 'internet_dahil',
            'klima_var', 'bahce_var', 'tv_var', 'barbeku_var', 'havuz_var',
            'havuz_isitmali', 'bahce_masasi_var', 'sezlong_var', 'deniz_manzarali',
            'doga_manzarali', 'dag_manzarali', 'mutfak_tam_donanmli',
            'mutfak_bulasik_makinesi', 'mutfak_kahve_makinesi', 'evcil_hayvan_uygun',
            'sigara_icilmez', 'kadastral_yol'
        ];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                ]);
            }
        }

        // Context7: Handle polymorphic price field (array -> scalar)
        if ($this->has('fiyat') && is_array($this->input('fiyat'))) {
            $fiyatArray = $this->input('fiyat');
            $scalarPrice = null;

            // Satılık/Kiralık/Günlük fiyatlarını kontrol et
            if (isset($fiyatArray['satilik_fiyat'])) $scalarPrice = $fiyatArray['satilik_fiyat'];
            elseif (isset($fiyatArray['gunluk_fiyat'])) $scalarPrice = $fiyatArray['gunluk_fiyat'];
            elseif (isset($fiyatArray['aylik_kira'])) $scalarPrice = $fiyatArray['aylik_kira'];

            // Para birimini root seviyeye taşı
            if (isset($fiyatArray['para_birimi']) && !$this->has('para_birimi')) {
                $this->merge(['para_birimi' => $fiyatArray['para_birimi']]);
            }

            if ($scalarPrice !== null) {
                $this->merge(['fiyat' => $scalarPrice]);
            }
        }
    }
}
