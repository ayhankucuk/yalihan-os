<?php

namespace App\Domain\Workforce\Support;

use App\Models\FeaturePack;
use App\Models\Ilan;
use App\Models\Ozellik;

/**
 * MissingFieldDetector — Sprint 7.2 Phase 2
 *
 * Bir ilandaki eksik zorunlu alanları tespit eder.
 * Feature Pack'e göre değerlendirme yapar.
 */
class MissingFieldDetector
{
    /**
     * İlandaki eksik alanları tespit eder.
     *
     * @return array<array{field: string, label: string, severity: string, reason: string}>
     */
    public function detect(Ilan $ilan, ?FeaturePack $pack = null): array
    {
        $missing = [];

        // Pack'ten gerekli alanları al
        $requiredFields = $this->getRequiredFields($ilan, $pack);

        // İlanın mevcut alanlarını kontrol et
        $ilanArray = $ilan->toArray();

        foreach ($requiredFields as $field => $label) {
            $value = $ilanArray[$field] ?? null;

            // Boş veya null kontrolü
            $isEmpty = $value === null
                || $value === ''
                || (is_string($value) && trim($value) === '');

            if ($isEmpty) {
                $severity = $this->getSeverity($field, $ilan);
                $missing[] = [
                    'field' => $field,
                    'label' => $label,
                    'severity' => $severity,
                    'reason' => $this->getReason($field, $ilan),
                ];
            }
        }

        return $missing;
    }

    /**
     * Pack veya kategori bazlı zorunlu alanları al.
     *
     * @return array<string, string> field_slug => label
     */
    private function getRequiredFields(Ilan $ilan, ?FeaturePack $pack): array
    {
        // Pack varsa pack'ten al
        if ($pack) {
            $fields = [];
            foreach ($pack->items()->with('ozellik')->get() as $item) {
                if ($item->ozellik) {
                    $fields[$item->ozellik->slug] = $item->ozellik->name;
                }
            }
            return $fields;
        }

        // Yoksa kategori/yayın_tipi bazlıKategoriYayinTipiFieldDependency'den al
        return $this->getDefaultRequiredFields($ilan);
    }

    /**
     * Varsayılan zorunlu alanları getir.
     *
     * @return array<string, string>
     */
    private function getDefaultRequiredFields(Ilan $ilan): array
    {
        $deps = \App\Models\KategoriYayinTipiFieldDependency::where('aktiflik_durumu', 1)
            ->where('required', true)
            ->where(function ($q) use ($ilan) {
                $q->where('kategori_slug', mb_strtolower($ilan->kategori ?? ''))
                    ->orWhereNull('kategori_slug');
            })
            ->get();

        if ($deps->isEmpty()) {
            // Fallback: temel alanlar
            return [
                'baslik' => 'Başlık',
                'fiyat' => 'Fiyat',
                'brut_m2' => 'Brüt m²',
                'kategori' => 'Kategori',
                'yayin_tipi' => 'Yayın Tipi',
            ];
        }

        $fields = [];
        foreach ($deps as $dep) {
            $fields[$dep->field_slug] = $dep->field_name;
        }

        return $fields;
    }

    /**
     * Alan için kritiklik seviyesi belirle.
     */
    private function getSeverity(string $field, Ilan $ilan): string
    {
        $yayinlamaBlokeleyen = ['baslik', 'fiyat', 'brut_m2', 'kategori', 'yayin_tipi'];

        if (in_array($field, $yayinlamaBlokeleyen)) {
            return 'blocking';
        }

        if (in_array($field, ['adres', 'lat', 'lng', 'ozellikler'])) {
            return 'advisory';
        }

        return 'optional';
    }

    /**
     * Eksik alan için insan tarafından okunabilir açıklama.
     */
    private function getReason(string $field, Ilan $ilan): string
    {
        return match ($field) {
            'baslik' => 'İlan başlığı boş bırakılamaz.',
            'fiyat' => 'Fiyat bilgisi zorunludur.',
            'brut_m2' => 'Brüt metrekare bilgisi gereklidir.',
            'kategori' => 'Kategori seçimi zorunludur.',
            'yayin_tipi' => 'Yayın tipi (Satılık/Kiralık) seçimi gereklidir.',
            'adres' => 'İlan adresi ekspertiz ve SEO için önerilir.',
            'lat', 'lng' => 'Koordinat bilgisi harita gösterimi için gereklidir.',
            'ozellikler' => 'Özellik eklenmemiş — ilan zayıf görünür.',
            default => "{$field} alanı boş bırakılmış.",
        };
    }
}
