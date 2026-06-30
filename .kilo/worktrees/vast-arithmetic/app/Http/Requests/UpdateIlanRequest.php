<?php

namespace App\Http\Requests;

use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\CategoryFieldValidator;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIlanRequest extends FormRequest
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
                Log::warning('Context7: Turkish field detected in UpdateIlanRequest', ['field' => $tr, 'preferred' => $en]);
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
            'yayin_durumu' => 'required|string|in:Taslak,Aktif,Pasif,Beklemede',
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

        $dynamic = (new CategoryFieldValidator)->getRules($kategoriSlug, $yayinTipiSlug);

        return array_merge($base, $dynamic);
    }

    public function withValidator($validator)
    {
        $yayinId = $this->input('yayin_tipi_id');
        $slug = null;
        if ($yayinId) {
            $yayin = \App\Models\YayinTipiSablonu::find($yayinId);
            $slug = $yayin ? strtolower($yayin->slug ?? '') : null;
        }
        if (in_array($slug, ['on-satis', 'insaat-halinde'])) {
            $validator->addRules([
                'proje_id' => 'required|exists:projeler,id',
            ]);
        }
    }
}
