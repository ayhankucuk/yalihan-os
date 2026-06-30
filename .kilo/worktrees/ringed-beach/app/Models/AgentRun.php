<?php

namespace App\Models;

class AgentRun extends BaseModel
{
    protected $table = 'agent_runs';

    protected $casts = [
        'input_summary' => 'array',
        'output_summary' => 'array',
        'meta' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ─── Scopes ─────────────────────────────────────────────

    public function scopeForAgent($query, string $agentName)
    {
        return $query->where('agent_name', $agentName);
    }

    public function scopeCompleted($query)
    {
        return $query->where('agent_durumu', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('agent_durumu', 'failed');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }

    // ─── Methods ────────────────────────────────────────────

    public function markCompleted(array $outputSummary = [], int $findingsCount = 0, int $decisionsCount = 0): void
    {
        $this->update([
            'agent_durumu' => 'completed',
            'completed_at' => now(),
            'duration_ms' => $this->started_at->diffInMilliseconds(now()),
            'output_summary' => $outputSummary,
            'findings_count' => $findingsCount,
            'decisions_count' => $decisionsCount,
        ]);
    }

    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'agent_durumu' => 'failed',
            'completed_at' => now(),
            'duration_ms' => $this->started_at->diffInMilliseconds(now()),
            'error_message' => $errorMessage,
        ]);
    }
}
