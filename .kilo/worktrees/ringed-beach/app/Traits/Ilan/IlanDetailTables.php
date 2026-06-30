<?php

namespace App\Traits\Ilan;

use Illuminate\Support\Facades\Log;

trait IlanDetailTables
{
    /**
     * AUTO-DETAILER: Ensure detail table records exist
     *
     * Context7: Prevents Cortex AI from returning 0% by guaranteeing
     * that every listing has its category-specific detail record.
     *
     * This method is called automatically after save() in IlanCrudService.
     */
    public function ensureDetailTableExists(): void
    {
        if (! $this->id || ! $this->anaKategori) {
            return; // Cannot create detail without ID and category
        }

        $kategoriSlug = strtolower($this->anaKategori->slug ?? '');

        // Yazlık kategorisi için
        if ($kategoriSlug === 'yazlık' || $kategoriSlug === 'yazlik') {
            // IlanTurizmDetail (primary detail table)
            $this->turizmDetail()->firstOrCreate(
                ['ilan_id' => $this->id],
                [
                    'check_in_saati' => $this->check_in_time ?? '14:00',
                    'check_out_saati' => $this->check_out_time ?? '11:00',
                    'min_konaklama' => $this->minimum_stay ?? 1,
                    'max_misafir' => $this->max_guests ?? 2,
                    'gunluk_fiyat' => $this->fiyat ?? 0,
                    'temizlik_ucreti' => $this->cleaning_fee ?? 0,
                    'havuz_var' => false,
                ]
            );

            // YazlikDetail (legacy support)
            $this->yazlikDetail()->firstOrCreate(
                ['ilan_id' => $this->id],
                [
                    'min_konaklama' => $this->minimum_stay ?? 1,
                    'max_misafir' => $this->max_guests ?? 2,
                    'temizlik_ucreti' => $this->cleaning_fee ?? 0,
                    'havuz' => false,
                    'gunluk_fiyat' => $this->fiyat ?? 0,
                ]
            );
        }

        // Arsa kategorisi için (prefix match — arsa, arsa-arazi, vb.)
        if (str_starts_with($kategoriSlug, 'arsa')) {
            $this->arsaDetail()->firstOrCreate(
                ['ilan_id' => $this->id],
                [
                    'ada_no' => null,
                    'parsel_no' => null,
                    'imar_durumu' => 'Belirsiz',
                    'kaks' => 0,
                    'taks' => 0,
                ]
            );
        }

        // Ticari kategorisi için (future-proof)
        if ($kategoriSlug === 'ticari' || $kategoriSlug === 'isyeri') {
            // IlanTicariDetail ilişkisi varsa
            // context7-ignore
            if (method_exists($this, 'ticariDetail')) {
                $this->ticariDetail()->firstOrCreate(
                    ['ilan_id' => $this->id],
                    [
                        'isyeri_tipi' => null,
                        'kira_bilgisi' => null,
                    ]
                );
            }
        }
    }

    // ======================================================================
    // HYBRID FIELD BRIDGES (TR ↔ EN) - Backward compatible accessors/mutators
    // ======================================================================

    // minimum_stay ↔ min_konaklama
    public function getMinimumStayAttribute()
    {
        return $this->attributes['minimum_stay'] ?? $this->attributes['min_konaklama'] ?? 1;
    }

    public function setMinimumStayAttribute($value): void
    {
        $this->attributes['minimum_stay'] = $value;
    }

    public function setMinKonaklamaAttribute($value): void
    {
        $this->attributes['minimum_stay'] = $value;
    }

    // check_in_time ↔ check_in_saati
    public function getCheckInTimeAttribute()
    {
        return $this->attributes['check_in_time'] ?? $this->attributes['check_in_saati'] ?? '14:00';
    }

    public function setCheckInTimeAttribute($value): void
    {
        $this->attributes['check_in_time'] = $value;
    }

    public function setCheckInSaatiAttribute($value): void
    {
        $this->attributes['check_in_time'] = $value;
    }

    // check_out_time ↔ check_out_saati
    public function getCheckOutTimeAttribute()
    {
        return $this->attributes['check_out_time'] ?? $this->attributes['check_out_saati'] ?? '11:00';
    }

    public function setCheckOutTimeAttribute($value): void
    {
        $this->attributes['check_out_time'] = $value;
    }

    public function setCheckOutSaatiAttribute($value): void
    {
        $this->attributes['check_out_time'] = $value;
    }

    // max_guests ↔ max_misafir
    public function getMaxGuestsAttribute()
    {
        return $this->attributes['max_guests'] ?? $this->attributes['max_misafir'] ?? null;
    }

    public function setMaxGuestsAttribute($value): void
    {
        $this->attributes['max_guests'] = $value;
    }

    public function setMaxMisafirAttribute($value): void
    {
        $this->attributes['max_guests'] = $value;
        Log::warning('Context7: Turkish model field set (max_misafir). Mapped to max_guests.', [
            'model' => 'Ilan', 'id' => $this->attributes['id'] ?? null
        ]);
    }

    // cleaning_fee ↔ temizlik_ucreti
    public function getCleaningFeeAttribute()
    {
        return $this->attributes['cleaning_fee'] ?? $this->attributes['temizlik_ucreti'] ?? 0;
    }

    public function setCleaningFeeAttribute($value): void
    {
        $this->attributes['cleaning_fee'] = $value;
    }

    public function setTemizlikUcretiAttribute($value): void
    {
        $this->attributes['cleaning_fee'] = $value;
        Log::warning('Context7: Turkish model field set (temizlik_ucreti). Mapped to cleaning_fee.', [
            'model' => 'Ilan', 'id' => $this->attributes['id'] ?? null
        ]);
    }

    // maximum_stay → max_stay_nights (API alias → DB column)
    public function getMaximumStayAttribute()
    {
        return $this->max_stay_nights;
    }

    public function setMaximumStayAttribute($value): void
    {
        $this->attributes['max_stay_nights'] = $value;
    }

    // cancellation_policy ↔ iptal_politikasi
    public function getCancellationPolicyAttribute()
    {
        $v = $this->getAttributeFromArray('cancellation_policy') ?? $this->attributes['cancellation_policy'] ?? null;

        $legacyVal = $this->getAttributeFromArray('iptal_politikasi') ?? $this->attributes['iptal_politikasi'] ?? null;
        return $v !== null ? $v : $legacyVal;
    }

    public function setCancellationPolicyAttribute($value): void
    {
        $this->attributes['cancellation_policy'] = $value;
        $this->attributes['iptal_politikasi'] = $value;
    }

    public function setIptalPolitikasiAttribute($value): void
    {
        $this->attributes['iptal_politikasi'] = $value;
        $this->attributes['cancellation_policy'] = $value;
        Log::warning('Context7: Turkish model field set (iptal_politikasi). Prefer cancellation_policy.', [
            'model' => 'Ilan', 'id' => $this->attributes['id'] ?? null
        ]);
    }
}
