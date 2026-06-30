<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SeasonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'superadmin', 'danisman']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'adi' => 'required|string|max:255',
            'sezon_tipi' => 'nullable|in:yaz,ara_sezon,kis,ozel',
            'baslangic' => 'required|date',
            'bitis' => 'required|date|after:baslangic',
            'gunluk_fiyat' => 'required|numeric|min:0',
            'ilan_id' => 'nullable|exists:ilanlar,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'adi.required' => 'Sezon adı zorunludur.',
            'adi.max' => 'Sezon adı en fazla 255 karakter olabilir.',
            'sezon_tipi.in' => 'Geçersiz sezon tipi.',
            'baslangic.required' => 'Başlangıç tarihi zorunludur.',
            'baslangic.date' => 'Geçerli bir başlangıç tarihi giriniz.',
            'bitis.required' => 'Bitiş tarihi zorunludur.',
            'bitis.date' => 'Geçerli bir bitiş tarihi giriniz.',
            'bitis.after' => 'Bitiş tarihi başlangıç tarihinden sonra olmalıdır.',
            'gunluk_fiyat.required' => 'Günlük fiyat zorunludur.',
            'gunluk_fiyat.numeric' => 'Günlük fiyat sayısal bir değer olmalıdır.',
            'gunluk_fiyat.min' => 'Günlük fiyat 0 veya daha büyük olmalıdır.',
        ];
    }
}
