<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'superadmin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $photoId = $this->route('photo') ? $this->route('photo')->id : null;

        return [
            'category' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'alt_text' => 'nullable|string|max:255',
            'tags' => 'nullable|string',
            'one_cikan' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'category.required' => 'Kategori seçimi zorunludur.',
            'title.required' => 'Başlık alanı zorunludur.',
            'title.max' => 'Başlık en fazla 255 karakter olabilir.',
            'description.max' => 'Açıklama en fazla 1000 karakter olabilir.',
            'alt_text.max' => 'Alt metin en fazla 255 karakter olabilir.',
        ];
    }
}
