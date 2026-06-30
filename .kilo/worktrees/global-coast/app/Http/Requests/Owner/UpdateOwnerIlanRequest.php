<?php

namespace App\Http\Requests\Owner;

use App\Models\Ilan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateOwnerIlanRequest
 *
 * SAB v6.1.2 — Owner Portal ilan güncelleme isteği.
 *
 * Owner yalnızca kendi mülkünü güncelleyebilir.
 * Policy kontrolü authorize() içinde yapılır.
 * Yayin durumu ve danışman ataması owner tarafından değiştirilemez.
 */
class UpdateOwnerIlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Ilan|null $ilan */
        $ilan = $this->route('ilan');

        if (! $ilan instanceof Ilan) {
            return false;
        }

        return $this->user()?->can('update', $ilan) ?? false;
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

            // Mülk özellikleri (opsiyonel)
            'metrekare'         => ['nullable', 'numeric', 'min:0'],
            'oda_sayisi'        => ['nullable', 'string', 'max:50'],
            'bina_yasi'         => ['nullable', 'integer', 'min:0', 'max:200'],
            'kat'               => ['nullable', 'string', 'max:50'],
            'isitma_tipi'       => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'baslik.required'          => 'İlan başlığı zorunludur.',
            'ana_kategori_id.required' => 'Kategori seçimi zorunludur.',
            'il_id.required'           => 'İl seçimi zorunludur.',
            'para_birimi.required'     => 'Para birimi seçimi zorunludur.',
            'fiyat_gosterim_modu.required' => 'Fiyat gösterim modu seçimi zorunludur.',
        ];
    }

    /**
     * Owner'ın değiştiremeyeceği alanları strip et.
     * yayin_durumu ve danisman_id mevcut değerlerinde kalır.
     */
    protected function prepareForValidation(): void
    {
        // Bu alanları input'tan çıkar — owner değiştiremez
        $this->request->remove('yayin_durumu');
        $this->request->remove('danisman_id');
        $this->request->remove('user_id');
    }
}
