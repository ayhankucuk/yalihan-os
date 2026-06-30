<?php

namespace App\Traits\Ilan;

use App\Enums\IlanDurumu;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait IlanAccessors
{
    /**
     * Safe accessor for yayin_durumu — handles legacy integer/string values
     * that predate the canonical enum migration. Prevents ValueError on hydration.
     * Uses tryFrom() → normalize() → null fallback chain (SAB §5.5 safe).
     */
    public function getYayinDurumuAttribute(): ?IlanDurumu
    {
        $raw = $this->attributes['yayin_durumu'] ?? null;

        if ($raw === null) {
            return null;
        }

        if ($raw instanceof IlanDurumu) {
            return $raw;
        }

        // tryFrom for canonical string values ('taslak', 'yayinda', etc.)
        $fromEnum = IlanDurumu::tryFrom((string) $raw);
        if ($fromEnum !== null) {
            return $fromEnum;
        }

        // Normalize legacy values ('aktif', '0', '1', etc.)
        return IlanDurumu::normalize($raw);
    }

    public function getYayindamiAttribute(): bool
    {
        $val = $this->yayin_durumu ?? null;
        if ($val instanceof IlanDurumu) {
            return $val->isActive();
        }
        return in_array($val, ['aktif', IlanDurumu::YAYINDA->value, 'yayinda'], true);
    }

    /**
     * Context7 Canonical Field: aktiflik_durumu
     *
     * ⚠️ REMOVED: Accessor/mutator removed - aktiflik_durumu is now a PHYSICAL column (SAB Core v2.7)
     * Migration: 2026_05_23_140000_refactor_is_active_to_aktiflik_durumu_in_ilanlar_table.php
     *
     * aktiflik_durumu (integer 0/1) and yayin_durumu (enum) are SEPARATE fields:
     * - aktiflik_durumu: General active/inactive state (Context7 PRIMARY)
     * - yayin_durumu: Publication status (taslak/yayinda/pasif/arsiv/beklemede)
     *
     * DO NOT create accessor/mutator - let Eloquent handle the physical column directly.
     */

    /**
     * Compatibility layer for legacy 'status' insertions from systems that haven't adopted canonical schema
     *
     * @context7-ignore
     */
    public function setDurumAttribute($value)
    {
        $this->attributes['yayin_durumu'] = $value instanceof IlanDurumu ? $value->value : $value;
    }

    /**
     * Mutator for yayin_durumu — normalizes any legacy/integer value to canonical string.
     * 🛡️ Phase 8: State Authority Guard — prevents direct unauthorized updates.
     */
    public function setYayinDurumuAttribute($value): void
    {
        // 1. Authority Guard: Block direct write if not via YalihanLifecycle (only for existing models)
        if ($this->exists && ! \App\Services\Listing\YalihanLifecycle::$isAuthorized) {
            $current = $this->getOriginal('yayin_durumu');
            // Allow if values are same (idempotent assignment)
            if ($current !== $value && \App\Enums\IlanDurumu::normalize($current) !== \App\Enums\IlanDurumu::normalize($value)) {
                throw new \DomainException(
                    "İlan durumu (yayin_durumu) doğrudan değiştirilemez. Lütfen YalihanLifecycle otoritesini kullanın."
                );
            }
        }

        if ($value instanceof IlanDurumu) {
            $this->attributes['yayin_durumu'] = $value->value;
            return;
        }

        // Try canonical string first
        $enum = IlanDurumu::tryFrom((string) $value);
        if ($enum !== null) {
            $this->attributes['yayin_durumu'] = $enum->value;
            return;
        }

        // Normalize legacy values ('aktif', '0', '1', etc.)
        $normalized = IlanDurumu::normalize($value);
        $this->attributes['yayin_durumu'] = $normalized?->value ?? 'taslak'; // safe default
    }

    public function getDisplayOrderAttribute()
    {
        if (array_key_exists('display_order', $this->attributes)) {
            return $this->attributes['display_order'];
        }

        return null;
    }

    public function setDisplayOrderAttribute($value)
    {
        $this->attributes['display_order'] = $value;
    }

    /**
     * Frontend price rendering strategy text.
     */
    public function getFiyatGosterimMetniAttribute(): ?string
    {
        $mod = (string) ($this->attributes['fiyat_gosterim_modu'] ?? 'exact');
        $currency = (string) ($this->attributes['para_birimi'] ?? 'TRY');

        return match ($mod) {
            'hidden' => null,
            'on_request' => 'Fiyat için iletişime geçin',
            'starting_from' => !empty($this->attributes['baslangic_fiyati'])
                ? number_format((float) $this->attributes['baslangic_fiyati'], 0, ',', '.') . ' ' . $currency . "'den başlayan"
                : 'Fiyat için iletişime geçin',
            default => !empty($this->attributes['fiyat'])
                ? number_format((float) $this->attributes['fiyat'], 0, ',', '.') . ' ' . $currency
                : 'Fiyat için iletişime geçin',
        };
    }

    /**
     * Context7-Hybrid: Taslak durumu kontrolü
     *
     * @return bool İlan taslak ise true
     */
    public function getTaslakAttribute(): bool
    {
        return in_array($this->yayin_durumu, ['taslak', 'Draft'], true);
    }

    /**
     * Context7-Hybrid: AI işlem durumu
     *
     * @return bool AI tarafından işlendiyse true
     */
    public function getIslendiAttribute(): bool
    {
        // Check if AI processing fields are set
        return ! empty($this->aciklama) &&
            strlen($this->aciklama) > 50 &&
            ! is_null($this->slug);
    }

    /**
     * Context7-Hybrid: Drive klasör adı accessor
     * İlan detay sayfası ve listelerde 📂 butonu için kullanılır
     *
     * Format: YE-SAT-YALKVK-DAİRE-001234 - Yalıkavak - Daire - Ahmet Yılmaz
     */
    public function getDriveFolderNameAttribute(): string
    {
        return app(\App\Services\IlanReferansService::class)
            ->generateDriveFolderName($this);
    }

    /**
     * Kapak fotoğrafını döndürür.
     */
    public function getKapakFotografiAttribute()
    {
        return $this->fotograflar()->where('kapak_fotografi', true)->first() ?? $this->fotograflar()->first();
    }

    /**
     * Kısa referans numarası (Müşteri için - Frontend)
     *
     * Format: Son 3 hane, 0 ile doldurulmuş
     * Örnek: 001, 234, 567
     *
     * Gemini AI Önerisi: Müşteri tarafında kısa, danışman arama yapınca bulur
     * Context7: REFNOMAT İK Sistemi
     */
    public function getKisaReferansAttribute(): string
    {
        if (! $this->referans_no) {
            return '';
        }

        // YE-SAT-YALKVK-DAİRE-001234 → 234
        $parts = explode('-', $this->referans_no);
        $siraNo = end($parts);

        // Son 3 haneyi al ve 0 ile doldur
        return str_pad(substr($siraNo, -3), 3, '0', STR_PAD_LEFT);
        // Sonuç: 001, 234, 567
    }

    /**
     * Orta referans numarası (Danışman için - Hover/Tooltip)
     *
     * Format: Ref No: 001 Lokasyon Kategori Site (Mal Sahibi)
     * Örnek: Ref No: 001 Yalıkavak Satılık Daire Ülkerler Sitesi (Ahmet Yılmaz)
     *
     * Gemini AI Önerisi: Danışman hover'da görür, kopyalar
     * Yalıhan Bekçi: Frontend görünüm için optimize edilmiş format
     */
    public function getOrtaReferansAttribute(): string
    {
        $parts = [];

        // Kısa referans
        $parts[] = 'Ref No: '.$this->kisa_referans;

        // Lokasyon
        if ($this->mahalle && is_object($this->mahalle)) {
            $parts[] = $this->mahalle->mahalle_adi;
        } elseif ($this->ilce && is_object($this->ilce)) {
            $parts[] = $this->ilce->ilce_adi;
        }

        // Yayın Tipi
        if ($this->yayinTipi) {
            $parts[] = $this->yayinTipi->name;
        }

        if ($this->altKategori) {
            $parts[] = $this->altKategori->name;
        } elseif ($this->anaKategori) {
            $parts[] = $this->anaKategori->name;
        }

        // Site
        if ($this->site) {
            $parts[] = $this->site->name;
        }

        // Mal Sahibi (Parantez içinde)
        if ($this->ilanSahibi) {
            // Context7: Kisi model'de first_name/last_name kullanılmalı ama legacy 'ad/soyad' var
            $firstName = $this->ilanSahibi->first_name ?? $this->ilanSahibi->ad ?? '';
            $lastName = $this->ilanSahibi->last_name ?? $this->ilanSahibi->soyad ?? '';
            $sahip = trim($firstName.' '.$lastName);
            $parts[] = "({$sahip})";
        }

        return implode(' ', array_filter($parts));
    }

    /**
     * Uzun referans numarası (Sistem için - Dosya Adı)
     *
     * Format: Ref YE-SAT-YALKVK-DAİRE-001234 - Yalıkavak Satılık...
     *
     * Gemini AI Önerisi: Dosya oluşturma ve arşivleme için
     * Context7: REFNOMATİK tam format
     */
    public function getUzunReferansAttribute(): string
    {
        // Context7: dosya_adi legacy field, referans_no kullan
        return $this->referans_no ?? $this->dosya_adi ?? '';
    }

    /**
     * Tam adres metnini oluşturur.
     */
    public function getTamAdresAttribute(): string
    {
        $adresParcalari = [
            $this->mahalle->mahalle_adi ?? null,
            $this->ilce->ilce_adi ?? null,
            $this->il->il_adi ?? null,
            $this->ulke->ulke_adi ?? null,
        ];

        return implode(', ', array_filter($adresParcalari));
    }

    /**
     * Owner private data (encrypted JSON)
     * { desired_price_min, desired_price_max, notes }
     */
    public function getOwnerPrivateDataAttribute(): array
    {
        $enc = $this->owner_private_encrypted ?? null;
        if (! $enc) {
            return [];
        }
        /** @sab-ignore-catch */
        try {
            $json = Crypt::decryptString($enc);
            $arr = json_decode($json, true);

            return is_array($arr) ? $arr : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function setOwnerPrivateDataAttribute($value): void
    {
        /** @sab-ignore-catch */
        try {
            $json = json_encode($value ?? [], JSON_UNESCAPED_UNICODE);
            $this->attributes['owner_private_encrypted'] = Crypt::encryptString($json);
        } catch (\Throwable $e) {
            $this->attributes['owner_private_encrypted'] = null;
        }
    }

    /**
     * Alanın yerelleştirilmiş değerini döndürür.
     */
    public function getLocalized(string $field): string
    {
        return app(\App\Services\AITranslation\TranslationFallbackService::class)->getLocalized($this, $field);
    }
}
