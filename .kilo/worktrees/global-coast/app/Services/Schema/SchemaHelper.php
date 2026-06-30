<?php

namespace App\Services\Schema;

use App\Enums\IlanDurumu;

use Illuminate\Support\Facades\Schema;

/**
 * Schema Helper Service
 *
 * Context7 Standardı: C7-SCHEMA-HELPER-2025-12-06
 *
 * Schema kontrollerini merkezi olarak yönetir.
 * Cache ile performans optimizasyonu sağlar.
 *
 * @package App\Services\Schema
 */
class SchemaHelper
{
    /**
     * Column cache (performans için)
     *
     * @var array
     */
    private static array $columnCache = [];

    /**
     * Table cache (performans için)
     *
     * @var array
     */
    private static array $tableCache = [];

    /**
     * Status kolonu var mı kontrol et
     *
     * @param string $table Tablo adı
     * @return bool
     */
    public static function hasStatusColumn(string $table): bool
    {
        $key = "{$table}.status"; // context7-ignore
        if (!isset(self::$columnCache[$key])) {
            self::$columnCache[$key] = Schema::hasColumn($table, 'status'); // context7-ignore
        }
        return self::$columnCache[$key];
    }

    /**
     * Display order kolonu var mı kontrol et
     *
     * @param string $table Tablo adı
     * @return bool
     */
    public static function hasDisplayOrderColumn(string $table): bool
    {
        $key = "{$table}.display_order";
        if (!isset(self::$columnCache[$key])) {
            self::$columnCache[$key] = Schema::hasColumn($table, 'display_order');
        }
        return self::$columnCache[$key];
    }

    /**
     * Tablo var mı kontrol et
     *
     * @param string $table Tablo adı
     * @return bool
     */
    public static function hasTable(string $table): bool
    {
        if (!isset(self::$tableCache[$table])) {
            self::$tableCache[$table] = Schema::hasTable($table);
        }
        return self::$tableCache[$table];
    }

    /**
     * Query'ye status/aktiflik filtresi ekle (Context7 canonical).
     * Öncelik: aktiflik_durumu → status (legacy).
     *
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @param string $table Tablo adı
     * @return void
     */
    public static function applyStatusFilter($query, string $table): void
    {
        if (Schema::hasColumn($table, 'aktiflik_durumu')) {
            $query->where('aktiflik_durumu', 1);
            return;
        }

        if (Schema::hasColumn($table, 'yayin_durumu')) {
            $query->whereIn('yayin_durumu', [IlanDurumu::YAYINDA->value, 'yayinda']);
            return;
        }

        if (Schema::hasColumn($table, 'talep_durumu')) {
            $query->where('talep_durumu', 'beklemede');
            return;
        }

        // @context7:exempt Fallback for NON-CORE tables only (e.g., blog_posts, external integrations)
        // Context7 RULE: Core tables MUST use aktiflik_durumu/yayin_durumu/talep_durumu
        // This fallback is ONLY for legacy/external tables that cannot be migrated
        if (Schema::hasColumn($table, 'status')) { // context7-ignore
            $query->where('status', 1); // ⚠️ Non-core legacy table only // context7-ignore
        }
    }

    /**
     * Query'ye display order ekle
     *
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @param string $table Tablo adı
     * @param string|null $fallbackOrder Fallback order column (varsayılan: 'name')
     * @return void
     */
    public static function applyDisplayOrder($query, string $table, ?string $fallbackOrder = 'name'): void
    {
        if (self::hasDisplayOrderColumn($table)) {
            $query->orderByRaw('COALESCE(display_order, 999999) ASC'); // context7-ignore
        } elseif ($fallbackOrder) {
            $query->orderBy($fallbackOrder, 'ASC'); // context7-ignore
        }
    }

    /**
     * Select columns with display_order if exists
     *
     * @param string $table Tablo adı
     * @param array $baseColumns Base columns
     * @return array
     */
    public static function getSelectColumns(string $table, array $baseColumns): array
    {
        if (self::hasDisplayOrderColumn($table)) {
            return array_merge($baseColumns, ['display_order']);
        }
        return $baseColumns;
    }

    /**
     * Cache'i temizle (test için)
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$columnCache = [];
        self::$tableCache = [];
    }
}
