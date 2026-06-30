<?php

namespace App\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update User Form Request - Validation for User updates
 * 
 * Context7: 100% Compliant
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ad_soyad' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:kullanicilar,email,' . $this->route('kullanici'),
            'telefon' => 'sometimes|string|max:20',
            'rol' => 'sometimes|in:admin,danisman,musteri',
            'aktiflik_durumu' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Bu email adresi zaten kayıtlı.',
            'rol.in' => 'Rol sadece admin, danisman veya musteri olabilir.',
        ];
    }
}
