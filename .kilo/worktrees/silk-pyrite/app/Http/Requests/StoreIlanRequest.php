<?php

namespace App\Http\Requests;

use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\CategoryFieldValidator;
use App\Services\Wizard\DependencyRuleEvaluator;
use App\Services\Wizard\EffectiveListingTypeResolver;
use App\Services\Wizard\EffectiveWizardSchemaResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreIlanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $warnings = [];
        $turkishToEnglish = [
            'min_konaklama'     => 'minimum_stay',
            'check_in_saati'    => 'check_in_time',
            'check_out_saati'   => 'check_out_time',
            'max_misafir'       => 'max_guests',
            'temizlik_ucreti'   => 'cleaning_fee',
            'iptal_politikasi'  => 'cancellation_policy',
        ];

        foreach ($turkishToEnglish as $tr => $en) {
            if ($this->has($tr) && !$this->has($en)) {
                $warnings[] = "Deprecated Turkish field '{$tr}' used. Please send '{$en}'.";
                Log::warning('Context7: Turkish field detected in StoreIlanRequest', ['field' => $tr, 'preferred' => $en]);
            }
        }

        $this->merge([
            'fiyat_gosterim_modu' => $this->input('fiyat_gosterim_modu', $this->input('price_display_mode', 'exact')),
            'fiyat' => $this->input('fiyat', $this->input('price')),
            'baslangic_fiyati' => $this->input('baslangic_fiyati', $this->input('starting_price')),
            'fiyat_notu' => $this->input('fiyat_notu', $this->input('price_note')),
            'para_birimi' => $this->input('para_birimi', $this->input('currency', 'TRY')),
            'minimum_stay' => $this->input('minimum_stay', $this->input('min_konaklama')),
            'maximum_stay' => $this->input('maximum_stay'),
            'check_in_time' => $this->input('check_in_time', $this->input('check_in_saati')),
            'check_out_time' => $this->input('check_out_time', $this->input('check_out_saati')),
            'max_guests' => $this->input('max_guests', $this->input('max_misafir')),
            'base_guest_count' => $this->input('base_guest_count'),
            'extra_guest_fee' => $this->input('extra_guest_fee'),
            'cleaning_fee' => $this->input('cleaning_fee', $this->input('temizlik_ucreti')),
            'security_deposit' => $this->input('security_deposit'),
            'booking_type' => $this->input('booking_type'),
            'cancellation_policy' => $this->input('cancellation_policy', $this->input('iptal_politikasi')),
            '_context7_warnings' => $warnings,
        ]);
    }

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $kategoriSlug = null;
        $yayinTipiSlug = null;
        $anaId = $this->input('ana_kategori_id');
        $yayinId = $this->input('yayin_tipi_id');
        if ($anaId) {
            $ana = IlanKategori::find($anaId);
            $kategoriSlug = $ana ? strtolower($ana->slug ?? '') : null;
        }
        if ($yayinId) {
            $yayin = YayinTipiSablonu::find($yayinId);
            $yayinTipiSlug = $yayin ? strtolower($yayin->slug ?? '') : null;
        }

        $statusValues = \App\Enums\IlanDurumu::values();
        $base = [
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
            'ana_kategori_id' => 'required|exists:ilan_kategorileri,id',
            'alt_kategori_id' => 'required|exists:ilan_kategorileri,id',
            'yayin_tipi_id' => 'required|integer|exists:yayin_tipi_sablonlari,id',
            'ilan_sahibi_id' => 'required|exists:kisiler,id',
            'danisman_id' => 'nullable|exists:users,id',
            'il_id' => 'nullable|exists:iller,id',
            'ilce_id' => 'nullable|exists:ilceler,id',
            'mahalle_id' => 'nullable|exists:mahalleler,id',
            'yayin_durumu' => 'required|string|in:'.implode(',', $statusValues),
            'sokak' => 'nullable|string|max:255',
            'cadde' => 'nullable|string|max:255',
            'bulvar' => 'nullable|string|max:255',
            'bina_no' => 'nullable|string|max:20',
            'daire_no' => 'nullable|string|max:20',
            'posta_kodu' => 'nullable|string|max:10',
            'nearby_distances' => 'nullable|string',
            'boundary_geojson' => 'nullable|string',
            'boundary_area' => 'nullable|numeric|min:0',
            'geometry_type' => 'nullable|string|in:point,polygon',
            'enlem' => 'nullable|numeric|between:-90,90',
            'boylam' => 'nullable|numeric|between:-180,180',
            'adres' => 'nullable|string|max:500',
            'isinma_tipi' => 'nullable|string|in:Doğalgaz,Kombi,Klima,Soba,Merkezi,Yerden Isıtma',
            'site_ozellikleri' => 'nullable|array',
            'site_ozellikleri.*' => 'string|in:Güvenlik,Otopark,Havuz,Spor,Sauna,Oyun Alanı,Asansör',
            'isyeri_tipi' => 'nullable|string|in:Ofis,Mağaza,Dükkan,Depo,Fabrika,Atölye,Showroom',
            'kira_bilgisi' => 'nullable|string|max:1000',
            'ciro_bilgisi' => 'nullable|numeric|min:0',
            'ruhsat_durumu' => 'nullable|string|in:Var,Yok,Başvuruda',
            'personel_kapasitesi' => 'nullable|integer|min:0',
            'isyeri_cephesi' => 'nullable|integer|min:0',
            'min_konaklama' => 'nullable|integer|min:1|max:365',
            'max_misafir' => 'nullable|integer|min:1|max:50',
            'temizlik_ucreti' => 'nullable|numeric|min:0',
            'havuz' => 'nullable|boolean',
            'havuz_turu' => 'nullable|string|max:100',
            'havuz_boyut' => 'nullable|string|max:50',
            'havuz_derinlik' => 'nullable|string|max:50',
            'havuz_boyut_en' => 'nullable|string|max:20',
            'havuz_boyut_boy' => 'nullable|string|max:20',
            'gunluk_fiyat' => 'nullable|numeric|min:0',
            'haftalik_fiyat' => 'nullable|numeric|min:0',
            'aylik_fiyat' => 'nullable|numeric|min:0',
            'sezonluk_fiyat' => 'nullable|numeric|min:0',
            'sezon_baslangic' => 'nullable|date',
            'sezon_bitis' => 'nullable|date|after_or_equal:sezon_baslangic',
            'elektrik_dahil' => 'nullable|boolean',
            'su_dahil' => 'nullable|boolean',
            'internet_dahil' => 'nullable|boolean',
            'carsaf_dahil' => 'nullable|boolean',
            'havlu_dahil' => 'nullable|boolean',
            'klima_var' => 'nullable|boolean',
            'oda_sayisi' => 'nullable|integer|min:1|max:20',
            'banyo_sayisi' => 'nullable|integer|min:1|max:10',
            'yatak_sayisi' => 'nullable|integer|min:1|max:20',
            'restoran_mesafe' => 'nullable|integer|min:0|max:100',
            'market_mesafe' => 'nullable|integer|min:0|max:100',
            'deniz_mesafe' => 'nullable|integer|min:0|max:100',
            'merkez_mesafe' => 'nullable|integer|min:0|max:100',
            'bahce_var' => 'nullable|boolean',
            'tv_var' => 'nullable|boolean',
            'barbeku_var' => 'nullable|boolean',
            'sezlong_var' => 'nullable|boolean',
            'bahce_masasi_var' => 'nullable|boolean',
            'manzara' => 'nullable|string|max:100',
            'ev_tipi' => 'nullable|string|max:50',
            'ev_konsepti' => 'nullable|string|max:100',
            'proje_id' => 'nullable|exists:projeler,id',

            // HYBRID CORE (English keys)
            'minimum_stay' => 'nullable|integer|min:1|max:365',
            'maximum_stay' => 'nullable|integer|min:1|max:365',
            'check_in_time' => 'nullable|string|max:10',
            'check_out_time' => 'nullable|string|max:10',
            'max_guests' => 'nullable|integer|min:1|max:50',
            'base_guest_count' => 'nullable|integer|min:0|max:50',
            'extra_guest_fee' => 'nullable|numeric|min:0',
            'cleaning_fee' => 'nullable|numeric|min:0',
            'security_deposit' => 'nullable|numeric|min:0',
            'booking_type' => 'nullable|string|in:instant,request',
            'cancellation_policy' => 'nullable|string|in:flexible,moderate,strict',
        ];

        // Schema-driven dependency-aware rules (replaces CategoryFieldValidator)
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
     * Backend-enforced policy guard: category + yayın tipi combination.
     * EffectiveListingTypeResolver is the SINGLE SOURCE OF TRUTH.
     */
    public function withValidator(Validator $validator): void
    {
        // Existing proje_id conditional rule
        $yayinId = $this->input('yayin_tipi_id');
        $slug = null;
        if ($yayinId) {
            $yayin = YayinTipiSablonu::find($yayinId);
            $slug = $yayin ? strtolower($yayin->slug ?? '') : null;
        }
        if (in_array($slug, ['on-satis', 'insaat-halinde'])) {
            $validator->addRules([
                'proje_id' => 'required|exists:projeler,id',
            ]);
        }

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

            if ($mainCategoryId && $listingTypeId) {
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
}
