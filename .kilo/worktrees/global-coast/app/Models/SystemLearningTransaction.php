<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * System Learning Transaction Model
 * Context7: C7-AUTO-LEARNING-2025-12-06
 *
 * Her işlemi kaydeden ve öğrenen sistem için transaction modeli
 */
class SystemLearningTransaction extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    protected $table = 'system_learning_transactions';

    protected $fillable = [
        'transaction_type',
        'module',
        'action',
        'related_type',
        'related_id',
        'input_data',
        'output_data',
        'context',
        'success',
        'performance_score',
        'execution_time_ms',
        'learned_patterns',
        'user_id',
        'ip_address',
        'user_agent',
        'executed_at',
    ];

    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'context' => 'array',
        'learned_patterns' => 'array',
        'success' => 'boolean',
        'performance_score' => 'decimal:2',
        'execution_time_ms' => 'integer',
        'executed_at' => 'datetime',
    ];

    /**
     * İlişkili kayıt (polymorphic)
     */
    public function related(): MorphTo
    {
        return $this->morphTo('related');
    }

    /**
     * Kullanıcı ilişkisi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Başarılı işlemler
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope: Başarısız işlemler
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope: Belirli modül
     */
    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope: Belirli transaction tipi
     */
    public function scopeType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope: Son N gün
     */
    public function scopeLastDays($query, int $days)
    {
        return $query->where('executed_at', '>=', now()->subDays($days));
    }
}
