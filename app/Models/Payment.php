<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use App\ValueObjects\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment — Checkout / Ödeme Kaydı
 *
 * CHECKOUT/ÖDEME AKIŞI — IMPLEMENTATION
 *
 * Rezervasyon–ödeme veri sözleşmesi:
 * - Bir rezervasyon (PropertyReservation) birden çok Payment kaydına sahip olabilir.
 * - Ödeme durum makinesi: pending → paid | failed (TransactionStatus değerleri).
 * - Ödeme sağlayıcı entegrasyonu YOK — mock / manuel onay akışı.
 * - Tenant izolasyonu: HasCountryScope (tenant_id + ulke_id).
 */
class Payment extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'payments';

    protected $fillable = [
        'tenant_id',
        'ulke_id',
        'reservation_id',
        'amount',
        'currency',
        'payment_method',
        'status',
        'reference',
        'notes',
        'idempotency_key',
        'recorded_by',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'tenant_id'      => 'integer',
        'ulke_id'        => 'integer',
        'reservation_id' => 'integer',
        'amount'         => 'decimal:2',
        'verified_at'    => 'datetime',
        'recorded_by'    => 'integer',
        'verified_by'    => 'integer',
    ];

    /**
     * İlişkili rezervasyon.
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(PropertyReservation::class, 'reservation_id')->withoutGlobalScopes();
    }

    /**
     * Ödemeyi kaydeden kullanıcı.
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Ödemeyi onaylayan kullanıcı.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Ödeme durumu terminal (paid | failed) mi?
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [TransactionStatus::PAID, TransactionStatus::FAILED], true);
    }

    /**
     * Ödeme onaylandı (paid) mı?
     */
    public function isPaid(): bool
    {
        return $this->status === TransactionStatus::PAID;
    }

    /**
     * Ödeme bekliyor (pending) mu?
     */
    public function isPending(): bool
    {
        return $this->status === TransactionStatus::PENDING;
    }
}