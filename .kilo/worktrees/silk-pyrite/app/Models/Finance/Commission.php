<?php

namespace App\Models\Finance;

use App\Enums\Finance\PaymentStatus;
use App\Models\Ilan;
use App\Models\BaseModel;
use App\Models\User;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Commission Model
 * 🛡️ SAB §12: Finance Domain Hardening
 */
class Commission extends BaseModel
{
    use HasCountryScope;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'ilan_id',
        'agent_id',
        'sale_price',
        'commission_rate',
        'total_commission',
        'office_share_percentage',
        'agent_share_percentage',
        'ofis_tutari',
        'danisman_tutari',
        'payment_state',
        'payout_date',
        'calculated_by',
        'paid_by',
        'invoice_number',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'payment_state' => PaymentStatus::class,
        'sale_price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'ofis_tutari' => 'decimal:2',
        'danisman_tutari' => 'decimal:2',
        'payout_date' => 'date',
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function calculator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }
}
