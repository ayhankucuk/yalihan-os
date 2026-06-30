<?php

namespace App\Governance\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Phase 4C — Async Event Flush Job
 *
 * Safety Guardrail #4: Telemetri critical path'i bloklamamalı.
 * Bu job afterResponse() ile tetiklenir, business işlemi bittikten sonra çalışır.
 *
 * Safety Guardrail #5: Fail-open — bu job başarısız olursa hiçbir şey etkilenmez.
 */
class FlushGovernanceEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    // Queue başarısız olursa tekrar deneme — ama business'ı kesmez
    public int $tries = 3;

    // Telemetri için yüksek öncelikli queue değil, düşük öncelikli
    public string $queue = 'governance';

    public function __construct(
        private readonly array $events
    ) {}

    public function handle(): void
    {
        if (empty($this->events)) {
            return;
        }

        try {
            // Batch insert — tek tek değil toplu yaz (performans)
            $rows = array_map(fn($event) => [
                'metric'       => $event['metric'],
                'tags'         => json_encode($event['tags'] ?? []),
                'is_violation' => $event['is_violation'] ?? false,
                'violation_type' => $event['violation_type'] ?? null,
                'severity'     => $event['severity'] ?? 'info',
                'trace_id'     => $event['trace_id'] ?? null,
                'request_id'   => $event['request_id'] ?? null,
                'tenant_id'    => $event['tenant_id'] ?? null,
                'source_class' => $event['source_class'] ?? null,
                'occurred_at'  => $event['occurred_at'] ?? now(),
                'created_at'   => now(),
            ], $this->events);

            DB::table('governance_events')->insert($rows);

        } catch (\Throwable $e) {
            // Fail-open: logla ama exception fırlatma
            Log::error('[GovernanceTelemetry] FlushJob başarısız', [
                'error'       => $e->getMessage(),
                'event_count' => count($this->events),
            ]);
        }
    }

    /**
     * Job başarısız olursa da business etkilenmez — sadece logla
     */
    public function failed(\Throwable $exception): void
    {
        Log::warning('[GovernanceTelemetry] FlushJob nihai başarısızlık', [
            'error' => $exception->getMessage(),
        ]);
    }
}
