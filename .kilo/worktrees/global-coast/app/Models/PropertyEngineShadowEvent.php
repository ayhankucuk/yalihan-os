<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Property Engine Shadow Event Model
 *
 * Shadow testing event log for PropertyHub V3 comparison
 * Context7 Compliant: ✅
 *
 * @property int $id
 * @property string $mode
 * @property string $env
 * @property string $context_hash
 * @property string $v2_signature
 * @property string $v3_signature
 * @property bool $match
 * @property string|null $error_v2
 * @property string|null $error_v3
 * @property int|null $latency_ms_v2
 * @property int|null $latency_ms_v3
 * @property int|null $rule_count_v2
 * @property int|null $rule_count_v3
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PropertyEngineShadowEvent extends BaseModel
{
    use HasFactory;
    use HasCountryScope;
    protected $guarded = [];

    protected $casts = [
        'match' => 'boolean',
        'latency_ms_v2' => 'integer',
        'latency_ms_v3' => 'integer',
        'rule_count_v2' => 'integer',
        'rule_count_v3' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
