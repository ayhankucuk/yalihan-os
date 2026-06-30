<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class IlanKategoriFieldUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'superadmin']);
    }

    /**
     * Normalize legacy field names before validation.
     */
    protected function prepareForValidation(): void
    {
        $field = $this->input('field');
        $map = [
            // Legacy → Canonical (limited)
            'sort_order' => 'display_order',
        ];

        $this->merge([
            'field' => $map[$field] ?? $field,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'field' => 'required|string|in:name,description,aktiflik_durumu,display_order',
            'value' => 'required',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'field.required' => 'Alan seçimi zorunludur.',
            'field.in' => 'Geçersiz alan seçimi.',
            'value.required' => 'Değer zorunludur.',
        ];
    }
}
