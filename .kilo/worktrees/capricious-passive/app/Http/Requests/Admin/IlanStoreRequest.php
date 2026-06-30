<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\IlanDurumu;

class IlanStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'yayin_durumu' => $this->input('yayin_durumu'),
        ]);
    }

    public function authorize(): bool
    {
        return true; // Admin yetkisi middleware seviyesinde kontrol ediliyor
    }

    public function rules(): array
    {
        return [
            'baslik' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'fiyat' => 'required|numeric|min:0',
            'para_birimi' => 'required|string|in:TRY,USD,EUR,GBP',
            'ana_kategori_id' => 'required|exists:ilan_kategorileri,id',
            'alt_kategori_id' => 'required|exists:ilan_kategorileri,id',
            'yayin_tipi_id' => 'required|integer|exists:yayin_tipi_sablonlari,id',
            'ilan_sahibi_id' => 'required|exists:kisiler,id',
            'danisman_id' => 'nullable|exists:users,id',
            'il_id' => 'nullable|exists:iller,id',
            'ilce_id' => 'nullable|exists:ilceler,id',
            'mahalle_id' => 'nullable|exists:mahalleler,id',
            'yayin_durumu' => 'required|string|in:' . implode(',', IlanDurumu::values()),
            'crm_only' => 'nullable|boolean',
            'sokak' => 'nullable|string|max:255',
            'cadde' => 'nullable|string|max:255',
            'bulvar' => 'nullable|string|max:255',
            'bina_no' => 'nullable|string|max:20',
            'daire_no' => 'nullable|string|max:20',
            'posta_kodu' => 'nullable|string|max:10',
            'nearby_distances' => 'nullable|string', // JSON Validation serviste yapılacak
        ];
    }
}
