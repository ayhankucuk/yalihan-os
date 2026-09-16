<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreOwnerIlanRequest
 *
 * SAB v6.1.2 — Owner Portal ilan oluşturma isteği.
 *
 * Admin StoreIlanRequest'ten daha kısıtlı: owner yalnızca temel
 * mülk bilgilerini girebilir; danışman ataması ve yayın durumu
 * sisteme bırakılır (taslak olarak başlar).
 */
class StoreOwnerIlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Ilan::class) ?? false;
    }

    public function rules(): array
    {
        return [
            // Temel Bilgiler
            'baslik'            => ['required', 'string', 'max:255'],
            'aciklama'          => ['nullable', 'string', 'max:10000'],

            // Fiyat
            'fiyat'             => ['nullable', 'numeric', 'min:0'],
            'para_birimi'       => ['required', 'string', Rule::in(['TRY', 'USD', 'EUR', 'GBP'])],
            'fiyat_gosterim_modu' => ['required', Rule::in(['exact', 'on_request', 'hidden'])],

            // Kategori
            'ana_kategori_id'   => ['required', 'integer', 'exists:ilan_kategorileri,id'],
            'alt_kategori_id'   => ['nullable', 'integer', 'exists:ilan_kategorileri,id'],

            // Konum
            'il_id'             => ['required', 'integer', 'exists:iller,id'],
            'ilce_id'           => ['nullable', 'integer', 'exists:ilceler,id'],
            'mahalle_id'        => ['nullable', 'integer', 'exists:mahalleler,id'],
            'adres'             => ['nullable', 'string', 'max:500'],
            'lat'               => ['nullable', 'numeric', 'between:-90,90'],
            'lng'               => ['nullable', 'numeric', 'between:-180,180'],

            // Mülk özellikleri (opsiyonel)
            'net_m2'            => ['nullable', 'numeric', 'min:0'],
            'brut_m2'           => ['nullable', 'numeric', 'min:0'],
            'oda_sayisi'        => ['nullable', 'string', 'max:50'],
            'banyo_sayisi'      => ['nullable', 'integer', 'min:0', 'max:50'],
            'bina_yasi'         => ['nullable', 'integer', 'min:0', 'max:200'],
            'kat'               => ['nullable', 'string', 'max:50'],
            'toplam_kat'        => ['nullable', 'integer', 'min:0', 'max:200'],
            'isitma_tipi'       => ['nullable', 'string', 'max:100'],
            'esyali'            => ['nullable', 'boolean'],
            'aidat'             => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'baslik.required'            => 'İlan başlığı zorunludur.',
            'ana_kategori_id.required'   => 'Kategori seçimi zorunludur.',
            'il_id.required'             => 'İl seçimi zorunludur.',
            'para_birimi.required'       => 'Para birimi seçimi zorunludur.',
            'fiyat_gosterim_modu.required' => 'Fiyat gösterim modu seçimi zorunludur.',
            'lat.between'                => 'Enlem değeri -90 ile 90 arasında olmalıdır.',
            'lng.between'                => 'Boylam değeri -180 ile 180 arasında olmalıdır.',
        ];
    }

    /**
     * Owner'ın değiştiremeyeceği alanları zorla.
     * Yeni ilan her zaman 'taslak' olarak başlar.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'yayin_durumu' => \App\Enums\IlanDurumu::TASLAK->value,
        ]);
    }
}
