<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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

    public function rules(): array
    {
        $kisiId = $this->route('kisi')->id ?? $this->route('id');

        return [
            // ✅ SAB Uyumlu Alan Adları
            'ad' => 'required|string|max:255',
            'soyad' => 'required|string|max:255',
            'telefon' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:kisiler,email,' . $kisiId,
            'tc_kimlik' => 'nullable|string|size:11',
            'kisi_tipi' => 'nullable|string|max:50',
            'aktiflik_durumu' => 'boolean',
            'crm_surec_asamasi' => 'required|string|in:yeni,gorusme,takip,tamamlandi,kaybedildi',
            'danisman_id' => 'nullable|exists:users,id',
            'il_id' => 'nullable|exists:iller,id',
            'ilce_id' => 'nullable|exists:ilceler,id',
            'mahalle_id' => 'nullable|exists:mahalleler,id',
            'adres_detay' => 'nullable|string|max:500',
            'notlar' => 'nullable|string|max:2000',
            // Legacy field mapping (if needed, but frontend sends correct names now)
        ];
    }

    public function messages(): array
    {
        return [
            'ad.required' => 'Ad alanı zorunludur.',
            'soyad.required' => 'Soyad alanı zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'email.unique' => 'Bu e-posta adresi zaten kullanılmaktadır.',
            'tc_kimlik.size' => 'TC Kimlik No 11 haneli olmalıdır.',
            'crm_surec_asamasi.required' => 'CRM durumu zorunludur.',
            'crm_surec_asamasi.in' => 'Geçersiz CRM durumu.',
            'danisman_id.exists' => 'Seçilen danışman bulunamadı.',
            'il_id.exists' => 'Seçilen il bulunamadı.',
            'ilce_id.exists' => 'Seçilen ilçe bulunamadı.',
            'mahalle_id.exists' => 'Seçilen mahalle bulunamadı.',
        ];
    }
}
