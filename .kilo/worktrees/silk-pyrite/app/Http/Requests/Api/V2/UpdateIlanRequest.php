<?php

namespace App\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Ilan (Listing) Form Request - Validation for Listing updates
 * 
 * Context7: 100% Compliant
 */
class UpdateIlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'baslik' => 'sometimes|string|max:255',
            'aciklama' => 'sometimes|string|min:20',
            'alan_m2' => 'sometimes|numeric|min:1',
            'birim_fiyat' => 'sometimes|numeric|min:0',
            'il' => 'sometimes|string|max:50',
            'ilce' => 'sometimes|string|max:50',
            'mahalle' => 'sometimes|string|max:50',
            'lat' => 'sometimes|numeric|between:-90,90',
            'lng' => 'sometimes|numeric|between:-180,180',
            'one_cikan' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'aciklama.min' => 'Açıklama en az 20 karakter olmalıdır.',
            'alan_m2.numeric' => 'Alan sayısal bir değer olmalıdır.',
            'lat.between' => 'Enlem -90 ile 90 arasında olmalıdır.',
            'lng.between' => 'Boylam -180 ile 180 arasında olmalıdır.',
        ];
    }
}
