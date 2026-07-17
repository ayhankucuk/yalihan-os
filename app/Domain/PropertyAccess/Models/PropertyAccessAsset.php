<?php

namespace App\Domain\PropertyAccess\Models;

use App\Models\BaseModel;
use App\Models\Kisi;
use App\Models\Property;
use App\Models\User;
use App\Scopes\TenantScope;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * PropertyAccessAsset Model
 *
 * Sprint 12D — Physical key and access credential inventory.
 *
 * Sensitive fields (tanimlayici_no) are hidden by default.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $property_id
 * @property string $varlik_tipi
 * @property string|null $tanimlayici_no
 * @property string|null $tanim
 * @property string $durum
 * @property int $olusturan_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PropertyAccessAsset extends BaseModel
{
    use BelongsToTenant;
    use SoftDeletes;

    protected static $factory = \Database\Factories\PropertyAccessAssetFactory::class;

    protected $table = 'property_access_assets';

    protected $fillable = [
        'tenant_id',
        'property_id',
        'varlik_tipi',
        'tanimlayici_no',
        'tanim',
        'durum',
        'olusturan_id',
    ];

    protected $hidden = [
        'tanimlayici_no', // Sensitive — hidden from default serialization
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'property_id' => 'integer',
        'olusturan_id' => 'integer',
    ];

    // ─── Constants ────────────────────────────────────────────────────────

    public const STATUS_AKTIF = 'AKTIF';
    public const STATUS_KAYIP = 'KAYIP';
    public const STATUS_DEAKTIVE = 'DEAKTIVE';
    public const STATUS_IPTAL = 'IPTAL';

    public const TYPE_KEY = 'KEY';
    public const TYPE_SITE_CARD = 'SITE_CARD';
    public const TYPE_GARAGE_REMOTE = 'GARAGE_REMOTE';
    public const TYPE_SMART_LOCK = 'SMART_LOCK';
    public const TYPE_ALARM_CODE = 'ALARM_CODE';
    public const TYPE_STORAGE_KEY = 'STORAGE_KEY';

    // ─── Relationships ───────────────────────────────────────────────────

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function olusturan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'olusturan_id');
    }

    public function custodies(): HasMany
    {
        return $this->hasMany(PropertyKeyCustody::class, 'asset_id');
    }

    /**
     * Current holder — the latest non-returned custody record
     */
    public function currentHolder(): ?Kisi
    {
        $latest = $this->custodies()
            ->where('islem_tipi', '!=', PropertyKeyCustody::TYPE_IADE)
            ->orderBy('islem_tarihi', 'desc')
            ->first();

        return $latest?->kisi;
    }

    // ─── Scopes ─────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('durum', self::STATUS_AKTIF);
    }

    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    // ─── Domain Methods ──────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->durum === self::STATUS_AKTIF;
    }

    public function isSensitive(): bool
    {
        return in_array($this->varlik_tipi, [
            self::TYPE_ALARM_CODE,
            self::TYPE_SMART_LOCK,
        ]);
    }

    /**
     * Get credential for authorized viewer only.
     * Returns null if user is not authorized.
     */
    public function getCredentialForViewer(?User $user): ?string
    {
        if (!$user) {
            return null;
        }
        if ($user->hasRole(['admin', 'super-admin'])) {
            return $this->tanimlayici_no;
        }
        // Property managers can see credentials for their properties
        return null;
    }
}
