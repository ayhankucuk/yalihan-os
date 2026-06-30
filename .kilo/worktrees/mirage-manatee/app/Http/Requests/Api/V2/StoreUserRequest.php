<?php

namespace App\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store User Form Request - Validation for User creation
 * 
 * Context7: 100% Compliant
 * - Uses canonical field names (ad_soyad, rol, aktiflik_durumu)
 * - No forbidden patterns (enabled, is_active, etc.)
 */
class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        // Only authenticated users can create users (or admins)
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        return [
            'ad_soyad' => 'required|string|max:255',
            'email' => 'required|email|unique:kullanicilar,email',
            'telefon' => 'sometimes|string|max:20',
            'sifre_hash' => 'required|string|min:6',
            'rol' => 'required|in:admin,danisman,musteri',
            'aktiflik_durumu' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors
     */
    public function messages(): array
    {
        return [
            'ad_soyad.required' => 'Ad Soyad alanı zorunludur.',
            'ad_soyad.max' => 'Ad Soyad en fazla 255 karakter olabilir.',
            'email.required' => 'Email alanı zorunludur.',
            'email.email' => 'Geçerli bir email adresi giriniz.',
            'email.unique' => 'Bu email adresi zaten kayıtlı.',
            'rol.required' => 'Rol alanı zorunludur.',
            'rol.in' => 'Rol sadece admin, danisman veya musteri olabilir.',
            'sifre_hash.required' => 'Şifre alanı zorunludur.',
            'sifre_hash.min' => 'Şifre en az 6 karakter olmalıdır.',
        ];
    }
}
