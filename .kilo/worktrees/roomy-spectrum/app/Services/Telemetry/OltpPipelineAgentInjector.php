<?php

namespace App\Services\Telemetry;

use App\Exceptions\Governance\TelemetryTransportException;
use Illuminate\Support\Facades\Log;

/**
 * Class OltpPipelineAgentInjector
 * @package App\Services\Telemetry
 * @description SAB v24.2.0 uyarınca PHP-FPM iş parçacığını bloke etmeden telemetriyi yerel sidecar ajana üfleyen mühürlü boru hattı.
 */
final class OltpPipelineAgentInjector
{
    private mixed $socketConnection = null;

    /**
     * OltpPipelineAgentInjector constructor.
     */
    public function __construct()
    {
        // Konfigürasyon doğrudan anayasal SSOT düğümünden çekilir (SAB Madde 14)
    }

    /**
     * Metrikleri yerel yan sürece (Sidecar Daemon) non-blocking modda iletir.
     *
     * @param string $olayTuru
     * @param array<string, mixed> $metrikler
     * @return void
     */
    public function injectMetricsAsynchronously(string $olayTuru, array $metrikler): void
    {
        $config = config('yalihan.telemetry_budget_guards', []);
        if (!($config['aktiflik_durumu'] ?? false)) {
            return;
        }

        $isBreach = $metrikler['is_breach'] ?? false;
        $samplingRate = (float)($config['sampling_rate'] ?? 0.10);

        // Fail-Loud: Bariyer ihlalleri ve DLQ Fatal vakaları %100 işlenir, sağlıklı akışlar örneklenir.
        if (!$isBreach && (mt_rand() / mt_getrandmax()) > $samplingRate) {
            return;
        }

        try {
            $payload = json_encode([
                'trace_id'    => request()->header('X-Correlation-ID') ?? uniqid('trace_', true),
                'domain'      => 'YALIHAN_PERFORMANCE',
                'event_type'  => $olayTuru,
                'metrics'     => $metrikler,
                'captured_at' => microtime(true),
                'tenant_id'   => auth()->user()?->tenant_id ?? 1
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

            $this->streamToSidecar($payload, $config);

        } catch (\Throwable $exception) {
            // Gözlemlenebilirlik çöküşleri fail-silent geçiştirilemez (SAB Madde 4)
            Log::error("SAB TELEMETRY PIPELINE INJECTION FAILURE: " . $exception->getMessage(), [
                'exception_class' => get_class($exception)
            ]);
        }
    }

    /**
     * @param string $payload
     * @param array<string, mixed> $config
     * @throws TelemetryTransportException
     */
    private function streamToSidecar(string $payload, array $config): void
    {
        $endpoint = $config['endpoint'] ?? '/var/run/yalihan-telemetry.sock';
        $transport = $config['transport'] ?? 'socket';
        $timeout = (float)($config['timeout_seconds'] ?? 0.2);

        if ($this->socketConnection === null) {
            $remoteSocket = $transport === 'socket' ? "unix://{$endpoint}" : "udp://{$endpoint}";
            
            $this->socketConnection = @stream_socket_client(
                $remoteSocket,
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
            );
        }

        if (!$this->socketConnection) {
            $this->socketConnection = null;
            throw new TelemetryTransportException("Telemetry sidecar daemon unreachable via {$transport}: {$endpoint}");
        }

        // Akışı kesin olarak non-blocking moduna sabitle (Sıfır I/O Blokajı)
        stream_set_blocking($this->socketConnection, false);
        @fwrite($this->socketConnection, $payload . "\n");
    }

    /**
     * Destructor: Soket bağlantısını güvenli kapatır.
     */
    public function __destruct()
    {
        if ($this->socketConnection !== null) {
            @fclose($this->socketConnection);
        }
    }
}
