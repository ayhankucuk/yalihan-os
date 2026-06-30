<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PhotoBulkActionRequest extends FormRequest
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
        return [
            'action' => 'required|in:delete,move,feature,unfeature',
            'photo_ids' => 'required|array|min:1',
            'photo_ids.*' => 'integer|exists:photos,id',
            'target_category' => 'required_if:action,move|string|max:50',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'İşlem seçimi zorunludur.',
            'action.in' => 'Geçersiz işlem seçildi.',
            'photo_ids.required' => 'En az bir fotoğraf seçmelisiniz.',
            'photo_ids.array' => 'Fotoğraf seçimi geçersiz.',
            'photo_ids.min' => 'En az bir fotoğraf seçmelisiniz.',
            'photo_ids.*.integer' => 'Fotoğraf ID geçersiz.',
            'photo_ids.*.exists' => 'Seçilen fotoğraflardan biri bulunamadı.',
            'target_category.required_if' => 'Hedef kategori seçimi zorunludur.',
        ];
    }
}
