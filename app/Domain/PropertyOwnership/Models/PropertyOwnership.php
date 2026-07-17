<?php

namespace App\Domain\PropertyOwnership\Models;

use App\Models\BaseModel;
use App\Models\Kisi;
use App\Models\Property; // context7-ignore: canonical aggregate
use App\Models\User;
use App\Scopes\TenantScope;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * PropertyOwnership Model
 *
 * Sprint 12D — Immutable ownership history.
 *
 * Rules:
 * - property_id, kisi_id, pay_orani, baslangic_tarihi are IMMUTABLE after creation
 * - Records are only closed via controlled domain operation (set bitis_tarihi)
 * - No UPDATE or DELETE via general CRUD
 * - Idempotency key prevents duplicate commands
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $property_id
 * @property int $kisi_id
 * @property float $pay_orani
 * @property string $sahiplik_tipi
 * @property int|null $yetkili_temsilci_id
 * @property string $baslangic_tarihi
 * @property string|null $bitis_tarihi
 * @property string $atama_kaynagi
 * @property string|null $atama_notu
 * @property string $idempotency_key
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Property $property
 * @property-read Kisi $kisi
 * @property-read Kisi|null $yetkiliTemsilci
 */
class PropertyOwnership extends BaseModel
{
    use BelongsToTenant;
    use SoftDeletes;

    protected static $factory = \Database\Factories\PropertyOwnershipFactory::class;

    protected $table = 'property_ownerships';

    protected $fillable = [
        'tenant_id',
        'property_id',
        'kisi_id',
        'pay_orani',
        'sahiplik_tipi',
        'yetkili_temsilci_id',
        'baslangic_tarihi',
        'bitis_tarihi',
        'atama_kaynagi',
        'atama_notu',
        'idempotency_key',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'property_id' => 'integer',
        'kisi_id' => 'integer',
        'pay_orani' => 'decimal:4',
        'yetkili_temsilci_id' => 'integer',
        'baslangic_tarihi' => 'date',
        'bitis_tarihi' => 'date',
    ];

    // ─── Boot: Immutability Guard ──────────────────────────────────────

    public static function boot(): void
    {
        parent::boot();

        // Guard: No UPDATE on core identity fields — records are append-only
        // Exception: `bitis_tarihi` can be updated via close() domain operation
        static::updating(function (PropertyOwnership $record) {
            $dirty = $record->getDirty();
            $disallowed = ['pay_orani', 'kisi_id', 'property_id', 'baslangic_tarihi', 'sahiplik_tipi'];
            $changed = array_intersect(array_keys($dirty), $disallowed);
            if (!empty($changed)) {
                throw new \DomainException(
                    'PropertyOwnership records are immutable. Changes to [' . implode(', ', $changed) . '] are not permitted. Use closeOwnership() domain operation.'
                );
            }
        });

        // Guard: No DELETE
        static::deleting(function (PropertyOwnership $record) {
            throw new \DomainException(
                'PropertyOwnership records cannot be deleted. History must be preserved.'
            );
        });

        // Auto-generate idempotency key if not provided
        static::creating(function (PropertyOwnership $ownership) {
            if (empty($ownership->idempotency_key)) {
                $ownership->idempotency_key = (string) Str::uuid();
            }
        });
    }

    // ─── Scopes ─────────────────────────────────────────────────────────

    /**
     * Currently active ownership records (no end date)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('bitis_tarihi');
    }

    /**
     * Ownership records for a specific property
     */
    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    /**
     * Historical records (has end date)
     */
    public function scopeHistorical($query)
    {
        return $query->whereNotNull('bitis_tarihi');
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

    public function yetkiliTemsilci(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'yetkili_temsilci_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'olusturan_id');
    }

    // ─── Domain Methods ──────────────────────────────────────────────────

    /**
     * Check if this ownership record is currently active
     */
    public function isActive(): bool
    {
        return $this->bitis_tarihi === null;
    }

    /**
     * Close this ownership record (sets bitis_tarihi)
     * This is the ONLY way to close an ownership record
     */
    public function close(string $endDate, int $actorId): void
    {
        if (!$this->isActive()) {
            throw new \DomainException('This ownership record is already closed.');
        }
        $this->bitis_tarihi = $endDate;
        $this->save();
    }

    /**
     * Generate idempotency key for an assignment command
     */
    public static function generateIdempotencyKey(
        int $propertyId,
        int $kisiId,
        string $effectiveDate,
        string $commandType = 'assign'
    ): string {
        return hash('sha256', implode('|', [
            $commandType,
            (string) $propertyId,
            (string) $kisiId,
            $effectiveDate,
        ]));
    }
}
