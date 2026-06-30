<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CountryScope implements Scope
{
    /**
     * Cache of tables that have ulke_id column.
     */
    private static array $hasColumnCache = [];

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            $user = Auth::user();

            // ✅ Multi-Country Isolation: Kullanıcı sadece kendi ülkesine ait verileri görür.
            // Super-admin veya merkez ofis kullanıcıları için istisna eklenebilir.
            if ($user->ulke_id && !app()->runningInConsole()) {
                $table = $model->getTable();

                // Skip tables that don't have ulke_id column
                if (!isset(self::$hasColumnCache[$table])) {
                    self::$hasColumnCache[$table] = Schema::hasColumn($table, 'ulke_id');
                }

                if (self::$hasColumnCache[$table]) {
                    $builder->where($table . '.ulke_id', $user->ulke_id);
                }
            }
        }
    }
}
