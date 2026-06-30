<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait SearchableTrait
{
    /**
     * Genel arama scope'u
     *
     * @param  array  $fields  Aranacak alanlar
     */
    public function scopeSearch(Builder $query, string $search, array $fields = []): Builder
    {
        if (empty($search)) {
            return $query;
        }

        // Eğer fields belirtilmemişse, searchable fields kullan
        if (empty($fields) && property_exists($this, 'searchable')) {
            $fields = $this->searchable;
        }

        // Varsayılan aranabilir alanlar
        if (empty($fields)) {
            $fields = ['name', 'title', 'description'];
        }

        // ✅ PERFORMANCE FIX: Schema builder'ı cache'le - hasColumn() her seferinde çağrılmasın
        $schema = $this->getConnection()->getSchemaBuilder();
        $tableName = $this->getTable();
        $validFields = [];

        return $query->where(function (Builder $q) use ($search, $fields, $schema, $tableName, &$validFields) {
            foreach ($fields as $field) {
                // Column kontrolü cache'le (aynı request içinde tekrar kullanılabilir)
                if (! isset($validFields[$field])) {
                    $validFields[$field] = $schema->hasColumn($tableName, $field);
                }

                if ($validFields[$field]) {
                    $q->orWhere($field, 'LIKE', "%{$search}%");
                }
            }
        });
    }

    /**
     * Tam metin arama scope'u
     */
    public function scopeFullTextSearch(Builder $query, string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        // MySQL FULLTEXT search desteği varsa kullan
        if (property_exists($this, 'fullTextColumns')) {
            $columns = implode(',', $this->fullTextColumns);

            return $query->whereRaw("MATCH({$columns}) AGAINST(? IN BOOLEAN MODE)", [$search]);
        }

        // Yoksa normal search kullan
        return $this->scopeSearch($query, $search);
    }

    /**
     * Filtreleme scope'u
     */
    public function scopeFilter(Builder $query, array $filters = []): Builder
    {
        if (empty($filters)) {
            return $query;
        }

        // ✅ OPTIMIZED: Schema builder'ı cache'le - hasColumn() her seferinde çağrılmasın
        $schema = $this->getConnection()->getSchemaBuilder();
        $tableName = $this->getTable();
        $validColumns = [];

        foreach ($filters as $field => $value) {
            if (empty($value)) {
                continue;
            }

            // Column kontrolü cache'le (aynı request içinde tekrar kullanılabilir)
            if (! isset($validColumns[$field])) {
                $validColumns[$field] = $schema->hasColumn($tableName, $field);
            }

            if ($validColumns[$field]) {
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
        }

        return $query;
    }

    /**
     * Sıralama scope'u
     */
    public function scopeSortBy(Builder $query, string $sortBy = 'created_at', string $sortDirection = 'desc'): Builder
    {
        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), $sortBy)) {
            return $query->orderBy($sortBy, $sortDirection);
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Pagination ile birlikte arama
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function searchAndPaginate(
        string $search = '',
        array $filters = [],
        int $perPage = 15,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ) {
        return static::search($search)
            ->filter($filters)
            ->sortBy($sortBy, $sortDirection)
            ->paginate($perPage);
    }
}
