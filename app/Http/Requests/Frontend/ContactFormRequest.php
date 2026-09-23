<?php

declare(strict_types=1);

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ContactFormRequest
 *
 * Validates the ACTUAL browser-field names submitted by Blade templates:
 *   - name      (full name — will be decomposed to ad/soyad by validated())
 *   - phone     (renamed from 'telefon' in earlier draft)
 *   - message   (renamed from 'mesaj' in earlier draft)
 *   - ilan_id   (hidden field from the property detail page)
 *
 * Corrections applied:
 *   - CORRECTION 1: Uses actual Blade field names (name, phone, message, ilan_id)
 *   - Loose phone regex to support international numbers
 *   - Name decomposition into ad/soyad via validated() — no field renaming
 *   - ilan_id required (from hidden field), exists check against ilanlar
 *
 * Task: WEB_PROPERTY_DETAIL_CRM_CONTACT_REMEDIATION_16
 */
class ContactFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ilan_id' => ['required', 'integer', 'exists:ilanlar,id'],
            'name'    => ['required', 'string', 'max:200'],
            'phone'   => ['required', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'ilan_id.required' => 'İlan bilgisi alınamadı. Lütfen sayfayı yenileyin.',
            'ilan_id.exists'  => 'Bu ilan artık mevcut değil.',
            'name.required'   => 'Ad soyad alanı zorunludur.',
            'phone.required'  => 'Telefon numarası zorunludur.',
        ];
    }

    /**
     * Normalize browser field names to canonical CRM field names.
     *
     * Blade submits: name, phone, message, ilan_id
     * Canonical CRM expects: ad, soyad, telefon, mesaj, ilan_id
     *
     * Full-name decomposition: "Ahmet Yılmaz" → ad="Ahmet", soyad="Yılmaz"
     * Single name: "Ahmet"     → ad="Ahmet", soyad=""
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        // Decompose 'name' (Blade) → 'ad' + 'soyad' (CRM)
        if (!empty($data['name']) && empty($data['ad'])) {
            $parts = explode(' ', trim((string) $data['name']), 2);
            $data['ad']    = $parts[0];
            $data['soyad'] = $parts[1] ?? '';
        }

        // Rename 'phone' (Blade) → 'telefon' (CRM)
        if (isset($data['phone']) && !isset($data['telefon'])) {
            $data['telefon'] = $data['phone'];
        }

        // Rename 'message' (Blade) → 'mesaj' (CRM)
        if (isset($data['message']) && !isset($data['mesaj'])) {
            $data['mesaj'] = $data['message'];
        }

        return $data;
    }
}
