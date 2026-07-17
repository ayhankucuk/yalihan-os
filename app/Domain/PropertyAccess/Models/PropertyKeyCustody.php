<?php

namespace App\Domain\PropertyAccess\Models;

use App\Models\BaseModel;
use App\Models\Kisi;
use App\Models\User;
use App\Scopes\TenantScope;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * PropertyKeyCustody Model
 *
 * Sprint 12D — Immutable custody transfer log.
 * Append-only: no updates, no deletes.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $asset_id
 * @property int $kisi_id
 * @property string $islem_tipi
 * @property string $islem_tarihi
 * @property string|null $notu
 * @property int $olusturan_id
 * @property string $idempotency_key
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PropertyKeyCustody extends BaseModel
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'property_key_custodies';

    // ─── Constants ────────────────────────────────────────────────────────

    public const TYPE_TESLIM = 'TESLIM';
    public const TYPE_IADE = 'IADE';
    public const TYPE_KAYIP_BILDIRIM = 'KAYIP_BILDIRIM';
    public const TYPE_YENILEME = 'YENILEME';

    protected $fillable = [
        'tenant_id',
        'asset_id',
        'kisi_id',
        'islem_tipi',
        'islem_tarihi',
        'notu',
        'olusturan_id',
        'idempotency_key',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'asset_id' => 'integer',
        'kisi_id' => 'integer',
        'olusturan_id' => 'integer',
        'islem_tarihi' => 'datetime',
    ];

    // ─── Boot: Immutability Guard ──────────────────────────────────────

    public static function boot(): void
    {
        parent::boot();

        static::updating(function (PropertyKeyCustody $record) {
            throw new \DomainException(
                'PropertyKeyCustody records are immutable. Custody changes are new records only.'
            );
        });

        static::deleting(function (PropertyKeyCustody $record) {
            throw new \DomainException(
                'PropertyKeyCustody records cannot be deleted. History must be preserved.'
            );
        });

        static::creating(function (PropertyKeyCustody $custody) {
            if (empty($custody->idempotency_key)) {
                $custody->idempotency_key = (string) Str::uuid();
            }
            if (empty($custody->islem_tarihi)) {
                $custody->islem_tarihi = now();
            }
        });
    }

    // ─── Relationships ───────────────────────────────────────────────────

    public function asset(): BelongsTo
    {
        return $this->belongsTo(PropertyAccessAsset::class, 'asset_id');
    }

    public function kisi(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'kisi_id');
    }

    public function olusturan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'olusturan_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────

    public function scopeForAsset($query, int $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    public function scopeHandover($query)
    {
        return $query->where('islem_tipi', self::TYPE_TESLIM);
    }

    public function scopeReturns($query)
    {
        return $query->where('islem_tipi', self::TYPE_IADE);
    }

    public function scopeLosses($query)
    {
        return $query->where('islem_tipi', self::TYPE_KAYIP_BILDIRIM);
    }

    // ─── Helpers ────────────────────────────────────────────────────────

    public function isHandover(): bool
    {
        return $this->islem_tipi === self::TYPE_TESLIM;
    }

    public function isReturn(): bool
    {
        return $this->islem_tipi === self::TYPE_IADE;
    }
}
