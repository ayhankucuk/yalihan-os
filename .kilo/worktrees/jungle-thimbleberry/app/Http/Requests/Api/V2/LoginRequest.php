<?php

namespace App\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Login Form Request - Validation for user authentication
 * 
 * Context7: 100% Compliant
 * - Field names: email, sifre (not password)
 * - No forbidden patterns
 */
class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return true; // Public endpoint, no auth required
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'sifre' => 'required|string|min:6',
        ];
    }

    /**
     * Get custom messages for validator errors
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email alanı zorunludur.',
            'email.email' => 'Geçerli bir email adresi giriniz.',
            'sifre.required' => 'Şifre alanı zorunludur.',
            'sifre.min' => 'Şifre en az 6 karakter olmalıdır.',
        ];
    }
}
