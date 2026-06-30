<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Trait ListenerTelemetry
 * Phase 12 Listener Hardening
 * Asenkron listener'lara otomatik loglama, süre ölçümü ve hata takibi ekler.
 */
trait ListenerTelemetry
{
    /**
     * Listener'ın başladığı zaman.
     */
    protected float $telemetryStartTime = 0;

    /**
     * İşlem süresini ölçmeye başlar.
     */
    protected function startTelemetry(): void
    {
        $this->telemetryStartTime = microtime(true);
    }

    /**
     * İşlem bittiğinde süreyi hesaplayıp loglar.
     */
    protected function finishTelemetry(string $eventName, array $context = []): void
    {
        if ($this->telemetryStartTime === 0.0) {
            return;
        }

        $durationMs = round((microtime(true) - $this->telemetryStartTime) * 1000, 2);

        Log::info("[Event Telemetry] {$eventName} processed successfully.", array_merge([
            'listener' => static::class,
            'duration_ms' => $durationMs,
        ], $context));
    }

    /**
     * Hata durumunda tetiklenir (ShouldQueue -> failed methodu tarafından çağrılmalı).
     */
    public function recordFailure(\Throwable $exception, string $eventName, array $context = []): void
    {
        $durationMs = $this->telemetryStartTime > 0
            ? round((microtime(true) - $this->telemetryStartTime) * 1000, 2)
            : 0;

        Log::error("[Event Telemetry] {$eventName} FAILED!", array_merge([
            'listener' => static::class,
            'duration_ms' => $durationMs,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ], $context));

        // Cortex Anomaly Detection veya Slack webhook için özel bir Service çağrılabilir (ileride)
    }
}
