<?php

/**
 * YALIHAN OS — Canonical Table Dictionary
 *
 * Tek kaynak: Her domain tablosu için tablo adı, model, migration, kullanım alanı
 * Bu dosya schema drift, audit ve CI kapısı için referans kaynaktır.
 *
 * Sınıflandırma:
 *   - CANONICAL: Production'da aktif, model + migration mevcut
 *   - DEPRECATED: Hala var ama yeni kod kullanmıyor
 *   - GHOST: Model/migration yok, kod kullanmıyor
 *   - STALE_REFERENCE: Config'de referans var ama tablo yok
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Feature & Property Domain
    |--------------------------------------------------------------------------
    */
    'features' => [
        'table'            => 'features',
        'model'            => \App\Models\Feature::class,
        'migration'        => 'database/migrations/YYYY_MM_DD_create_features_table.php',
        'alias'            => 'ozellikler', // legacy name
        'domain'           => 'Feature',
        'usage'            => 'Master feature catalog (villa, land, commercial)',
        'canonical_table'  => 'features',
        'status'           => 'CANONICAL',
        'notes'            => '22 active features in production',
    ],

    'ozellikler' => [
        'table'            => 'ozellikler',
        'model'            => \App\Models\Ozellik::class,
        'migration'        => null, // legacy table, no dedicated migration
        'alias'            => null,
        'domain'           => 'Feature',
        'usage'            => 'Legacy feature names ( kullanıcı görüntüleme )',
        'canonical_table'  => 'features',
        'status'           => 'DEPRECATED',
        'notes'            => 'Use features table instead',
    ],

    'feature_assignments' => [
        'table'            => 'feature_assignments',
        'model'            => \App\Models\FeatureAssignment::class,
        'migration'        => 'database/migrations/YYYY_MM_DD_create_feature_assignments_table.php',
        'alias'            => null,
        'domain'           => 'Feature',
        'usage'            => 'Morph relation: YayinTipiSablonu, AltKategoriYayinTipi, Ilan',
        'canonical_table'  => 'feature_assignments',
        'status'           => 'CANONICAL',
        'notes'            => '32 columns, morphTo relation pattern',
    ],

    'feature_categories' => [
        'table'            => 'feature_categories',
        'model'            => \App\Models\FeatureCategory::class,
        'migration'        => null,
        'alias'            => null,
        'domain'           => 'Feature',
        'usage'            => 'Feature grouping for admin UI',
        'canonical_table'  => 'feature_categories',
        'status'           => 'CANONICAL',
        'notes'            => 'Used in PropertyHub dashboard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Domain
    |--------------------------------------------------------------------------
    */
    'yayin_tipi_sablonlari' => [
        'table'            => 'yayin_tipi_sablonlari',
        'model'            => \App\Models\YayinTipiSablonu::class,
        'migration'        => null,
        'alias'            => null,
        'domain'           => 'Template',
        'usage'            => 'Category × Listing Type pivot with field assignments',
        'canonical_table'  => 'yayin_tipi_sablonlari',
        'status'           => 'CANONICAL',
        'notes'            => '15 columns, used by PropertyHubOrchestrator',
    ],

    'kategori_yayin_tipi_field_dependencies' => [
        'table'            => 'kategori_yayin_tipi_field_dependencies',
        'model'            => \App\Models\KategoriYayinTipiFieldDependency::class,
        'migration'        => null,
        'alias'            => null,
        'domain'           => 'Template',
        'usage'            => 'Field schema per category × listing type (42 records)',
        'canonical_table'  => 'kategori_yayin_tipi_field_dependencies',
        'status'           => 'CANONICAL',
        'notes'            => 'Used in Sprint 6.8 health score calculation',
    ],

    'ups_feature_packs' => [
        'table'            => 'ups_feature_packs',
        'model'            => \App\Models\FeaturePack::class,
        'migration'        => null,
        'alias'            => null,
        'domain'           => 'Template',
        'usage'            => 'Bulk feature application to templates',
        'canonical_table'  => 'ups_feature_packs',
        'status'           => 'CANONICAL',
        'notes'            => 'Used by PropertyHubOrchestrator::applyPackToTemplates()',
    ],

    /*
    |--------------------------------------------------------------------------
    | Listing Domain
    |--------------------------------------------------------------------------
    */
    'ilanlar' => [
        'table'            => 'ilanlar',
        'model'            => \App\Models\Ilan::class,
        'migration'        => null,
        'alias'            => null,
        'domain'           => 'Listing',
        'usage'            => 'Core listing entity (169 columns)',
        'canonical_table'  => 'ilanlar',
        'status'           => 'CANONICAL',
        'notes'            => 'Largest table, tenant-scoped',
    ],

    'ilan_kategorileri' => [
        'table'            => 'ilan_kategorileri',
        'model'            => \App\Models\IlanKategori::class,
        'migration'        => null,
        'alias'            => null,
        'domain'           => 'Listing',
        'usage'            => 'Category hierarchy (konut, arsa, isyeri, yazlik)',
        'canonical_table'  => 'ilan_kategorileri',
        'status'           => 'CANONICAL',
        'notes'            => 'Hierarchical: seviye 0 = root',
    ],

    'yayin_tipleri' => [
        'table'            => 'yayin_tipleri',
        'model'            => \App\Models\YayinTipi::class,
        'migration'        => null,
        'alias'            => null,
        'domain'           => 'Listing',
        'usage'            => 'Listing type enum (satilik, gunluk_kiralama)',
        'canonical_table'  => 'yayin_tipleri',
        'status'           => 'CANONICAL',
        'notes'            => 'SSOT for listing types',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ghost / Stale References (For Documentation Only)
    |--------------------------------------------------------------------------
    */
    'property_features' => [
        'table'            => 'property_features',
        'model'            => null,
        'migration'        => null,
        'alias'            => null,
        'domain'           => 'PropertyHub',
        'usage'            => 'NONE — STALE_REFERENCE',
        'canonical_table'  => 'features', // potential canonical match
        'status'           => 'STALE_REFERENCE',
        'notes'            => 'Removed from AuditSchemaAlignment. No model, no migration, no code usage.',
    ],

    'property_templates' => [
        'table'            => 'property_templates',
        'model'            => null,
        'migration'        => null,
        'alias'            => null,
        'domain'           => 'PropertyHub',
        'usage'            => 'NONE — STALE_REFERENCE',
        'canonical_table'  => 'yayin_tipi_sablonlari', // potential canonical match
        'status'           => 'STALE_REFERENCE',
        'notes'            => 'Removed from AuditSchemaAlignment. No model, no migration, no code usage.',
    ],

    'template_feature_assignments' => [
        'table'            => 'template_feature_assignments',
        'model'            => null,
        'migration'        => null,
        'alias'            => null,
        'domain'           => 'PropertyHub',
        'usage'            => 'NONE — STALE_REFERENCE',
        'canonical_table'  => 'feature_assignments', // potential canonical match
        'status'           => 'STALE_REFERENCE',
        'notes'            => 'Removed from AuditSchemaAlignment. No model, no migration, no code usage.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration Accessors
    |--------------------------------------------------------------------------
    */
    // Accessors: stateless helpers. Call: config('canonical_tables')['getCanonical'](config('canonical_tables'))
    'getByStatus' => fn (array $tables, string $status): array =>
        array_filter($tables, fn($entry) => is_array($entry) && ($entry['status'] ?? '') === $status),

    'getCanonical' => fn (array $tables): array =>
        array_filter($tables, fn($entry) => is_array($entry) && ($entry['status'] ?? '') === 'CANONICAL'),

    'getStaleReferences' => fn (array $tables): array =>
        array_filter($tables, fn($entry) => is_array($entry) && ($entry['status'] ?? '') === 'STALE_REFERENCE'),

];
