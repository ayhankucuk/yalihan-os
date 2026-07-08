<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Location Analyze Request — Sprint 6.2
 */
class LocationAnalyzeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ilan_id' => ['required', 'integer', 'min:1'],
            'include_ai_summary' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'ilan_id.required' => 'İlan ID zorunludur.',
            'ilan_id.integer' => 'İlan ID bir tamsayı olmalıdır.',
            'ilan_id.min' => 'Geçerli bir İlan ID giriniz.',
        ];
    }
}
