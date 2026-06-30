<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Schema Guard — Forbidden Alias Registry
    |--------------------------------------------------------------------------
    |
    | Single source of truth for field naming enforcement.
    | Every entry maps a wrong/legacy field to its canonical DB column.
    |
    | severity: critical | high | medium
    |   critical = causes 500 error (column doesn't exist)
    |   high     = causes data integrity issues (writes to wrong column)
    |   medium   = style violation (works but violates Context7)
    |
    */

    'forbidden_aliases' => [

        // ── property_features ──────────────────────────────────────────
        [
            'table'         => 'property_features',
            'wrong_field'   => 'slug',
            'correct_field' => 'code',
            'severity'      => 'critical',
            'note'          => 'DB column is "code". Model has getSlugAttribute() accessor. SQL SELECT must use "code".',
        ],
        [
            'table'         => 'property_features',
            'wrong_field'   => 'description',
            'correct_field' => null, // forbidden — column does not exist
            'severity'      => 'critical',
            'note'          => 'Column does not exist in DB. Use help_text for descriptive content.',
        ],
        [
            'table'         => 'property_features',
            'wrong_field'   => 'type',
            'correct_field' => 'data_type',
            'severity'      => 'critical',
            'note'          => 'Context7: "type" is universally banned. Use data_type or input_type.',
        ],

        // ── template_feature_assignments ───────────────────────────────
        [
            'table'         => 'template_feature_assignments',
            'wrong_field'   => 'display_order',
            'correct_field' => 'sort_order',
            'severity'      => 'critical',
            'note'          => 'DB column is "sort_order". display_order exists on other tables but NOT here.',
        ],
        [
            'table'         => 'template_feature_assignments',
            'wrong_field'   => 'group_name',
            'correct_field' => 'section_name',
            'severity'      => 'critical',
            'note'          => 'DB column is "section_name", not "group_name".',
        ],

        // ── property_templates ─────────────────────────────────────────
        [
            'table'         => 'property_templates',
            'wrong_field'   => 'template_version',
            'correct_field' => 'version',
            'severity'      => 'critical',
            'note'          => 'property_templates uses "version". ilanlar and ups_templates use "template_version" — domain-specific.',
        ],
        [
            'table'         => 'property_templates',
            'wrong_field'   => 'slug',
            'correct_field' => 'code',
            'severity'      => 'critical',
            'note'          => 'DB column is "code". Unique index on code.',
        ],

        // ── yayin_tipi_sablonlari ──────────────────────────────────────
        [
            'table'         => 'yayin_tipi_sablonlari',
            'wrong_field'   => 'name',
            'correct_field' => 'ad',
            'severity'      => 'critical',
            'note'          => 'DB column is "ad". Model has getNameAttribute() accessor but SQL SELECT fails with "name".',
        ],
        [
            'table'         => 'yayin_tipi_sablonlari',
            'wrong_field'   => 'description',
            'correct_field' => 'aciklama',
            'severity'      => 'critical',
            'note'          => 'DB column is "aciklama".',
        ],

        // ── fx_rates ───────────────────────────────────────────────────
        [
            'table'         => 'fx_rates',
            'wrong_field'   => 'currency_code',
            'correct_field' => 'from_currency',
            'severity'      => 'critical',
            'note'          => 'DB has from_currency + to_currency pair. No single "currency_code" column.',
        ],
        [
            'table'         => 'fx_rates',
            'wrong_field'   => 'rate_date',
            'correct_field' => 'effective_at',
            'severity'      => 'critical',
            'note'          => 'DB column is "effective_at" (datetime).',
        ],
        [
            'table'         => 'fx_rates',
            'wrong_field'   => 'rate_to_try',
            'correct_field' => 'rate',
            'severity'      => 'critical',
            'note'          => 'DB has single "rate" column. No directional rate fields.',
        ],
        [
            'table'         => 'fx_rates',
            'wrong_field'   => 'buying_rate',
            'correct_field' => 'rate',
            'severity'      => 'critical',
            'note'          => 'DB has single "rate" column. No buy/sell distinction.',
        ],
        [
            'table'         => 'fx_rates',
            'wrong_field'   => 'selling_rate',
            'correct_field' => 'rate',
            'severity'      => 'critical',
            'note'          => 'DB has single "rate" column. No buy/sell distinction.',
        ],

        // ── ilanlar ────────────────────────────────────────────────────
        [
            'table'         => 'ilanlar',
            'wrong_field'   => 'ana_baslik',
            'correct_field' => 'baslik',
            'severity'      => 'critical',
            'note'          => 'Legacy field. DB column is "baslik".',
        ],
        [
            'table'         => 'ilanlar',
            'wrong_field'   => 'latitude',
            'correct_field' => 'lat',
            'severity'      => 'critical',
            'note'          => 'Context7: lat/lng pair. Never use latitude/longitude.',
        ],
        [
            'table'         => 'ilanlar',
            'wrong_field'   => 'longitude',
            'correct_field' => 'lng',
            'severity'      => 'critical',
            'note'          => 'Context7: lat/lng pair. Never use latitude/longitude.',
        ],
        [
            'table'         => 'ilanlar',
            'wrong_field'   => 'featured',
            'correct_field' => 'one_cikan',
            'severity'      => 'high',
            'note'          => 'Context7: one_cikan is canonical.',
        ],
        [
            'table'         => 'ilanlar',
            'wrong_field'   => 'is_featured',
            'correct_field' => 'one_cikan',
            'severity'      => 'high',
            'note'          => 'Context7: one_cikan is canonical.',
        ],

        // ── cities ─────────────────────────────────────────────────────
        [
            'table'         => 'cities',
            'wrong_field'   => 'is_active',
            'correct_field' => 'aktiflik_durumu',
            'severity'      => 'medium',
            'note'          => 'Context7: aktiflik_durumu is canonical. cities table has is_active (schema violation).',
        ],
        [
            'table'         => 'cities',
            'wrong_field'   => 'latitude',
            'correct_field' => null,
            'severity'      => 'critical',
            'note'          => 'Column does not exist in cities table.',
        ],
        [
            'table'         => 'cities',
            'wrong_field'   => 'longitude',
            'correct_field' => null,
            'severity'      => 'critical',
            'note'          => 'Column does not exist in cities table.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scan Targets
    |--------------------------------------------------------------------------
    |
    | Directories scanned by system:detect-schema-drift command.
    |
    */

    'scan_paths' => [
        'app/Http/Controllers',
        'app/Services',
        'app/Actions',
        'app/Models',
        'app/Domains',
        'app/Jobs',
        'app/Repositories',
        'resources/views/admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | File Extensions to Scan
    |--------------------------------------------------------------------------
    */

    'scan_extensions' => ['php', 'blade.php'],

    /*
    |--------------------------------------------------------------------------
    | Excluded Files (self-guard)
    |--------------------------------------------------------------------------
    |
    | Files that define the guard itself must be excluded from scanning.
    |
    */

    'excluded_files' => [
        'config/schema_guard.php',
        'config/sab.php',
        'config/context7_guard.php',
        'app/Console/Commands/DetectSchemaDrift.php',
        'app/Console/Commands/AuditSchemaAlignment.php',
        'docs/schema-truth.md',
        'docs/schema-field-map.md',
    ],

    /*
    |--------------------------------------------------------------------------
    | Domain-Specific Column Notes
    |--------------------------------------------------------------------------
    |
    | Some columns exist in multiple tables with different names.
    | This section documents legitimate domain differences.
    |
    */

    'domain_notes' => [
        'template_version' => [
            'property_templates' => 'version',        // column name in this table
            'ilanlar'            => 'template_version', // column name in this table
            'ups_templates'      => 'template_version', // column name in this table
            'note' => 'Only property_templates uses "version". Others use "template_version". Context matters.',
        ],
        'display_order_vs_sort_order' => [
            'property_features'            => 'display_order',  // ✅ correct for this table
            'yayin_tipi_sablonlari'        => 'display_order',  // ✅ correct for this table
            'template_feature_assignments' => 'sort_order',     // ⚠️ different name
            'note' => 'template_feature_assignments uses sort_order. Others use display_order.',
        ],
    ],
];
