<?php

namespace App\Modules\Emlak\Models;

use App\Modules\BaseModule\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $ilan_id
 * @property string $dosya_yolu
 * @property int $display_order
 * @property bool $kapak_fotografi
 * @property string|null $alt_text
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Ilan $ilan
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IlanFotografi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IlanFotografi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IlanFotografi onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IlanFotografi query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IlanFotografi whereAltText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IlanFotografi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IlanFotografi whereDosyaYolu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IlanFotografi whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IlanFotografi whereIlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IlanFotografi whereKapakFotografi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IlanFotografi whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IlanFotografi whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IlanFotografi withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IlanFotografi withoutTrashed()
 *
 * @mixin \Eloquent
 */
class IlanFotografi extends BaseModel
{
    use SoftDeletes;

    /**
     * İlişkilendirilmiş tablo adı
     *
     * @var string
     */
    protected $table = 'ilan_fotograflari';

    /**
     * Toplu atanabilir alanlar
     *
     * @var array
     */
    protected $fillable = [
        'ilan_id',
        'dosya_yolu',
        'display_order',
        'kapak_fotografi',
    ];

    /**
     * Cast edilecek özellikler
     *
     * @var array
     */
    protected $casts = [
        'kapak_fotografi' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Bu fotoğrafın ait olduğu ilan
     */
    public function ilan()
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }
}
