<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class IlanKategoriRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        return auth()->check() && $user->hasAnyRole(['admin', 'superadmin']);
    }

    /**
     * Normalize legacy field names before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'aktiflik_durumu' => $this->input('aktiflik_durumu'),
            'display_order' => $this->input('display_order'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $routeParam = $this->route('kategori');
        $id = is_object($routeParam) ? ($routeParam->id ?? null) : (is_numeric($routeParam) ? (int) $routeParam : null);
        $seviye = $this->input('seviye');

        return [
            'name' => 'required|string|max:255',
            'parent_id' => array_filter([
                'nullable',
                'exists:ilan_kategorileri,id',
                $id ? 'not_in:'.$id : null,
                // Context7: Alt kategori (seviye=1) için parent_id zorunlu
                function ($attribute, $value, $fail) use ($seviye) {
                    if (($seviye == 1 || $seviye == 2) && ! $value) {
                        $fail('Alt kategori veya Yayın Tipi için Üst Kategori seçmelisiniz.');
                    }
                    if ($seviye == 0 && $value) {
                        $fail('Ana kategorinin üst kategorisi olamaz.');
                    }
                },
            ]),
            'seviye' => 'required|integer|in:0,1,2',
            'aktiflik_durumu' => 'nullable|boolean', // Context7: active state field
            'display_order' => 'nullable|integer|min:0', // ✅ SAB: sequence field
            'slug' => 'nullable|string|max:255|unique:ilan_kategorileri,slug'.($id ? ','.$id : ''),
            'icon' => 'nullable|string|max:100',
            'aciklama' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Kategori adı zorunludur.',
            'name.max' => 'Kategori adı en fazla 255 karakter olabilir.',
            'parent_id.exists' => 'Seçilen üst kategori bulunamadı.',
            'parent_id.not_in' => 'Kategori kendisinin alt kategorisi olamaz.',
            'seviye.required' => 'Seviye seçimi zorunludur.',
            'seviye.integer' => 'Seviye sayısal bir değer olmalıdır.',
            'seviye.in' => 'Geçersiz seviye değeri.',
            'aktiflik_durumu.boolean' => 'Aktiflik değeri geçersiz.',
            'display_order.integer' => 'Sıralama sayısal bir değer olmalıdır.', // ✅ SAB: display_order
            'display_order.min' => 'Sıralama 0 veya daha büyük olmalıdır.', // ✅ SAB: display_order
        ];
    }
}
