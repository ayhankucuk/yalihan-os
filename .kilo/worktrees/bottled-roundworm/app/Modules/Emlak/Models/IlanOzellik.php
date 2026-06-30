<?php

namespace App\Modules\Emlak\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Ensure the Ozellik class exists in the specified namespace

/**
 * @property-read \App\Models\Ilan|null $ilan
 * @property-read Ozellik|null $ozellik
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IlanOzellik newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IlanOzellik newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IlanOzellik query()
 *
 * @mixin \Eloquent
 */
class IlanOzellik extends Model
{
    use HasFactory;

    /**
     * Tablo adı
     *
     * @var string
     */
    protected $table = 'ilan_ozellikler';

    /**
     * Toplu atanabilir alanlar
     *
     * @var array
     */
    protected $fillable = [
        'ilan_id', 'feature_id', 'deger',
    ];

    /**
     * Özelliğe ait ilan ilişkisi
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ilan()
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    /**
     * Özelliğin feature ilişkisi
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function feature()
    {
        return $this->belongsTo(\App\Models\Feature::class, 'feature_id');
    }

    /**
     * Backward compatibility için category ilişkisi
     */
    public function category()
    {
        return $this->feature()->with('category');
    }

    /**
     * Name accessor (Feature'dan gelir)
     */
    public function getNameAttribute()
    {
        return $this->feature->name ?? 'Özellik Adı Yok';
    }
}
