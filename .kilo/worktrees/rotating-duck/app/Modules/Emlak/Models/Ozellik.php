<?php

namespace App\Modules\Emlak\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\IlanOzellik> $ilanOzellikleri
 * @property-read int|null $ilan_ozellikleri_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ozellik status()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ozellik kategori($kategori)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ozellik newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ozellik newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ozellik query()
 *
 * @mixin \Eloquent
 */
class Ozellik extends Model
{
    use HasFactory;

    /**
     * Tablo adı
     *
     * @var string
     */
    protected $table = 'ozellikler';

    /**
     * Toplu atanabilir alanlar
     *
     * @var array
     */
    protected $fillable = [

    ];

    /**
     * Özelliğe bağlı ilan özellik kayıtları ilişkisi
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ilanOzellikleri()
    {
        return $this->hasMany(IlanOzellik::class, 'ozellik_id');
    }

    /**
     * Eski kategori ilişkisi (FeatureCategory)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(\App\Models\FeatureCategory::class, 'category_id');
    }

    /**
     * Yeni kategori ilişkisi (OzellikKategori)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function kategori()
    {
        return $this->belongsTo(\App\Models\OzellikKategori::class, 'kategori_id');
    }

    /**
     * Aktif özellikleri filtrele
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAktif($query)
    {
        return $query->where('yayin_durumu', true);
    }

    /**
     * Belirli bir kategorideki özellikleri filtrele
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $kategori
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeKategori($query, $kategori)
    {
        return $query->where('kategori', $kategori);
    }
}
