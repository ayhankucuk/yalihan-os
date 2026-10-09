<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Address
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $il_id
 * @property int|null $ilce_id
 * @property int|null $mahalle_id
 * @property string|null $adres
 * @property string|null $postal_code
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string $aktiflik_durumu
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read Il|null $il
 * @property-read Ilce|null $ilce
 * @property-read Mahalle|null $mahalle
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Property[] $properties
 */
class Address extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'addresses';

    protected $fillable = [
        'il_id',
        'ilce_id',
        'mahalle_id',
        'address',
        'postal_code',
        'latitude',
        'longitude',
        'aktiflik_durumu',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * İl ilişkisi
     */
    public function il(): BelongsTo
    {
        return $this->belongsTo(Il::class, 'il_id');
    }

    /**
     * İlçe ilişkisi
     */
    public function ilce(): BelongsTo
    {
        return $this->belongsTo(Ilce::class, 'ilce_id');
    }

    /**
     * Mahalle ilişkisi
     */
    public function mahalle(): BelongsTo
    {
        return $this->belongsTo(Mahalle::class, 'mahalle_id');
    }

    /**
     * Bu adresi kullanan mülkler
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
