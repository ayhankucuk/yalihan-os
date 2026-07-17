<?php

namespace App\Domain\PropertyDocument\Models;

use App\Models\BaseModel;
use App\Models\Property;
use App\Models\User;
use App\Scopes\TenantScope;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * PropertyDocument Model
 *
 * Sprint 12D — Document classification and expiry tracking.
 * File storage is delegated to existing media infrastructure.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $property_id
 * @property string $dokuman_tipi
 * @property string|null $dosya_yolu
 * @property string|null $referans_no
 * @property string|null $yayin_tarihi
 * @property string|null $son_gecerlilik_tarihi
 * @property string $durum
 * @property string|null $notu
 * @property int $olusturan_id
 * @property string $idempotency_key
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PropertyDocument extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'property_documents';

    protected $fillable = [
        'tenant_id',
        'property_id',
        'dokuman_tipi',
        'dosya_yolu',
        'referans_no',
        'yayin_tarihi',
        'son_gecerlilik_tarihi',
        'durum',
        'notu',
        'olusturan_id',
        'idempotency_key',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'property_id' => 'integer',
        'olusturan_id' => 'integer',
        'yayin_tarihi' => 'date',
        'son_gecerlilik_tarihi' => 'date',
    ];

    // ─── Constants ────────────────────────────────────────────────────────

    public const STATUS_AKTIF = 'AKTIF';
    public const STATUS_SURESI_DOLMUS = 'SURESI_DOLMUS';
    public const STATUS_IPTAL = 'IPTAL';

    public const TYPE_TITLE_DEED = 'TITLE_DEED';
    public const TYPE_MANAGEMENT_AGREEMENT = 'MANAGEMENT_AGREEMENT';
    public const TYPE_OWNER_AUTHORIZATION = 'OWNER_AUTHORIZATION';
    public const TYPE_ID_DOCUMENT = 'ID_DOCUMENT';
    public const TYPE_COMPANY_DOCUMENT = 'COMPANY_DOCUMENT';
    public const TYPE_INSURANCE = 'INSURANCE';
    public const TYPE_OCCUPANCY_PERMIT = 'OCCUPANCY_PERMIT';
    public const TYPE_ZONING = 'ZONING';
    public const TYPE_UTILITY_SUBSCRIPTION = 'UTILITY_SUBSCRIPTION';
    public const TYPE_KEY_RECEIPT = 'KEY_RECEIPT';

    // ─── Relationships ───────────────────────────────────────────────────

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function olusturan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'olusturan_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('durum', self::STATUS_AKTIF);
    }

    public function scopeExpired($query)
    {
        return $query->where('durum', self::STATUS_SURESI_DOLMUS);
    }

    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query
            ->where('durum', self::STATUS_AKTIF)
            ->whereNotNull('son_gecerlilik_tarihi')
            ->where('son_gecerlilik_tarihi', '<=', now()->addDays($days));
    }

    // ─── Domain Methods ──────────────────────────────────────────────────

    public function isExpired(): bool
    {
        if (!$this->son_gecerlilik_tarihi) {
            return false;
        }
        return $this->son_gecerlilik_tarihi->isPast();
    }

    public function isActive(): bool
    {
        return $this->durum === self::STATUS_AKTIF && !$this->isExpired();
    }

    public function markExpired(): void
    {
        if ($this->durum !== self::STATUS_AKTIF) {
            return;
        }
        $this->durum = self::STATUS_SURESI_DOLMUS;
        $this->save();
    }

    public function invalidate(): void
    {
        if ($this->durum === self::STATUS_IPTAL) {
            return;
        }
        $this->durum = self::STATUS_IPTAL;
        $this->save();
    }

    public static function generateIdempotencyKey(
        int $propertyId,
        string $documentType,
        ?string $referenceNumber
    ): string {
        return hash('sha256', implode('|', [
            'attach',
            (string) $propertyId,
            $documentType,
            $referenceNumber ?? 'null',
        ]));
    }
}
