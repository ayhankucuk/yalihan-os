<?php

namespace App\Models;

use App\Enums\PipelineAdimDurumu;
use App\Enums\PipelineDurumu;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PipelineRun extends BaseModel
{
    use HasCountryScope;

    protected $table = 'pipeline_runs';

    protected $fillable = [
        'run_uuid',
        'pipeline_type',
        'module',
        'pipeline_durumu',
        'mevcut_asama',
        'input_payload',
        'normalized_payload',
        'final_output',
        'karar_aksiyonu',
        'karar_gerekcesi',
        'total_steps',
        'completed_steps',
        'started_at',
        'finished_at',
        'triggered_by',
    ];

    protected $casts = [
        'pipeline_durumu' => PipelineDurumu::class,
        'input_payload' => 'array',
        'normalized_payload' => 'array',
        'final_output' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'total_steps' => 'integer',
        'completed_steps' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $run) {
            $run->run_uuid ??= (string) Str::uuid();
        });
    }

    // --- Relationships ---

    public function steps(): HasMany
    {
        return $this->hasMany(PipelineStep::class, 'pipeline_run_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'triggered_by');
    }

    // --- State helpers ---

    public function isTerminal(): bool
    {
        return $this->pipeline_durumu->isTerminal();
    }

    public function isRunning(): bool
    {
        return $this->pipeline_durumu->isRunning();
    }

    public function durationMs(): ?int
    {
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }

        return (int) $this->started_at->diffInMilliseconds($this->finished_at);
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->whereNotIn('pipeline_durumu', [
            PipelineDurumu::COMPLETED->value,
            PipelineDurumu::FAILED->value,
            PipelineDurumu::HALTED->value,
            PipelineDurumu::CANCELLED->value,
        ]);
    }

    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}
