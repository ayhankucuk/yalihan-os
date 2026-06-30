<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Phase 17 Backward-Compatible Adapter — ListingIdAliasRequest
 *
 * AMAÇ:
 * Dış dünya (Frontend JS, AI Cortex, Raporlama servisleri) `listing_id` query
 * parametresiyle istek göndermeye devam edebilir. Bu adapter, `listing_id` ile
 * gelen her isteği domain canonical alanına (`ilan_id`) eşler.
 *
 * KATMAN SEBEBİ — Neden FormRequest?
 *   - Middleware: Tüm isteklere global etki eder, domain sınırı yoktur.
 *   - DTO/Mapper: Ayrı nesne yönetimi gerektirir, basit mapping için fazla.
 *   - FormRequest: Validation + Mapping birleşik, controller'a sıfır değişiklik,
 *     sadece ihtiyaç duyulan endpoint'lere bağlanır. SAB Thin Controller kuralına
 *     en uygun yaklaşımdır.
 *
 * KULLANIM:
 *   Controller metotlarında `Request $request` yerine `ListingIdAliasRequest $request`
 *   kullanıldığında, `$request->input('ilan_id')` canonical değeri döner.
 *
 * GEÇİCİLİK:
 *   Bu sınıf Phase 17 cleanup aşaması tamamlandığında ve tüm istemciler
 *   `ilan_id` kullanır hale geldiğinde kaldırılacaktır.
 *   Kaldırma tarihi: Phase 17 Aşama 5 — Cleanup sonrası.
 *
 * @see Phase 17 Backward Compatibility Plan
 * @deprecated Bu adapter Phase 17 uçtan uca tamamlandığında silinecektir.
 */
class ListingIdAliasRequest extends FormRequest
{
    /**
     * Her kullanıcı bu isteği yapabilir (yetkilendirme controller'da).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation kuralları.
     *
     * Hem `listing_id` (legacy/dış kontrat) hem `ilan_id` (canonical) kabul edilir.
     * İkisi birden varsa `ilan_id` önceliklidir (canonical wins).
     */
    public function rules(): array
    {
        return [
            'listing_id' => 'required_without:ilan_id|integer|exists:ilanlar,id',
            'ilan_id'    => 'required_without:listing_id|integer|exists:ilanlar,id',
        ];
    }

    /**
     * Validation sonrası canonical ilan_id'yi garanti altına al.
     * `listing_id` geldi ise `ilan_id` olarak eşle.
     */
    public function passedValidation(): void
    {
        if ($this->missing('ilan_id') || blank($this->input('ilan_id'))) {
            $this->merge([
                'ilan_id' => $this->input('listing_id'),
            ]);
        }
    }

    /**
     * Validation hata mesajlarını Türkçeleştir.
     */
    public function messages(): array
    {
        return [
            'listing_id.exists' => 'Belirtilen ilan mevcut değil.',
            'ilan_id.exists'    => 'Belirtilen ilan mevcut değil.',
        ];
    }

    /**
     * Adapter aracılığıyla canonical değeri doğrudan al.
     * Controller'ı bu metot üzerinden kullanır.
     */
    public function ilanId(): int
    {
        return (int) $this->input('ilan_id');
    }
}
