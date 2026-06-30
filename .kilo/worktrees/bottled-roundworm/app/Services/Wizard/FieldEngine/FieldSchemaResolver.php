<?php

namespace App\Services\Wizard\FieldEngine;

use App\Models\KategoriYayinTipiFieldDependency;
use Illuminate\Support\Collection;

/**
 * FieldSchemaResolver — DB şemasını temiz DTO/Array yapısına çözer.
 * 
 * Veritabanındaki kolon isimleri (field_slug, required vb.) ile 
 * Wizard Engine'in beklediği isimler arasındakı köprüyü kurar.
 */
class FieldSchemaResolver
{
    /**
     * Kategori bazlı alan tanımlarını çeker ve normalize eder.
     *
     * @param string $categorySlug
     * @return array
     */
    public function resolveByCategory(string $categorySlug): array
    {
        $rows = KategoriYayinTipiFieldDependency::query()
            ->where('kategori_slug', $categorySlug)
            ->where('aktiflik_durumu', 1)
            ->orderBy('display_order')
            ->get();

        return $this->normalize($rows);
    }

    /**
     * Kategori ve Yayın Tipi bazlı spesifik şemayı çeker.
     *
     * @param string $categorySlug
     * @param string $yayinTipi
     * @return array
     */
    public function resolveByContext(string $categorySlug, string $yayinTipi): array
    {
        $rows = KategoriYayinTipiFieldDependency::query()
            ->where('kategori_slug', $categorySlug)
            ->where('yayin_tipi', $yayinTipi)
            ->where('aktiflik_durumu', 1)
            ->orderBy('display_order')
            ->get();

        return $this->normalize($rows);
    }

    /**
     * DB sonuçlarını motorun beklediği formata çevirir.
     *
     * @param Collection $rows
     * @return array
     */
    protected function normalize(Collection $rows): array
    {
        return $rows->map(function ($row) {
            return [
                'name'         => $row->field_slug, // DB: field_slug
                'label'        => $row->field_name,
                'type'         => $row->field_type ?? 'text', // context7-ignore
                'category'     => $row->field_category,
                'unit'         => $row->field_unit,
                'is_required'  => (bool) ($row->required ?? false), // DB: required
                'sort_order'   => (int) ($row->display_order ?? 0), // DB: display_order
                'options'      => $this->parseJson($row->field_options),
                'dependencies' => $this->parseJson($row->dependencies),
                'ai' => [
                    'auto_fill'  => (bool) $row->ai_auto_fill,
                    'suggestion' => (bool) $row->ai_suggestion,
                    'prompt_key' => $row->ai_prompt_key,
                ]
            ];
        })->values()->all();
    }

    /**
     * JSON kolonlarını güvenli bir şekilde diziye çevirir.
     */
    protected function parseJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (empty($value)) {
            return [];
        }

        return json_decode($value, true) ?: [];
    }
}
