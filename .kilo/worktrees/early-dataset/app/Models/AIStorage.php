<?php

namespace App\Models;

use App\Traits\HasCountryScope;

/**
 * AIStorage Model
 *
 * LocalMySQLProvider için AI pattern/data depolama.
 * Önceki Deprecated\AIStorage ghost'unun kanonik karşılığı.
 *
 * @property int         $id
 * @property string      $storage_key  Benzersiz anahtar
 * @property mixed       $data         JSON formatında veri
 * @property string|null $type         pattern, result, cache, ...
 * @property string|null $context      Anahtar context'i (prefix'ten türetilir)
 */
class AIStorage extends BaseModel
{
    use HasCountryScope;

    protected $table = 'ai_storages';

    protected $fillable = [
        'storage_key',
        'data',
        'type',
        'context',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // -------------------------------------------------------------------------
    // Scope'lar
    // -------------------------------------------------------------------------

    /** Anahtar bazlı tekil kayıt — LocalMySQLProvider::get() tarafından kullanılır */
    public static function findByKey(string $key): ?self
    {
        return static::where('storage_key', $key)->orderBy('id')->first();
    }

    /** Type bazlı filtrele */
    public function scopeOfType($query, string $type) // context7-ignore
    {
        return $query->where('type', $type);
    }

    /** Context prefix'e göre listele */
    public function scopeWithPrefix($query, string $prefix)
    {
        return $query->where('storage_key', 'like', $prefix . '%');
    }
}
