<?php

namespace App\Models;

class AgentMemory extends BaseModel
{
    protected $table = 'agent_memory';

    protected $casts = [
        'memory_value' => 'array',
        'expires_at' => 'datetime',
    ];

    // ─── Scopes ─────────────────────────────────────────────

    public function scopeForAgent($query, string $agentName)
    {
        return $query->where('agent_name', $agentName);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('memory_type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    // ─── Static Helpers ─────────────────────────────────────

    /**
     * Store or update a memory entry (upsert by agent_name + memory_key).
     */
    public static function remember(string $agentName, string $key, string $type, array $value, ?\DateTimeInterface $expiresAt = null): self
    {
        return static::updateOrCreate(
            ['agent_name' => $agentName, 'memory_key' => $key],
            [
                'memory_type' => $type,
                'memory_value' => $value,
                'expires_at' => $expiresAt,
            ]
        );
    }

    /**
     * Recall a memory value. Returns null if expired or not found.
     */
    public static function recall(string $agentName, string $key): ?array
    {
        $memory = static::forAgent($agentName)
            ->where('memory_key', $key)
            ->active()
            ->first();

        return $memory?->memory_value;
    }

    /**
     * Increment a counter in memory (useful for pattern tracking).
     */
    public static function incrementCounter(string $agentName, string $key, string $type = 'counter'): int
    {
        $current = static::recall($agentName, $key);
        $count = ($current['count'] ?? 0) + 1;

        static::remember($agentName, $key, $type, [
            'count' => $count,
            'last_incremented' => now()->toIso8601String(),
        ]);

        return $count;
    }
}
