<?php

namespace App\Http\Requests\Admin\Ilan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Ilan Request
 *
 * Context7: C7-ILAN-UPDATE-REQUEST-2025-12-28
 * Validation rules for updating existing listings
 */
class UpdateIlanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'fiyat_gosterim_modu' => $this->input('fiyat_gosterim_modu', $this->input('price_display_mode', 'exact')),
            'fiyat' => $this->input('fiyat', $this->input('price')),
            'baslangic_fiyati' => $this->input('baslangic_fiyati', $this->input('starting_price')),
            'fiyat_notu' => $this->input('fiyat_notu', $this->input('price_note')),
            'para_birimi' => $this->input('para_birimi', $this->input('currency', 'TRY')),
        ]);

        // ✅ FIX-2 (SAB Sprint 2026-04-15): features[] → top-level promotion (parity with StoreIlanRequest)
        // Wizard sends features[slug]=value, schema validation expects top-level slug keys.
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

        // ✅ FIX-3 (SAB Sprint 2026-04-15): adres_detay → adres bridge (parity with StoreIlanRequest)
        if ($this->has('adres_detay') && !$this->filled('adres')) {
            $this->merge(['adres' => $this->input('adres_detay')]);
        }

        $this->normalizeBooleanInputs();
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('ilan')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
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

            // Context7: 3-level category
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

            // Status
            'yayin_durumu' => 'required|in:' . implode(',', \App\Enums\IlanDurumu::values()),

            // Property Details
            'oda_sayisi' => 'nullable|integer|min:0|max:20',
            'banyo_sayisi' => 'nullable|integer|min:0|max:10',
            'yatak_sayisi' => 'nullable|integer|min:0|max:20',
            'brut_m2' => 'nullable|numeric|min:0',
            'net_m2' => 'nullable|numeric|min:0',

            // Vacation Rental (Rental Engine)
            'rental_enabled' => 'nullable|boolean',
            'min_stay_nights' => 'nullable|integer|min:1|max:365',
            'max_stay_nights' => 'nullable|integer|min:1|max:365',
            'max_guests' => 'nullable|integer|min:1|max:50',
            'base_guest_count' => 'nullable|integer|min:1|max:50',
            'extra_guest_fee' => 'nullable|numeric|min:0',
            'cleaning_fee' => 'nullable|numeric|min:0',
            'security_deposit' => 'nullable|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'booking_type' => 'nullable|string',
            'cancellation_policy' => 'nullable|string',
            'checkin_time' => 'nullable|date_format:H:i',
            'checkout_time' => 'nullable|date_format:H:i',
            'airbnb_ical_url' => 'nullable|url',

            // Legacy / Old Variable Fallbacks
            'min_konaklama' => 'nullable|integer|min:1|max:365',
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

            // Distances
            'restoran_mesafe' => 'nullable|integer|min:0|max:100',
            'market_mesafe' => 'nullable|integer|min:0|max:100',
            'deniz_mesafe' => 'nullable|integer|min:0|max:100',
            'merkez_mesafe' => 'nullable|integer|min:0|max:100',

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

            // Geometry (Arsa/Arazi polygon)
            'boundary_geojson' => 'nullable|string',
            'boundary_area' => 'nullable|numeric|min:0',
            'geometry_type' => 'nullable|string|in:point,polygon',

            // ✅ FIX-2 (SAB Sprint 2026-04-15): Pass features array through validation (parity with StoreIlanRequest)
            'features' => 'nullable|array',
            'features.*' => 'nullable',

            // ✅ FIX-3 (SAB Sprint 2026-04-15): Accept adres_detay from wizard (parity with StoreIlanRequest)
            'adres_detay' => 'nullable|string|max:500',
        ];
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
            'ilan_sahibi_id.required' => 'İlan sahibi seçimi zorunludur.',
        ];
    }

    /**
     * Normalize booleans before validation.
     */
    protected function normalizeBooleanInputs(): void
    {
        // Convert string booleans to actual booleans
        $booleanFields = [
            'havuz', 'elektrik_dahil', 'su_dahil', 'internet_dahil',
            'klima_var', 'bahce_var', 'tv_var', 'barbeku_var', 'carsaf_dahil', 'havlu_dahil',
            'sezlong_var', 'bahce_masasi_var', 'havuz_var', 'havuz_isitmali',
            'deniz_manzarali', 'doga_manzarali', 'dag_manzarali',
            'mutfak_tam_donanmli', 'mutfak_bulasik_makinesi', 'mutfak_kahve_makinesi',
            'evcil_hayvan_uygun', 'sigara_icilmez', 'kadastral_yol'
        ];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                ]);
            }
        }

        if ($this->has('rental_enabled')) {
            $this->merge([
                'rental_enabled' => filter_var($this->input('rental_enabled'), FILTER_VALIDATE_BOOLEAN)
            ]);
        }
    }
}
