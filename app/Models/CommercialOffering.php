<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\Ilan;
use App\Models\Property;
use App\Models\PropertyReservation;
use App\Domain\Shared\ValueObjects\Money;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CommercialOffering extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'commercial_offerings';

    protected $fillable = [
        'tenant_id',
        'workspace_id',
        'property_id',
        'uuid',
        'idempotency_key',
        'offering_type',
        'fiyat',
        'para_birimi',
        'komisyon_orani',
        'depozito',
        'yayin_durumu',
        'baslangic_tarihi',
        'bitis_tarihi',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'workspace_id' => 'integer',
        'property_id' => 'integer',
        'fiyat' => 'decimal:2',
        'komisyon_orani' => 'decimal:2',
        'depozito' => 'decimal:2',
        'baslangic_tarihi' => 'date',
        'bitis_tarihi' => 'date',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (CommercialOffering $offering) {
            if (empty($offering->uuid)) {
                $offering->uuid = (string) Str::uuid();
            }
            if (empty($offering->yayin_durumu)) {
                $offering->yayin_durumu = 'DRAFT';
            }
            if (empty($offering->para_birimi)) {
                $offering->para_birimi = 'TRY';
            }
        });
    }

    /**
     * Get Money Value Object.
     */
    public function getMoney(): Money
    {
        return new Money(
            $this->fiyat ? (float) $this->fiyat : 0.0,
            $this->para_birimi ?? 'TRY'
        );
    }

    /**
     * Set Money Value Object.
     */
    public function setMoney(Money $money): void
    {
        $this->fiyat = $money->getAmount();
        $this->para_birimi = $money->getCurrency();
    }

    /**
     * CommercialOffering -> Property (N:1).
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /**
     * CommercialOffering -> Listings (1:N).
     * Decouples commercial pricing terms from marketing channels.
     */
    public function listings(): HasMany
    {
        return $this->hasMany(Ilan::class, 'commercial_offering_id');
    }

    /**
     * CommercialOffering -> Reservations (1:N).
     * Bookings are bound to a specific commercial offering contract.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(PropertyReservation::class, 'commercial_offering_id');
    }
}
