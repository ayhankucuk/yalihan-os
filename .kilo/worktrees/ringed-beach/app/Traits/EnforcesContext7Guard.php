<?php

namespace App\Traits;

use App\Exceptions\Context7ViolationException;

/**
 * Context7 Guard Trait
 *
 * Model'lerde yasak alan kullanımını engeller.
 * config/context7_guard.php'den tablo bazlı forbidden field listesini okur.
 * Ayrıca global forbidden fields listesi de kontrol edilir.
 *
 * @context7-ignore-file
 */
trait EnforcesContext7Guard
{
    /**
     * Global forbidden fields (tablo bağımsız, her zaman yasak)
     */
    protected static array $globalForbiddenFields = [ // context7-ignore: guard definition list
        'status',    // context7-ignore: guard definition — → yayin_durumu / talep_durumu / aktiflik_durumu
        'active',    // context7-ignore: guard definition — → aktiflik_durumu
        'is_active', // context7-ignore: guard definition — → aktiflik_durumu
        'aktif',     // context7-ignore: guard definition — → aktiflik_durumu
    ];

    /**
     * Boot trait: creating ve updating event'lerinde guard kontrolü
     */
    public static function bootEnforcesContext7Guard(): void
    {
        static::creating(function ($model) {
            static::checkForbiddenFields($model);
        });

        static::updating(function ($model) {
            static::checkForbiddenFields($model);
        });
    }

    /**
     * Override fill to catch forbidden fields BEFORE mass-assignment filtering.
     * Without this, non-fillable forbidden fields silently pass through.
     *
     * @param array<string, mixed> $attributes
     * @return static
     * @throws Context7ViolationException
     */
    public function fill(array $attributes)
    {
        $tableName = $this->getTable();
        $tableConfig = config("context7_guard.tables.{$tableName}.forbidden", []);
        $allForbidden = array_merge(static::$globalForbiddenFields, $tableConfig);

        foreach (array_keys($attributes) as $field) {
            if (in_array($field, $allForbidden, true)) {
                throw new Context7ViolationException(
                    "CONTEXT7 VIOLATION: '{$field}' alanı kullanımı yasaktır", // context7-ignore
                    [
                        'field' => $field,
                        'table' => $tableName,
                        'model' => static::class,
                        'source' => 'fill_guard',
                    ]
                );
            }
        }

        return parent::fill($attributes);
    }

    /**
     * Model attribute'larında yasak alan var mı kontrol et
     *
     * @throws Context7ViolationException
     */
    protected static function checkForbiddenFields($model): void
    {
        $dirty = array_keys($model->getDirty());
        $tableName = $model->getTable();

        // 1. Global forbidden fields kontrolü
        foreach ($dirty as $field) {
            if (in_array($field, static::$globalForbiddenFields, true)) {
                throw new Context7ViolationException(
                    "CONTEXT7 VIOLATION: '{$field}' alanı kullanımı yasaktır", // context7-ignore
                    [
                        'field' => $field,
                        'table' => $tableName,
                        'model' => get_class($model),
                    ]
                );
            }
        }

        // 2. Tablo bazlı forbidden fields kontrolü (config/context7_guard.php)
        $tableConfig = config("context7_guard.tables.{$tableName}.forbidden", []);
        foreach ($dirty as $field) {
            if (in_array($field, $tableConfig, true)) {
                throw new Context7ViolationException(
                    "CONTEXT7 VIOLATION: '{$field}' alanı kullanımı yasaktır", // context7-ignore
                    [
                        'field' => $field,
                        'table' => $tableName,
                        'model' => get_class($model),
                        'source' => 'table_config',
                    ]
                );
            }
        }
    }
}
