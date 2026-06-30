<?php

namespace App\Console\Commands\Bekci;

use Illuminate\Console\Command;
use App\Models\AiTelemetry;
use Illuminate\Support\Facades\DB;

/**
 * 🛡️ Yalıhan Bekçi - AI Performance Baseline Audit Command
 *
 * Denetler:
 * - Ortalama Latency Bütçesi
 * - p99 Latency Bütçesi
 * - GET > 1ms ve POST/Write > 10ms Anayasal İhlalleri
 *
 * SAB Compliance: Madde 5 (Performance Budget)
 */
class PerformanceAuditCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bekci:performance-audit
                            {--tenant= : Belirli bir kiracı (Tenant ID) filtresi}
                            {--limit=100 : İncelenecek maksimum telemetri satırı}
                            {--fail-on-breach : Latency bütçe aşımında hata kodu (1) fırlat}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AI telemetri verilerini analiz ederek p99 ve ortalama gecikme bütçelerini denetler';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🛡️  YALIHAN BEKÇİ - AI PERFORMANCE BASELINE AUDIT');
        $this->line('================================================================');

        $tenantId = $this->option('tenant');
        $limit = (int) $this->option('limit');
        $failOnBreach = $this->option('fail-on-breach');

        // Query setup
        $query = AiTelemetry::query();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
            $this->comment("Kiracı Filtresi: Tenant #{$tenantId}");
        }

        $totalRecords = $query->count();
        if ($totalRecords === 0) {
            $this->warn('⚠️  Sistemde kayıtlı AI telemetri verisi bulunamadı!');
            return self::SUCCESS;
        }

        $this->info("Toplam Analiz Edilen Kayıt: {$totalRecords} (Maksimum Limit: {$limit})");

        // Latency calculations
        $avgLatency = (float) $query->avg('response_time_ms');
        $p95Latency = $this->calculatePercentile($query, 0.95);
        $p99Latency = $this->calculatePercentile($query, 0.99);
        $maxLatency = $query->max('response_time_ms');

        // Display Summary Table
        $this->newLine();
        $this->info('📊 Gecikme Metrikleri Özeti (Ms)');
        $this->table(
            ['Ortalama Latency', 'p95 Latency', 'p99 Latency', 'Maksimum Latency'],
            [[
                number_format($avgLatency, 2) . ' ms',
                number_format($p95Latency ?? 0, 2) . ' ms',
                number_format($p99Latency ?? 0, 2) . ' ms',
                number_format($maxLatency ?? 0, 2) . ' ms'
            ]]
        );

        // Budget evaluation
        // Read requests (GET) latency budget: < 1ms (represented by simple mock/internal checks or telemetry)
        // Write requests (POST) latency budget: < 10ms
        $breaches = [];
        $records = (clone $query)->orderBy('id', 'desc')->take($limit)->get();

        foreach ($records as $record) {
            $isWrite = in_array(strtolower($record->feature), ['save', 'store', 'update', 'delete', 'post', 'write', 'fix', 'govern']);
            $budget = $isWrite ? 10 : 1; // Write < 10ms, Read < 1ms
            
            if ($record->response_time_ms > $budget) {
                $breaches[] = [
                    'id' => $record->id,
                    'tenant_id' => $record->tenant_id,
                    'provider' => $record->provider,
                    'feature' => $record->feature,
                    'denetim_tipi' => $isWrite ? 'POST (Write)' : 'GET (Read)',
                    'latency' => $record->response_time_ms . ' ms',
                    'budget' => $budget . ' ms',
                    'breach_amount' => ($record->response_time_ms - $budget) . ' ms',
                ];
            }
        }

        $this->newLine();
        if (empty($breaches)) {
            $this->info('✅ MÜKEMMEL: Tüm istekler anayasal gecikme bütçesine (Reads <1ms, Writes <10ms) uymaktadır!');
            return self::SUCCESS;
        }

        $this->error('🚨 BÜTÇE AŞIMI TESPİT EDİLDİ!');
        $this->table(
            ['ID', 'Tenant ID', 'Saglayici', 'Ozellik', 'Denetim Tipi', 'Sure', 'Limit', 'Sapma'],
            array_map(fn($b) => [
                $b['id'], $b['tenant_id'], $b['provider'], $b['feature'], $b['denetim_tipi'],
                "<fg=red>{$b['latency']}</>", $b['budget'], "<fg=yellow>+{$b['breach_amount']}</>"
            ], $breaches)
        );

        $this->newLine();
        $this->warn(sprintf('⚠️  Toplam %d adet latency bütçe aşımı tespit edildi.', count($breaches)));

        if ($failOnBreach) {
            $this->error('❌ Anayasal bütçe aşımı sebebiyle komut başarısız olarak işaretleniyor.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Helper to compute exact percentiles using skip/take.
     */
    private function calculatePercentile($query, float $percentile): ?float
    {
        $count = (clone $query)->count();
        if ($count === 0) {
            return null;
        }

        $offset = (int) ceil($count * $percentile);
        if ($offset <= 0) {
            $offset = 1;
        }

        return (float) (clone $query)
            ->orderBy('response_time_ms', 'asc')
            ->skip($offset - 1)
            ->take(1)
            ->value('response_time_ms');
    }
}
