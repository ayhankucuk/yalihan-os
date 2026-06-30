<?php

namespace App\Models;

use App\Enums\PipelineAdimDurumu;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineStep extends BaseModel
{
    use HasCountryScope;

    protected $table = 'pipeline_steps';

    protected $fillable = [
        'pipeline_run_id',
        'adim_adi',
        'shard_key',
        'agent_adi',
        'adim_durumu',
        'queue_name',
        'input_payload',
        'output_payload',
        'hata_mesaji',
        'meta',
        'deneme_sayisi',
        'duration_ms',
        'worker_node',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'adim_durumu' => PipelineAdimDurumu::class,
        'input_payload' => 'array',
        'output_payload' => 'array',
        'meta' => 'array',
        'deneme_sayisi' => 'integer',
        'duration_ms' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    // --- Relationships ---

    public function pipelineRun(): BelongsTo
    {
        return $this->belongsTo(PipelineRun::class, 'pipeline_run_id');
    }

    // --- State helpers ---

    public function isTerminal(): bool
    {
        return $this->adim_durumu->isTerminal();
    }

    public function markRunning(): void
    {
        $this->update([
            'adim_durumu' => PipelineAdimDurumu::RUNNING,
            'started_at' => now(),
            'deneme_sayisi' => $this->deneme_sayisi + 1,
        ]);
    }

    public function markCompleted(array $output): void
    {
        $this->update([
            'adim_durumu' => PipelineAdimDurumu::COMPLETED,
            'output_payload' => $output,
            'finished_at' => now(),
            'duration_ms' => $this->started_at
                ? (int) $this->started_at->diffInMilliseconds(now())
                : null,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'adim_durumu' => PipelineAdimDurumu::FAILED,
            'hata_mesaji' => $error,
            'finished_at' => now(),
            'duration_ms' => $this->started_at
                ? (int) $this->started_at->diffInMilliseconds(now())
                : null,
        ]);
    }

    public function markSkipped(string $reason = 'Previous step failed'): void
    {
        $this->update([
            'adim_durumu' => PipelineAdimDurumu::SKIPPED,
            'hata_mesaji' => $reason,
            'finished_at' => now(),
        ]);
    }

    // --- Scopes ---

    public function scopeForStep($query, string $stepName)
    {
        return $query->where('adim_adi', $stepName);
    }

    public function scopeForShard($query, string $stepName, string $shardKey)
    {
        return $query->where('adim_adi', $stepName)->where('shard_key', $shardKey);
    }
}
