<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cortex Neural Connection Model
 * Context7: C7-CORTEX-NEURAL-NETWORK-2025-12-06
 */
class CortexNeuralConnection extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    protected $table = 'cortex_neural_connections';

    protected $fillable = [
        'source_module',
        'target_module',
        'connection_type',
        'connection_strength',
        'interaction_count',
        'success_rate',
        'avg_performance',
        'learned_patterns',
        'usage_context',
        'first_interaction_at',
        'last_interaction_at',
        'is_active',
    ];

    protected $casts = [
        'connection_strength' => 'decimal:2',
        'interaction_count' => 'integer',
        'success_rate' => 'decimal:2',
        'avg_performance' => 'decimal:2',
        'learned_patterns' => 'array',
        'usage_context' => 'array',
        'first_interaction_at' => 'datetime',
        'last_interaction_at' => 'datetime',
        'is_active' => \App\Enums\AktiflikDurumu::class,
    ];

    /**
     * Scope: Aktif bağlantılar
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', \App\Enums\AktiflikDurumu::AKTIF);
    }
}
