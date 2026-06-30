/**
 * Category Presets — SSOT for Wizard Template Requirements
 *
 * Each preset defines:
 * - required_ui_ipuclari: fields that MUST be in ui_ipuclari
 * - expected_features_min: minimum feature_schema count
 * - expected_template_min: minimum template.fields count
 * - expected_required_keys: base fields the template.required should include
 */

export const CATEGORY_PRESETS = {
    arsa: {
        kategori_id: 3,
        label: 'Arsa & Arazi',
        alias_ids: [15, 16, 17, 18, 19, 20, 21, 22],
        expected_template_min: 7,
        expected_features_min: 3,
        expected_required_keys: ['baslik', 'fiyat'],
        category_whitelist_slugs: ['altyapi', 'tapu', 'muhit', 'ulasim', 'arsa-ozellikleri'],
        required_ui_ipuclari: [
            {
                slug: 'arsa_tipi',
                label: 'Arsa Tipi',
                hint: 'Arsanızın tipini seçin',
                placeholder: 'Arsa tipini seçiniz',
            },
            {
                slug: 'imar_durumu',
                label: 'İmar Durumu',
                hint: 'Arsanızın imar durumunu belirtiniz',
                placeholder: 'İmar durumunu seçiniz',
            },
            {
                slug: 'tapu_durumu',
                label: 'Tapu Durumu',
                hint: 'Tapu tipini seçiniz',
                placeholder: 'Tapu durumunu seçiniz',
            },
            {
                slug: 'ada_no',
                label: 'Ada No',
                hint: 'Arsanızın bulunduğu adanın numarasını giriniz',
                placeholder: 'Örn: 123',
            },
            {
                slug: 'parsel_no',
                label: 'Parsel No',
                hint: 'Arsanızın parsel numarasını giriniz',
                placeholder: 'Örn: 45',
            },
            {
                slug: 'kaks',
                label: 'KAKS',
                hint: 'Kat Alanı Kat Sayısı oranı',
                placeholder: 'Örn: 1.20',
            },
            {
                slug: 'gabari',
                label: 'Gabari',
                hint: 'Maksimum bina yüksekliği',
                placeholder: 'Örn: 12.50',
                birim: 'metre',
            },
        ],
    },
    konut: {
        kategori_id: 1,
        label: 'Konut',
        alias_ids: [7, 8, 9, 10],
        expected_template_min: 7,
        expected_features_min: 5,
        expected_required_keys: ['baslik', 'fiyat'],
        category_whitelist_slugs: [
            'ic-ozellikler',
            'banyo',
            'mutfak',
            'dis-ozellikler',
            'bahce',
            'otopark',
            'guvenlik',
            'muhit',
            'ulasim',
        ],
        required_ui_ipuclari: [
            {
                slug: 'oda_sayisi',
                label: 'Oda Sayısı',
                hint: 'Konutun oda sayısını seçiniz',
                placeholder: 'Oda sayısını seçiniz',
            },
            {
                slug: 'banyo_sayisi',
                label: 'Banyo Sayısı',
                hint: 'Banyoyu sayısını giriniz',
                placeholder: 'Örn: 1',
            },
            {
                slug: 'net_m2',
                label: 'Net m²',
                hint: 'Konutun net kullanım alanı',
                placeholder: 'Örn: 120',
                birim: 'm²',
            },
            {
                slug: 'brut_m2',
                label: 'Brüt m²',
                hint: 'Konutun brüt alanı',
                placeholder: 'Örn: 150',
                birim: 'm²',
            },
            {
                slug: 'kat_no',
                label: 'Kat No',
                hint: 'Konutun bulunduğu kat',
                placeholder: 'Örn: 3',
            },
            {
                slug: 'bina_yasi',
                label: 'Bina Yaşı',
                hint: 'Binanın yapım yılı veya yaşı',
                placeholder: 'Örn: 5',
            },
            {
                slug: 'isitma_tipi',
                label: 'Isıtma Tipi',
                hint: 'Konutun ısıtma sistemini seçiniz',
                placeholder: 'Isıtma tipini seçiniz',
            },
        ],
    },
    isyeri: {
        kategori_id: 2,
        label: 'İşyeri',
        alias_ids: [11, 12, 13, 14],
        expected_template_min: 5,
        expected_features_min: 3,
        expected_required_keys: ['baslik', 'fiyat'],
        category_whitelist_slugs: [
            'ic-ozellikler',
            'dis-ozellikler',
            'otopark',
            'guvenlik',
            'muhit',
            'ulasim',
            'altyapi',
        ],
        required_ui_ipuclari: [
            {
                slug: 'net_m2',
                label: 'Net m²',
                hint: 'İşyerinin net kullanım alanı',
                placeholder: 'Örn: 80',
                birim: 'm²',
            },
            {
                slug: 'brut_m2',
                label: 'Brüt m²',
                hint: 'İşyerinin brüt alanı',
                placeholder: 'Örn: 100',
                birim: 'm²',
            },
            {
                slug: 'kat_no',
                label: 'Kat No',
                hint: 'İşyerinin bulunduğu kat',
                placeholder: 'Örn: Zemin',
            },
            {
                slug: 'bina_yasi',
                label: 'Bina Yaşı',
                hint: 'Binanın yapım yılı veya yaşı',
                placeholder: 'Örn: 10',
            },
            {
                slug: 'isitma_tipi',
                label: 'Isıtma Tipi',
                hint: 'İşyerinin ısıtma sistemi',
                placeholder: 'Isıtma tipini seçiniz',
            },
        ],
    },
    yazlik: {
        kategori_id: 4,
        label: 'Yazlık Kiralama',
        alias_ids: [26, 27, 28, 29, 30, 31],
        expected_template_min: 7,
        expected_features_min: 3,
        expected_required_keys: ['baslik', 'fiyat', 'gunluk_fiyat', 'minimum_konaklama'],
        category_whitelist_slugs: [
            'ic-ozellikler',
            'banyo',
            'mutfak',
            'dis-ozellikler',
            'bahce',
            'otopark',
            'guvenlik',
            'muhit',
            'ulasim',
        ],
        required_ui_ipuclari: [
            {
                slug: 'gunluk_fiyat',
                label: 'Günlük Fiyat',
                hint: 'Günlük kiralama bedelini giriniz',
                placeholder: 'Örn: 2500',
                birim: 'TL',
            },
            {
                slug: 'minimum_konaklama',
                label: 'Min. Konaklama',
                hint: 'Minimum konaklama süresini belirtin',
                placeholder: 'Örn: 3',
                birim: 'Gün',
            },
            {
                slug: 'depozito',
                label: 'Depozito',
                hint: 'Hasar depozitosu tutarı',
                placeholder: 'Örn: 5000',
                birim: 'TL',
            },
            {
                slug: 'temizlik_ucreti',
                label: 'Temizlik Ücreti',
                hint: 'Tek seferlik temizlik bedeli',
                placeholder: 'Örn: 750',
                birim: 'TL',
            },
            {
                slug: 'maksimum_misafir',
                label: 'Maks. Misafir',
                hint: 'Konaklayabilecek maksimum kişi sayısı',
                placeholder: 'Örn: 6',
            },
            {
                slug: 'oda_sayisi',
                label: 'Oda Sayısı',
                hint: 'Konutun oda sayısını seçiniz',
                placeholder: 'Oda sayısını seçiniz',
            },
            {
                slug: 'havuz_tipi',
                label: 'Havuz Tipi',
                hint: 'Havuz özelliğini belirtin',
                placeholder: 'Örn: Özel Havuzlu',
            },
        ],
    },
    turistik_tesisler: {
        kategori_id: 5,
        label: 'Turistik Tesisler',
        alias_ids: [32, 33, 34],
        expected_template_min: 5,
        expected_features_min: 3,
        expected_required_keys: ['baslik', 'fiyat'],
        category_whitelist_slugs: ['ic-ozellikler', 'dis-ozellikler', 'muhit', 'ulasim', 'altyapi'],
        required_ui_ipuclari: [
            {
                slug: 'yildiz_sayisi',
                label: 'Yıldız Sayısı',
                hint: 'Tesisisin yıldız sayısını giriniz',
                placeholder: 'Örn: 5',
            },
            {
                slug: 'oda_sayisi',
                label: 'Toplam Oda',
                hint: 'Toplam oda sayısını belirtin',
                placeholder: 'Örn: 50',
            },
            {
                slug: 'yatak_kapasitesi',
                label: 'Yatak Kapasitesi',
                hint: 'Toplam yatak kapasitesi',
                placeholder: 'Örn: 100',
            },
            { slug: 'bina_yasi', label: 'Bina Yaşı', hint: 'Binanın yaşı', placeholder: 'Örn: 15' },
            {
                slug: 'denize_mesafe',
                label: 'Denize Mesafe',
                hint: 'Metre cinsinden mesafe',
                placeholder: 'Örn: 200',
                birim: 'm',
            },
        ],
    },
    konut_projesi: {
        kategori_id: 23,
        label: 'Konut Projesi',
        alias_ids: [6, 24, 25], // Projeden Satış, Villa Projesi, Karma Proje
        expected_template_min: 5,
        expected_features_min: 3,
        expected_required_keys: ['baslik', 'fiyat'],
        category_whitelist_slugs: [
            'ic-ozellikler',
            'dis-ozellikler',
            'muhit',
            'ulasim',
            'banyo',
            'mutfak',
            'bahce',
            'otopark',
            'guvenlik',
        ],
        required_ui_ipuclari: [
            {
                slug: 'teslim_tarihi',
                label: 'Teslim Tarihi',
                hint: 'Projenin beklenen teslim yılı',
                placeholder: 'Örn: 2026',
            },
            {
                slug: 'blok_sayisi',
                label: 'Blok Sayısı',
                hint: 'Toplam blok sayısı',
                placeholder: 'Örn: 4',
            },
            {
                slug: 'kat_sayisi',
                label: 'Kat Sayısı',
                hint: 'Binaların kat sayısı',
                placeholder: 'Örn: 12',
            },
            {
                slug: 'daire_sayisi',
                label: 'Daire Sayısı',
                hint: 'Toplam daire sayısı',
                placeholder: 'Örn: 120',
            },
            {
                slug: 'pesinat_orani',
                label: 'Peşinat Oranı',
                hint: 'Gerekli peşinat yüzdesi (%)',
                placeholder: 'Örn: 30',
                birim: '%',
            },
        ],
    },
};

/**
 * Find preset by kategori_id
 */
export function getPresetByKategoriId(kategoriId) {
    return (
        Object.values(CATEGORY_PRESETS).find(
            (p) => p.kategori_id === kategoriId || (p.alias_ids && p.alias_ids.includes(kategoriId))
        ) || null
    );
}

/**
 * Get missing ui_ipuclari fields for a given context response
 */
export function getMissingUiIpuclari(contextData, preset) {
    if (!preset || !contextData?.context?.template?.fields)
        return preset?.required_ui_ipuclari?.map((f) => f.slug) || [];
    const existingFields = Object.keys(contextData.context.template.fields);
    return preset.required_ui_ipuclari
        .filter((f) => !existingFields.includes(f.slug))
        .map((f) => f.slug);
}
