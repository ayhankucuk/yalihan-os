<?php

namespace App\Agents\Contracts;

interface AgentContract
{
    /**
     * Agent identifier (e.g., 'cortex', 'governance', 'execution', 'optimizer', 'watcher').
     */
    public function name(): string;

    /**
     * Execute the agent's primary task.
     *
     * @param array $context Input data for the agent
     * @return array Result with at minimum 'success' key
     */
    public function run(array $context = []): array;

    /**
     * Report agent health metrics.
     *
     * @return array{agent: string, agent_durumu: string, last_run: ?string, recent_failures: int}
     */
    public function health(): array;
}
