<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Media Analyze Request — Sprint 6.3
 */
class MediaAnalyzeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ilan_id' => ['required', 'integer', 'min:1'],
            'async' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'ilan_id.required' => 'İlan ID zorunludur.',
            'ilan_id.integer' => 'İlan ID bir tamsayı olmalıdır.',
        ];
    }
}
