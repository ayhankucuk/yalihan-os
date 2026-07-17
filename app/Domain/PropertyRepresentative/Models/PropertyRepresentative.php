<?php

namespace App\Domain\PropertyRepresentative\Models;

use App\Models\BaseModel;
use App\Models\Kisi;
use App\Models\Property; // context7-ignore: canonical aggregate
use App\Scopes\TenantScope;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * PropertyRepresentative Model
 *
 * Sprint 12D — Immutable representative assignments.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $property_id
 * @property int $kisi_id
 * @property string $temsil_yetu_tipi
 * @property string $baslangic_tarihi
 * @property string|null $bitis_tarihi
 * @property string|null $notu
 * @property string $idempotency_key
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PropertyRepresentative extends BaseModel
{
    use BelongsToTenant;
    use SoftDeletes;

    protected static $factory = \Database\Factories\PropertyRepresentativeFactory::class;

    protected $table = 'property_representatives';

    protected $fillable = [
        'tenant_id',
        'property_id',
        'kisi_id',
        'temsil_yetu_tipi',
        'baslangic_tarihi',
        'bitis_tarihi',
        'notu',
        'idempotency_key',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'property_id' => 'integer',
        'kisi_id' => 'integer',
        'baslangic_tarihi' => 'date',
        'bitis_tarihi' => 'date',
    ];

    // ─── Boot: Immutability Guard ──────────────────────────────────────

    public static function boot(): void
    {
        parent::boot();

        static::updating(function (PropertyRepresentative $record) {
            throw new \DomainException(
                'PropertyRepresentative records are immutable. Use close() domain operation.'
            );
        });

        static::deleting(function (PropertyRepresentative $record) {
            throw new \DomainException(
                'PropertyRepresentative records cannot be deleted.'
            );
        });

        static::creating(function (PropertyRepresentative $rep) {
            if (empty($rep->idempotency_key)) {
                $rep->idempotency_key = (string) Str::uuid();
            }
        });
    }

    // ─── Scopes ─────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereNull('bitis_tarihi');
    }

    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('temsil_yetu_tipi', $type);
    }

    // ─── Relationships ───────────────────────────────────────────────────

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function kisi(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'kisi_id');
    }

    // ─── Domain Methods ──────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->bitis_tarihi === null;
    }

    public function close(string $endDate): void
    {
        if (!$this->isActive()) {
            throw new \DomainException('This representative assignment is already closed.');
        }
        $this->bitis_tarihi = $endDate;
        $this->save();
    }
}
