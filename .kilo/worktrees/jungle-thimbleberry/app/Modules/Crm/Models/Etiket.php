<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $color
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Kisi> $kisiler
 * @property-read int|null $kisiler_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Etiket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Etiket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Etiket query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Etiket whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Etiket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Etiket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Etiket whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Etiket whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Etiket extends Model
{
    use HasFactory;

    protected $table = 'etiketler';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
    ];

    /**
     * Bu etikete sahip kişileri getiren ilişki.
     */
    public function kisiler()
    {
        return $this->belongsToMany(Kisi::class, 'etiket_kisi', 'etiket_id', 'kisi_id')->withTimestamps();
    }
}
