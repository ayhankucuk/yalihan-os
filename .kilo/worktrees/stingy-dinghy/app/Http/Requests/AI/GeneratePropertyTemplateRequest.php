<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePropertyTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alt_kategori_id' => 'required|integer',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response = \App\Services\Response\ResponseService::error(
            message: 'Geçersiz kategori seçimi',
            code: 'VALIDATION_FAILED',
            errors: $validator->errors()->toArray(),
            yanitKodu: 422
        );

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}
