<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\Ilan;
use App\Traits\BelongsToTenant;
use App\Traits\HasCountryScope;
use App\Domain\Property\ValueObjects\Location;
use App\Domain\Property\ValueObjects\TapuInfo;
use App\Domain\Property\ValueObjects\PhysicalSpecs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Property extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;
    use HasCountryScope;

    protected $table = 'properties';

    /**
     * When true, the workspace_id invariant guard is skipped.
     * Used by factory tests and legacy data migration.
     * @internal For testing and migration only.
     */
    public static bool $skipWorkspaceIdGuard = false;

    protected $fillable = [
        'tenant_id',
        'workspace_id',
        'uuid',
        'idempotency_key',
        'tkgm_id', // context7-ignore
        'ada',
        'parsel',
        'il_id',
        'ilce_id',
        'mahalle_id',
        'lat',
        'lng',
        'alan_m2',
        'bina_yasi',
        'kat_sayisi',
        'bulundugu_kat',
        'oda_sayisi',
        'banyo_sayisi',
        'aktiflik_durumu',
        'kapak_resmi',
        'nitelik',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'workspace_id' => 'integer',
        'il_id' => 'integer',
        'ilce_id' => 'integer',
        'mahalle_id' => 'integer',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'alan_m2' => 'decimal:2',
        'kat_sayisi' => 'integer',
        'bulundugu_kat' => 'integer',
        'banyo_sayisi' => 'integer',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Property $property) {
            if (empty($property->uuid)) {
                $property->uuid = (string) Str::uuid();
            }
            if (empty($property->aktiflik_durumu)) {
                $property->aktiflik_durumu = 'DRAFT';
            }

            // Invariant 1: Property cannot be created without a workspace
            // Skipped when $skipWorkspaceIdGuard is set (factory, migration)
            if (!self::$skipWorkspaceIdGuard
                && \Illuminate\Support\Facades\Schema::hasColumn('properties', 'workspace_id')) {
                if (empty($property->workspace_id)) {
                    throw new \DomainException('Property must belong to a Workspace.');
                }
            }
        });

        // Invariant 3: TKGM identity cannot be modified after creation
        static::updating(function (Property $property) {
            if ($property->isDirty('tkgm_id') && $property->getOriginal('tkgm_id') !== null) {
                throw new \DomainException('TKGM identity is immutable after Property creation.');
            }
            if ($property->isDirty('ada') && $property->getOriginal('ada') !== null) {
                throw new \DomainException('Tapu ada is immutable after Property creation.');
            }
            if ($property->isDirty('parsel') && $property->getOriginal('parsel') !== null) {
                throw new \DomainException('Tapu parsel is immutable after Property creation.');
            }
        });
    }

    /**
     * Get Location Value Object.
     */
    public function getLocation(): Location
    {
        return new Location(
            $this->il_id,
            $this->ilce_id,
            $this->mahalle_id,
            $this->lat ? (string) $this->lat : null,
            $this->lng ? (string) $this->lng : null
        );
    }

    /**
     * Set Location Value Object.
     */
    public function setLocation(Location $location): void
    {
        $this->il_id = $location->getIlId();
        $this->ilce_id = $location->getIlceId();
        $this->mahalle_id = $location->getMahalleId();
        $this->lat = $location->getLat();
        $this->lng = $location->getLng();
    }

    /**
     * Get TapuInfo Value Object.
     */
    public function getTapuInfo(): TapuInfo
    {
        return new TapuInfo(
            $this->ada,
            $this->parsel,
            $this->tkgm_id // context7-ignore
        );
    }

    /**
     * Set TapuInfo Value Object.
     */
    public function setTapuInfo(TapuInfo $tapuInfo): void
    {
        $this->ada = $tapuInfo->getAda();
        $this->parsel = $tapuInfo->getParsel();
        $this->tkgm_id = $tapuInfo->getTkgmId(); // context7-ignore
    }

    /**
     * Get PhysicalSpecs Value Object.
     */
    public function getPhysicalSpecs(): PhysicalSpecs
    {
        return new PhysicalSpecs(
            $this->alan_m2 ? (float) $this->alan_m2 : null,
            $this->oda_sayisi,
            $this->banyo_sayisi,
            $this->bina_yasi
        );
    }

    /**
     * Set PhysicalSpecs Value Object.
     */
    public function setPhysicalSpecs(PhysicalSpecs $specs): void
    {
        $this->alan_m2 = $specs->getAlanM2();
        $this->oda_sayisi = $specs->getOdaSayisi();
        $this->banyo_sayisi = $specs->getBanyoSayisi();
        $this->bina_yasi = $specs->getBinaYasi();
    }

    /**
     * Scope: by idempotency key.
     * Invariant 6: Same idempotency key returns existing Property, no duplicate.
     */
    public function scopeByIdempotencyKey($query, string $key)
    {
        return $query->where('idempotency_key', $key);
    }

    /**
     * Property → Listings relation (1:N).
     *
     * ADR-042 / SAAB v11 Sprint 11 M2:
     * A Property can have multiple Listings (e.g., Yalıhan satılık,
     * Sahibinden satılık, Airbnb günlük kiralama).
     */
    public function listings(): HasMany
    {
        return $this->hasMany(Ilan::class, 'property_id');
    }
}
