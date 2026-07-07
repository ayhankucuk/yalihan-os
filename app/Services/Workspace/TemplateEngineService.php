<?php

declare(strict_types=1);

namespace App\Services\Workspace;

use App\Support\YayinTipiRules;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Class TemplateEngineService
 *
 * Sprint 6.1-E02: Template Engine MVP
 *
 * Resolves a property workspace intent (yayın tipi slug) into a structured
 * field schema, required documents, AI hooks, and readiness rules.
 *
 * Intent values are canonical slugs defined in YayinTipiRules:
 *   satilik | kiralik | devren | kat-karsiligi | gunluk | haftalik | aylik | sezonluk
 *
 * Design decisions:
 * - No DB dependency — schemas are code-defined for testability and speed.
 * - Intents map 1:1 to YayinTipiRules canonical slugs (SSOT).
 * - AI hooks are declarative — actual dispatching is handled by AIHookRegistry (S6.1-E07).
 *
 * @package App\Services\Workspace
 */
class TemplateEngineService
{
    /**
     * Base fields required by every intent.
     */
    private const BASE_FIELDS = [
        [
            'key'      => 'baslik',
            'label'    => 'İlan Başlığı',
            'alan_tipi' => 'text',
            'required' => true,
            'max'      => 120,
        ],
        [
            'key'      => 'aciklama',
            'label'    => 'Açıklama',
            'alan_tipi' => 'textarea',
            'required' => true,
            'max'      => 5000,
        ],
        [
            'key'      => 'fiyat',
            'label'    => 'Fiyat (₺)',
            'alan_tipi' => 'number',
            'required' => true,
            'min'      => 0,
        ],
        [
            'key'      => 'para_birimi',
            'label'    => 'Para Birimi',
            'alan_tipi' => 'select',
            'required' => true,
            'options'  => ['TRY', 'EUR', 'USD', 'GBP'],
        ],
        [
            'key'      => 'kapak_resmi',
            'label'    => 'Kapak Resmi',
            'alan_tipi' => 'image',
            'required' => true,
        ],
        [
            'key'      => 'il',
            'label'    => 'İl',
            'alan_tipi' => 'select',
            'required' => true,
        ],
        [
            'key'      => 'ilce',
            'label'    => 'İlçe',
            'alan_tipi' => 'select',
            'required' => true,
        ],
        [
            'key'      => 'lat',
            'label'    => 'Enlem',
            'alan_tipi' => 'hidden',
            'required' => false,
        ],
        [
            'key'      => 'lng',
            'label'    => 'Boylam',
            'alan_tipi' => 'hidden',
            'required' => false,
        ],
    ];

    /**
     * Intent-specific field schemas.
     * Merged with BASE_FIELDS on resolution.
     */
    private const INTENT_FIELDS = [
        'satilik' => [
            ['key' => 'brut_metrekare',  'label' => 'Brüt m²',         'alan_tipi' => 'number', 'required' => true,  'min' => 1],
            ['key' => 'net_metrekare',   'label' => 'Net m²',           'alan_tipi' => 'number', 'required' => false, 'min' => 1],
            ['key' => 'oda_sayisi',      'label' => 'Oda Sayısı',       'alan_tipi' => 'select', 'required' => true,  'options' => ['1+0','1+1','2+1','3+1','3+2','4+1','4+2','5+1','5+2','6+']],
            ['key' => 'bina_yasi',       'label' => 'Bina Yaşı',        'alan_tipi' => 'number', 'required' => false, 'min' => 0],
            ['key' => 'kat',             'label' => 'Kat',               'alan_tipi' => 'number', 'required' => false],
            ['key' => 'toplam_kat',      'label' => 'Toplam Kat',       'alan_tipi' => 'number', 'required' => false],
            ['key' => 'isitma_tipi',     'label' => 'Isıtma Tipi',      'alan_tipi' => 'select', 'required' => false, 'options' => ['dogalgaz','merkezi','elektrik','klima','yok']],
            ['key' => 'tapusu_var',      'label' => 'Tapu Durumu',      'alan_tipi' => 'select', 'required' => true,  'options' => ['kat-mulkiyeti','arsa-payi','hisseli','kira-kontrati']],
        ],

        'kiralik' => [
            ['key' => 'brut_metrekare',  'label' => 'Brüt m²',         'alan_tipi' => 'number', 'required' => true,  'min' => 1],
            ['key' => 'net_metrekare',   'label' => 'Net m²',           'alan_tipi' => 'number', 'required' => false, 'min' => 1],
            ['key' => 'oda_sayisi',      'label' => 'Oda Sayısı',       'alan_tipi' => 'select', 'required' => true,  'options' => ['1+0','1+1','2+1','3+1','3+2','4+1','4+2','5+1','5+2','6+']],
            ['key' => 'depozito',        'label' => 'Depozito (ay)',     'alan_tipi' => 'number', 'required' => false, 'min' => 0, 'max' => 12],
            ['key' => 'aidat',           'label' => 'Aidat (₺/ay)',     'alan_tipi' => 'number', 'required' => false, 'min' => 0],
            ['key' => 'kira_donem',      'label' => 'Kira Dönemi',      'alan_tipi' => 'select', 'required' => true,  'options' => ['aylik','yillik']],
            ['key' => 'esyali',          'label' => 'Eşyalı mı?',       'alan_tipi' => 'boolean','required' => false],
        ],

        'sezonluk' => [
            ['key' => 'kapasite',        'label' => 'Kişi Kapasitesi',  'alan_tipi' => 'number', 'required' => true,  'min' => 1, 'max' => 50],
            ['key' => 'yatak_odasi',     'label' => 'Yatak Odası Sayısı','alan_tipi' => 'number','required' => true,  'min' => 0],
            ['key' => 'banyo_sayisi',    'label' => 'Banyo Sayısı',     'alan_tipi' => 'number', 'required' => false, 'min' => 0],
            ['key' => 'havuz',           'label' => 'Havuz Var mı?',    'alan_tipi' => 'boolean','required' => false],
            ['key' => 'denize_mesafe',   'label' => 'Denize Mesafe (m)','alan_tipi' => 'number', 'required' => false, 'min' => 0],
            ['key' => 'musait_tarihler', 'label' => 'Müsait Tarihler',  'alan_tipi' => 'calendar','required' => true],
            ['key' => 'min_konaklama',   'label' => 'Min. Konaklama (gün)','alan_tipi' => 'number','required' => true, 'min' => 1],
            ['key' => 'fiyat_haftalik',  'label' => 'Haftalık Fiyat (₺)','alan_tipi' => 'number','required' => false,'min' => 0],
        ],

        'gunluk' => [
            ['key' => 'kapasite',        'label' => 'Kişi Kapasitesi',  'alan_tipi' => 'number', 'required' => true,  'min' => 1],
            ['key' => 'yatak_odasi',     'label' => 'Yatak Odası Sayısı','alan_tipi' => 'number','required' => true,  'min' => 0],
            ['key' => 'musait_tarihler', 'label' => 'Müsait Tarihler',  'alan_tipi' => 'calendar','required' => true],
            ['key' => 'fiyat_geceler',   'label' => 'Gecelik Fiyat (₺)','alan_tipi' => 'number', 'required' => true, 'min' => 0],
            ['key' => 'min_konaklama',   'label' => 'Min. Konaklama (gece)','alan_tipi' => 'number','required' => true,'min' => 1],
        ],

        'haftalik' => [
            ['key' => 'kapasite',        'label' => 'Kişi Kapasitesi',  'alan_tipi' => 'number', 'required' => true, 'min' => 1],
            ['key' => 'yatak_odasi',     'label' => 'Yatak Odası Sayısı','alan_tipi' => 'number','required' => true, 'min' => 0],
            ['key' => 'musait_tarihler', 'label' => 'Müsait Tarihler',  'alan_tipi' => 'calendar','required' => true],
            ['key' => 'fiyat_haftalik',  'label' => 'Haftalık Fiyat (₺)','alan_tipi' => 'number','required' => true, 'min' => 0],
        ],

        'aylik' => [
            ['key' => 'brut_metrekare',  'label' => 'Brüt m²',         'alan_tipi' => 'number', 'required' => true, 'min' => 1],
            ['key' => 'oda_sayisi',      'label' => 'Oda Sayısı',       'alan_tipi' => 'select', 'required' => true, 'options' => ['1+0','1+1','2+1','3+1','3+2','4+1','4+2','5+1']],
            ['key' => 'esyali',          'label' => 'Eşyalı mı?',       'alan_tipi' => 'boolean','required' => false],
            ['key' => 'musait_tarihler', 'label' => 'Müsait Tarihler',  'alan_tipi' => 'calendar','required' => false],
        ],

        'devren' => [
            ['key' => 'brut_metrekare',  'label' => 'Brüt m²',         'alan_tipi' => 'number', 'required' => true, 'min' => 1],
            ['key' => 'ciro',            'label' => 'Aylık Ciro (₺)',  'alan_tipi' => 'number', 'required' => false, 'min' => 0],
            ['key' => 'kira_tutari',     'label' => 'Kira Tutarı (₺)', 'alan_tipi' => 'number', 'required' => false, 'min' => 0],
            ['key' => 'personel_sayisi', 'label' => 'Personel Sayısı', 'alan_tipi' => 'number', 'required' => false, 'min' => 0],
            ['key' => 'kira_bitis',      'label' => 'Kira Bitiş Tarihi','alan_tipi' => 'date',  'required' => false],
        ],

        'kat-karsiligi' => [
            ['key' => 'arsa_metrekare',  'label' => 'Arsa m²',         'alan_tipi' => 'number', 'required' => true, 'min' => 1],
            ['key' => 'imar_durumu',     'label' => 'İmar Durumu',      'alan_tipi' => 'select', 'required' => true, 'options' => ['konut','ticari','karma','tarimsal']],
            ['key' => 'kaks',            'label' => 'KAKS',             'alan_tipi' => 'text',   'required' => false],
            ['key' => 'yuzde_pay',       'label' => 'Yüzde Pay (%)',   'alan_tipi' => 'number', 'required' => false, 'min' => 0, 'max' => 100],
        ],
    ];

    /**
     * Readiness rules per intent — keys that must be present and non-empty
     * before workspace can transition to ready_for_review.
     *
     * @var array<string, array<string>>
     */
    private const READINESS_RULES = [
        'satilik'      => ['baslik', 'aciklama', 'fiyat', 'kapak_resmi', 'il', 'ilce', 'brut_metrekare', 'oda_sayisi', 'tapusu_var'],
        'kiralik'      => ['baslik', 'aciklama', 'fiyat', 'kapak_resmi', 'il', 'ilce', 'brut_metrekare', 'oda_sayisi', 'kira_donem'],
        'sezonluk'     => ['baslik', 'aciklama', 'fiyat', 'kapak_resmi', 'il', 'ilce', 'kapasite', 'yatak_odasi', 'musait_tarihler', 'min_konaklama'],
        'gunluk'       => ['baslik', 'aciklama', 'fiyat', 'kapak_resmi', 'il', 'ilce', 'kapasite', 'yatak_odasi', 'musait_tarihler', 'fiyat_geceler'],
        'haftalik'     => ['baslik', 'aciklama', 'fiyat', 'kapak_resmi', 'il', 'ilce', 'kapasite', 'yatak_odasi', 'musait_tarihler', 'fiyat_haftalik'],
        'aylik'        => ['baslik', 'aciklama', 'fiyat', 'kapak_resmi', 'il', 'ilce', 'brut_metrekare', 'oda_sayisi'],
        'devren'       => ['baslik', 'aciklama', 'fiyat', 'kapak_resmi', 'il', 'ilce', 'brut_metrekare'],
        'kat-karsiligi'=> ['baslik', 'aciklama', 'fiyat', 'kapak_resmi', 'il', 'ilce', 'arsa_metrekare', 'imar_durumu'],
    ];

    /**
     * Required documents per intent (e.g. for compliance workflows).
     *
     * @var array<string, array<string>>
     */
    private const REQUIRED_DOCUMENTS = [
        'satilik'       => ['tapu_fotokopisi', 'iskan_belgesi'],
        'kiralik'       => ['tapu_fotokopisi'],
        'sezonluk'      => ['is_ruhsati', 'yangin_raporu'],
        'gunluk'        => ['is_ruhsati'],
        'haftalik'      => ['is_ruhsati'],
        'aylik'         => ['tapu_fotokopisi'],
        'devren'        => ['vergi_levhasi', 'kira_kontrati'],
        'kat-karsiligi' => ['tapu_fotokopisi', 'imar_durumu_belgesi'],
    ];

    /**
     * AI hooks per intent — declarative list of AI capabilities to activate.
     * Actual dispatching is handled by AIHookRegistry (S6.1-E07).
     *
     * @var array<string, array<string>>
     */
    private const AI_HOOKS = [
        'satilik'       => ['generate_title', 'generate_description', 'suggest_price', 'detect_property_type'],
        'kiralik'       => ['generate_title', 'generate_description', 'suggest_price'],
        'sezonluk'      => ['generate_title', 'generate_description', 'suggest_price', 'generate_calendar_block', 'translate_to_english'],
        'gunluk'        => ['generate_title', 'generate_description', 'suggest_price', 'generate_calendar_block', 'translate_to_english'],
        'haftalik'      => ['generate_title', 'generate_description', 'suggest_price', 'generate_calendar_block'],
        'aylik'         => ['generate_title', 'generate_description', 'suggest_price'],
        'devren'        => ['generate_title', 'generate_description'],
        'kat-karsiligi' => ['generate_title', 'generate_description'],
    ];

    /**
     * Resolve a structured template schema for the given intent.
     *
     * @param string $intent Canonical yayın tipi slug (e.g. 'satilik', 'sezonluk')
     * @return array{
     *   intent: string,
     *   template_id: string,
     *   fields: array<int, array<string, mixed>>,
     *   readiness_rules: array<string>,
     *   required_documents: array<string>,
     *   ai_hooks: array<string>,
     *   requires_calendar: bool
     * }
     * @throws InvalidArgumentException When intent is unknown
     */
    public function resolveTemplate(string $intent): array
    {
        // Canonicalize — throws InvalidArgumentException for unknown slugs
        $canonical = YayinTipiRules::canonicalizeSlug($intent);

        $intentFields = self::INTENT_FIELDS[$canonical] ?? [];
        $fields = array_merge(self::BASE_FIELDS, $intentFields);

        return [
            'intent'             => $canonical,
            'template_id'        => 'tpl_' . $canonical,
            'fields'             => $fields,
            'readiness_rules'    => self::READINESS_RULES[$canonical] ?? [],
            'required_documents' => self::REQUIRED_DOCUMENTS[$canonical] ?? [],
            'ai_hooks'           => self::AI_HOOKS[$canonical] ?? [],
            'requires_calendar'  => YayinTipiRules::requiresCalendar($canonical),
        ];
    }

    /**
     * Get all supported intents.
     *
     * @return array<string>
     */
    public function getSupportedIntents(): array
    {
        return array_keys(self::INTENT_FIELDS);
    }

    /**
     * Check if an intent is supported.
     *
     * @param string $intent
     * @return bool
     */
    public function supports(string $intent): bool
    {
        try {
            $canonical = YayinTipiRules::canonicalizeSlug($intent);
            return isset(self::INTENT_FIELDS[$canonical]);
        } catch (InvalidArgumentException $e) {
            Log::debug('TemplateEngineService: unsupported intent slug', [
                'intent' => $intent,
                'reason' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
