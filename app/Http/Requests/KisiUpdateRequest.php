<?php

namespace App\Http\Requests;

use App\Enums\KisiDurumu;
use App\Models\Kisi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * KisiUpdateRequest
 *
 * Context7: C7-KISI-UPDATE-REQUEST-2025-12-27
 */
class KisiUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email') && ! $this->has('eposta')) {
            $this->merge(['eposta' => $this->input('email')]);
        }
    }

    public function rules(): array
    {
        $kisi = $this->route('kisi');
        $kisiId = $kisi instanceof Kisi ? $kisi->id : ($kisi ?? $this->route('id'));

        return [
            // ✅ SAB Uyumlu Alan Adları
            'ad' => 'required|string|max:255',
            'soyad' => 'required|string|max:255',
            'telefon' => 'nullable|string|max:20',
            'eposta' => 'nullable|email|max:255|unique:kisiler,eposta,'.$kisiId,
            'email' => 'nullable|email|max:255',
            'tc_kimlik' => 'nullable|string|size:11',
            'kisi_tipi' => 'nullable|string|max:50',
            'aktiflik_durumu' => 'boolean',
            'crm_surec_asamasi' => ['nullable', Rule::enum(KisiDurumu::class)],
            'danisman_id' => 'nullable|exists:users,id',
            'il_id' => 'nullable|exists:iller,id',
            'ilce_id' => 'nullable|exists:ilceler,id',
            'mahalle_id' => 'nullable|exists:mahalleler,id',
            'adres_detay' => 'nullable|string|max:500',
            'notlar' => 'nullable|string|max:2000',
            // Legacy field mapping (if needed, but frontend sends correct names now)
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        if (is_array($validated)) {
            if (isset($validated['email']) && ! isset($validated['eposta'])) {
                $validated['eposta'] = $validated['email'];
            }
            unset($validated['email']);
        }

        return $validated;
    }

    public function messages(): array
    {
        return [
            'ad.required' => 'Ad alanı zorunludur.',
            'soyad.required' => 'Soyad alanı zorunludur.',
            'eposta.email' => 'Geçerli bir e-posta adresi giriniz.',
            'eposta.unique' => 'Bu e-posta adresi zaten kullanılmaktadır.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'email.unique' => 'Bu e-posta adresi zaten kullanılmaktadır.',
            'tc_kimlik.size' => 'TC Kimlik No 11 haneli olmalıdır.',
            'crm_surec_asamasi.required' => 'CRM durumu zorunludur.',
            'crm_surec_asamasi.in' => 'Geçersiz CRM durumu.',
            'crm_surec_asamasi.enum' => 'Geçersiz CRM durumu.',
            'crm_surec_asamasi.Illuminate\Validation\Rules\Enum' => 'Geçersiz CRM durumu.',
            'danisman_id.exists' => 'Seçilen danışman bulunamadı.',
            'il_id.exists' => 'Seçilen il bulunamadı.',
            'ilce_id.exists' => 'Seçilen ilçe bulunamadı.',
            'mahalle_id.exists' => 'Seçilen mahalle bulunamadı.',
        ];
    }
}
