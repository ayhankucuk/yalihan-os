<?php

declare(strict_types=1);

namespace App\Domain\Ilan\Policies;

use App\Domain\Ilan\ValueObjects\FieldDefinition;
use App\Domain\Ilan\ValueObjects\FieldKey;
use App\Domain\Ilan\ValueObjects\ValidationRule;

/**
 * CategoryFieldPolicy — Pure Domain Policy for Category & Publication Type Form Rules
 *
 * Single Source of Truth (SSOT) for form field requirements, allowed fields,
 * and category-level validation rules without any DB or Eloquent dependency.
 */
final class CategoryFieldPolicy
{
    /**
     * Get all canonical field definitions for a category and publication type.
     *
     * @return FieldDefinition[]
     */
    public function getFieldDefinitions(string $kategoriSlug, string $yayinTipi): array
    {
        $kategoriSlug = $this->normalizeCategorySlug($kategoriSlug);
        $yayinTipi = strtolower(trim($yayinTipi));

        return match ("{$kategoriSlug}:{$yayinTipi}") {
            'konut:satilik' => $this->konutSatilik(),
            'konut:kiralik' => $this->konutKiralik(),
            'arsa-arazi:satilik' => $this->arsaSatilik(),
            'isyeri:satilik' => $this->isyeriSatilik(),
            'isyeri:kiralik' => $this->isyeriKiralik(),
            'yazlik-kiralama:gunluk' => $this->yazlikGunluk(),
            default => $this->fallbackFields($kategoriSlug, $yayinTipi),
        };
    }

    /**
     * Check if a specific field is required for a category and publication type.
     */
    public function isRequired(string $kategoriSlug, string $yayinTipi, FieldKey $key): bool
    {
        $fields = $this->getFieldDefinitions($kategoriSlug, $yayinTipi);
        foreach ($fields as $field) {
            if ($field->key()->equals($key)) {
                return $field->rule()->isRequired();
            }
        }

        return false;
    }

    /**
     * Check if a field is allowed for a given category & publication type.
     */
    public function isAllowed(string $kategoriSlug, string $yayinTipi, FieldKey $key): bool
    {
        $fields = $this->getFieldDefinitions($kategoriSlug, $yayinTipi);
        foreach ($fields as $field) {
            if ($field->key()->equals($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate listing payload against policy rules.
     * Returns an array of error messages (empty if valid).
     *
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public function validateListingData(string $kategoriSlug, string $yayinTipi, array $data): array
    {
        $errors = [];
        $definitions = $this->getFieldDefinitions($kategoriSlug, $yayinTipi);

        foreach ($definitions as $definition) {
            $keyStr = $definition->key()->value();
            $val = $data[$keyStr] ?? null;

            if ($definition->rule()->isRequired() && ($val === null || $val === '')) {
                $errors[$keyStr] = "{$definition->name()} alanı zorunludur.";
                continue;
            }

            if ($val !== null && $val !== '' && !$definition->rule()->isValid($val)) {
                $errors[$keyStr] = "{$definition->name()} değeri geçerli bir {$definition->rule()->type()} formatında değil.";
            }
        }

        return $errors;
    }

    private function normalizeCategorySlug(string $slug): string
    {
        $s = strtolower(trim($slug));

        return match ($s) {
            'arsa' => 'arsa-arazi',
            'isyeri', 'ticari' => 'isyeri',
            'yazlik' => 'yazlik-kiralama',
            'turistik' => 'turistik-tesisler',
            'proje' => 'projeden-satis',
            default => $s,
        };
    }

    /**
     * 🏠 Konut — Satılık
     *
     * @return FieldDefinition[]
     */
    private function konutSatilik(): array
    {
        return [
            new FieldDefinition(
                FieldKey::fromString('oda_sayisi'),
                'Oda Sayısı',
                'temel',
                ValidationRule::create('select', true, ['1+0', '1+1', '2+1', '2+2', '3+1', '3+2', '4+1', '4+2', '5+1', '5+2', '6+', '7+']),
                '🛏️',
                10,
                true,
                true,
                true
            ),
            new FieldDefinition(
                FieldKey::fromString('banyo_sayisi'),
                'Banyo Sayısı',
                'temel',
                ValidationRule::create('select', true, ['1', '2', '3', '4', '5+']),
                '🚿',
                20,
                true,
                true,
                true
            ),
            new FieldDefinition(
                FieldKey::fromString('net_m2'),
                'Net m²',
                'fiziksel',
                ValidationRule::create('number', true, [], 10, 2000, 1, 'm²'),
                '📐',
                30,
                true,
                true,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('brut_m2'),
                'Brüt m²',
                'fiziksel',
                ValidationRule::create('number', false, [], 10, 3000, 1, 'm²'),
                '📏',
                40,
                true,
                false,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('bulundugu_kat'),
                'Bulunduğu Kat',
                'fiziksel',
                ValidationRule::create('select', true, ['Bodrum Kat', 'Zemin Kat', 'Giriş Katı', 'Bahçe Katı', '1', '2', '3', '4', '5', 'Villa Tipi', 'Müstakil']),
                '🏢',
                50,
                true,
                true,
                true
            ),
            new FieldDefinition(
                FieldKey::fromString('bina_yasi'),
                'Bina Yaşı',
                'fiziksel',
                ValidationRule::create('select', false, ['0 (Yeni)', '1-5', '6-10', '11-15', '16-20', '21+']),
                '🏗️',
                60,
                true,
                false,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('tapu_durumu'),
                'Tapu Durumu',
                'hukuki',
                ValidationRule::create('select', false, ['Kat Mülkiyetli', 'Kat İrtifaklı', 'Müstakil Parsel', 'Hisseli']),
                '📜',
                70,
                false,
                false,
                false
            ),
        ];
    }

    /**
     * 🏠 Konut — Kiralık
     *
     * @return FieldDefinition[]
     */
    private function konutKiralik(): array
    {
        return [
            new FieldDefinition(
                FieldKey::fromString('oda_sayisi'),
                'Oda Sayısı',
                'temel',
                ValidationRule::create('select', true, ['1+0', '1+1', '2+1', '2+2', '3+1', '3+2', '4+1', '4+2', '5+1', '5+2', '6+']),
                '🛏️',
                10,
                true,
                true,
                true
            ),
            new FieldDefinition(
                FieldKey::fromString('banyo_sayisi'),
                'Banyo Sayısı',
                'temel',
                ValidationRule::create('select', true, ['1', '2', '3', '4', '5+']),
                '🚿',
                20,
                true,
                true,
                true
            ),
            new FieldDefinition(
                FieldKey::fromString('net_m2'),
                'Net m²',
                'fiziksel',
                ValidationRule::create('number', true, [], 10, 2000, 1, 'm²'),
                '📐',
                30,
                true,
                true,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('aidat_tutari'),
                'Aidat',
                'finansal',
                ValidationRule::create('currency', false, [], 0, null, 1, 'TRY'),
                '💵',
                40,
                false,
                false,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('depozito_tutari'),
                'Depozito',
                'finansal',
                ValidationRule::create('currency', false, [], 0, null, 1, 'TRY'),
                '🔒',
                50,
                false,
                false,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('esyali_durumu'),
                'Eşyalı',
                'fiziksel',
                ValidationRule::create('select', false, ['Eşyalı', 'Boş / Eşyasız', 'Kısmi Eşyalı']),
                '🛋️',
                60,
                true,
                true,
                false
            ),
        ];
    }

    /**
     * 🏞️ Arsa & Arazi — Satılık
     *
     * @return FieldDefinition[]
     */
    private function arsaSatilik(): array
    {
        return [
            new FieldDefinition(
                FieldKey::fromString('net_m2'),
                'Arsa Alanı (m²)',
                'temel',
                ValidationRule::create('number', true, [], 50, 1000000, 1, 'm²'),
                '📐',
                10,
                true,
                true,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('imar_durumu'),
                'İmar Durumu',
                'hukuki',
                ValidationRule::create('select', true, ['Konut İmarlı', 'Ticari İmarlı', 'Turizm İmarlı', 'Sanayi İmarlı', 'Tarla / İmarsız', 'Zeytinlik', 'Bağ/Bahçe']),
                '📋',
                20,
                true,
                true,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('ada_no'),
                'Ada No',
                'hukuki',
                ValidationRule::create('text', false),
                '📍',
                30,
                false,
                false,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('parsel_no'),
                'Parsel No',
                'hukuki',
                ValidationRule::create('text', false),
                '📍',
                40,
                false,
                false,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('kaks'),
                'KAKS (Emsal)',
                'hukuki',
                ValidationRule::create('number', false, [], 0, 5, null),
                '📊',
                50,
                false,
                false,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('taks'),
                'TAKS',
                'hukuki',
                ValidationRule::create('number', false, [], 0, 1, null),
                '📊',
                60,
                false,
                false,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('yola_cephe'),
                'Yola Cepheli',
                'altyapi',
                ValidationRule::create('boolean', false),
                '🛣️',
                70,
                true,
                false,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('altyapi_elektrik'),
                'Elektrik Altyapısı',
                'altyapi',
                ValidationRule::create('boolean', false),
                '⚡',
                80,
                false,
                false,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('altyapi_su'),
                'Su Altyapısı',
                'altyapi',
                ValidationRule::create('boolean', false),
                '💧',
                90,
                false,
                false,
                false
            ),
        ];
    }

    /**
     * 🏢 İşyeri — Satılık
     *
     * @return FieldDefinition[]
     */
    private function isyeriSatilik(): array
    {
        return [
            new FieldDefinition(
                FieldKey::fromString('isyeri_turu'),
                'İşyeri Türü',
                'temel',
                ValidationRule::create('select', true, ['Ofis / Büro', 'Dükkan / Mağaza', 'Depo / Antrepo', 'Fabrika / İmalathane', 'Plaza Katı', 'Komple Bina']),
                '🏢',
                10,
                true,
                true,
                true
            ),
            new FieldDefinition(
                FieldKey::fromString('net_m2'),
                'Net m²',
                'fiziksel',
                ValidationRule::create('number', true, [], 10, 50000, 1, 'm²'),
                '📐',
                20,
                true,
                true,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('bulundugu_kat'),
                'Bulunduğu Kat',
                'fiziksel',
                ValidationRule::create('select', false, ['Bodrum Kat', 'Zemin Kat', 'Giriş / Düzayak', 'Asma Kat', '1', '2', '3', '4', 'Plaza Katı', 'Komple Bina']),
                '🏢',
                30,
                true,
                false,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('tapu_durumu'),
                'Tapu Durumu',
                'hukuki',
                ValidationRule::create('select', false, ['Kat Mülkiyetli', 'Kat İrtifaklı', 'Müstakil Parsel', 'Hisseli']),
                '📜',
                40,
                false,
                false,
                false
            ),
        ];
    }

    /**
     * 🏢 İşyeri — Kiralık
     *
     * @return FieldDefinition[]
     */
    private function isyeriKiralik(): array
    {
        return [
            new FieldDefinition(
                FieldKey::fromString('isyeri_turu'),
                'İşyeri Türü',
                'temel',
                ValidationRule::create('select', true, ['Ofis / Büro', 'Dükkan / Mağaza', 'Depo / Antrepo', 'Fabrika / İmalathane', 'Plaza Katı', 'Komple Bina']),
                '🏢',
                10,
                true,
                true,
                true
            ),
            new FieldDefinition(
                FieldKey::fromString('net_m2'),
                'Net m²',
                'fiziksel',
                ValidationRule::create('number', true, [], 10, 50000, 1, 'm²'),
                '📐',
                20,
                true,
                true,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('aidat_tutari'),
                'Aidat',
                'finansal',
                ValidationRule::create('currency', false, [], 0, null, 1, 'TRY'),
                '💵',
                30,
                false,
                false,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('depozito_tutari'),
                'Depozito',
                'finansal',
                ValidationRule::create('currency', false, [], 0, null, 1, 'TRY'),
                '🔒',
                40,
                false,
                false,
                false
            ),
        ];
    }

    /**
     * 🏖️ Yazlık Kiralama — Günlük / Sezonluk
     *
     * @return FieldDefinition[]
     */
    private function yazlikGunluk(): array
    {
        return [
            new FieldDefinition(
                FieldKey::fromString('oda_sayisi'),
                'Oda Sayısı',
                'temel',
                ValidationRule::create('select', true, ['1+0', '1+1', '2+1', '2+2', '3+1', '3+2', '4+1', '4+2', '5+1', '6+']),
                '🛏️',
                10,
                true,
                true,
                true
            ),
            new FieldDefinition(
                FieldKey::fromString('banyo_sayisi'),
                'Banyo Sayısı',
                'temel',
                ValidationRule::create('select', true, ['1', '2', '3', '4', '5+']),
                '🚿',
                20,
                true,
                true,
                true
            ),
            new FieldDefinition(
                FieldKey::fromString('max_misafir_sayisi'),
                'Maksimum Misafir',
                'kiralama',
                ValidationRule::create('number', true, [], 1, 30, 1, 'kişi'),
                '👥',
                30,
                true,
                true,
                true
            ),
            new FieldDefinition(
                FieldKey::fromString('min_konaklama_gece'),
                'Min. Konaklama',
                'kiralama',
                ValidationRule::create('number', false, [], 1, 90, 1, 'gece'),
                '🌙',
                40,
                true,
                false,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('denize_mesafe_m'),
                'Denize Mesafe (m)',
                'kiralama',
                ValidationRule::create('number', false, [], 0, 50000, 10, 'm'),
                '🏖️',
                50,
                true,
                true,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('havuz_tipi'),
                'Havuz Türü',
                'kiralama',
                ValidationRule::create('select', false, ['Özel Havuzlu', 'Ortak Havuzlu', 'Havuzsuz', 'Sonsuzluk Havuzu']),
                '🏊‍♂️',
                60,
                true,
                true,
                false
            ),
            new FieldDefinition(
                FieldKey::fromString('temizlik_ucreti'),
                'Temizlik Ücreti',
                'finansal',
                ValidationRule::create('currency', false, [], 0, null, 1, 'TRY'),
                '🧹',
                70,
                false,
                false,
                false
            ),
        ];
    }

    /**
     * Fallback minimal definitions.
     *
     * @return FieldDefinition[]
     */
    private function fallbackFields(string $kategoriSlug, string $yayinTipi): array
    {
        return [
            new FieldDefinition(
                FieldKey::fromString('net_m2'),
                'Net Alan (m²)',
                'temel',
                ValidationRule::create('number', false, [], 1, 100000, 1, 'm²'),
                '📐',
                10,
                true,
                true,
                false
            ),
        ];
    }
}
