<?php

namespace App\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Ilan (Listing) Form Request - Validation for Listing creation
 * 
 * Context7: 100% Compliant
 * - Field names: baslik, aciklama, alan_m2, birim_fiyat, il, ilce, mahalle, lat, lng
 * - No forbidden patterns
 */
class StoreIlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'baslik' => 'required|string|max:255',
            'aciklama' => 'required|string|min:20',
            'alan_m2' => 'required|numeric|min:1',
            'birim_fiyat' => 'required|numeric|min:0',
            'il' => 'required|string|max:50',
            'ilce' => 'required|string|max:50',
            'mahalle' => 'required|string|max:50',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'one_cikan' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'baslik.required' => 'Başlık alanı zorunludur.',
            'baslik.max' => 'Başlık en fazla 255 karakter olabilir.',
            'aciklama.required' => 'Açıklama alanı zorunludur.',
            'aciklama.min' => 'Açıklama en az 20 karakter olmalıdır.',
            'alan_m2.required' => 'Alan (m²) zorunludur.',
            'alan_m2.numeric' => 'Alan sayısal bir değer olmalıdır.',
            'birim_fiyat.required' => 'Birim Fiyat zorunludur.',
            'il.required' => 'İl alanı zorunludur.',
            'ilce.required' => 'İlçe alanı zorunludur.',
            'mahalle.required' => 'Mahalle alanı zorunludur.',
            'lat.required' => 'Enlem (latitude) zorunludur.',
            'lat.between' => 'Enlem -90 ile 90 arasında olmalıdır.',
            'lng.required' => 'Boylam (longitude) zorunludur.',
            'lng.between' => 'Boylam -180 ile 180 arasında olmalıdır.',
        ];
    }
}
