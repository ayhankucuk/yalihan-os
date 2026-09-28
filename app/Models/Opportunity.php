<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cortex Fırsat Modeli
 *
 * @sealed 2026-09-22
 *
 * @property int $id
 * @property int $ilan_id
 * @property int $lead_id
 * @property int $tenant_id
 * @property float $firsat_skoru
 * @property array|null $skor_detayi
 * @property string|null $firsat_nedeni
 * @property string|null $ikna_metni
 * @property string $firsat_durumu
 * @property bool $aktiflik_durumu
 */
class Opportunity extends BaseModel
{
    use HasFactory, Filterable, SoftDeletes, BelongsToTenant;

    protected $table = 'opportunities';

    protected $fillable = [
        'ilan_id',
        'lead_id',
        'tenant_id',
        'firsat_skoru',
        'skor_detayi',
        'firsat_nedeni',
        'ikna_metni',
        'firsat_durumu',
        'aktiflik_durumu',
    ];

    protected $casts = [
        'firsat_skoru' => 'float',
        'skor_detayi' => 'array',
        'aktiflik_durumu' => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
