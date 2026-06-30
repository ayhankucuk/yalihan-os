<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DashboardWidgetRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'type' => 'required|in:stat,chart,table,activity',
            'data_source' => 'required|string',
            'position_x' => 'required|integer|min:0',
            'position_y' => 'required|integer|min:0',
            'width' => 'required|integer|min:1|max:12',
            'height' => 'required|integer|min:1|max:6',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Widget adı zorunludur.',
            'name.max' => 'Widget adı en fazla 255 karakter olabilir.',
            'type.required' => 'Widget tipi zorunludur.',
            'type.in' => 'Geçersiz widget tipi.',
            'data_source.required' => 'Veri kaynağı zorunludur.',
            'position_x.required' => 'X pozisyonu zorunludur.',
            'position_x.integer' => 'X pozisyonu sayısal bir değer olmalıdır.',
            'position_x.min' => 'X pozisyonu 0 veya daha büyük olmalıdır.',
            'position_y.required' => 'Y pozisyonu zorunludur.',
            'position_y.integer' => 'Y pozisyonu sayısal bir değer olmalıdır.',
            'position_y.min' => 'Y pozisyonu 0 veya daha büyük olmalıdır.',
            'width.required' => 'Genişlik zorunludur.',
            'width.integer' => 'Genişlik sayısal bir değer olmalıdır.',
            'width.min' => 'Genişlik en az 1 olmalıdır.',
            'width.max' => 'Genişlik en fazla 12 olabilir.',
            'height.required' => 'Yükseklik zorunludur.',
            'height.integer' => 'Yükseklik sayısal bir değer olmalıdır.',
            'height.min' => 'Yükseklik en az 1 olmalıdır.',
            'height.max' => 'Yükseklik en fazla 6 olabilir.',
        ];
    }
}
