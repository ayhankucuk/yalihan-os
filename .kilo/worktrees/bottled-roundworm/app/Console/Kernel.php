<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Services\Logging\LogService;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // TCMB Kur Güncelleme - Her gün 10:00'da (TCMB yayınlandıktan sonra)
        $schedule->command('exchange:update')
            ->dailyAt('10:00')
            ->appendOutputTo(storage_path('logs/exchange-rates.log'));

        // TestSprite otomatik öğrenme - Her gün 03:00'da
        $schedule->command('testsprite:auto-learn')
            ->dailyAt('03:00')
            ->appendOutputTo(storage_path('logs/testsprite-auto-learn.log'));

        // Unified Quality Gate - Her gece 03:00'te resmi zincir
        $schedule->command('quality:gate --with-context7')
            ->dailyAt('03:00')
            ->appendOutputTo(storage_path('logs/quality-gate.log'));

        // Context7: Query String Scanner - Her saat taraması (Controller/Route queries)
        $schedule->command('context7:query-scan --persist')
            ->hourly()
            ->appendOutputTo(storage_path('logs/context7-query-scan.log'));

        // Unified Quality Gate - Her 6 saatte bir derin doğrulama
        $schedule->command('quality:gate --with-context7')
            ->everySixHours()
            ->appendOutputTo(storage_path('logs/quality-gate-deep-check.log'));

        // Context7: Hot-Fix Real-time Scanner - Her saat başı (Dev modunda CPU tasarrufu)
        $schedule->command('context7:hot-fix --auto-repair')
            ->hourly()
            ->appendOutputTo(storage_path('logs/context7-hot-fix.log'));

        // 🤖 Context7: Smart Pattern Detection - Her 2 saatte bir
        $schedule->command('context7:smart-detect')
            ->everyTwoHours()
            ->appendOutputTo(storage_path('logs/context7-smart-detect.log'));

        // 🔍 Context7: Dependency Audit - Her gün 04:00'te
        $schedule->command('context7:dependency-audit')
            ->dailyAt('04:00')
            ->appendOutputTo(storage_path('logs/context7-dependency-audit.log'));

        // 👃 Context7: Code Smell Detection - Her gün 04:30'da
        $schedule->command('context7:smell-detect')
            ->dailyAt('04:30')
            ->appendOutputTo(storage_path('logs/context7-smell-detect.log'));

        // 📊 Context7: Compliance Score Report - Haftalık Pazartesi 02:00
        $schedule->command('context7:score-report')
            ->weekly()
            ->mondays()
            ->at('02:00')
            ->appendOutputTo(storage_path('logs/context7-score-report.log'));

        // 📋 Context7: Phase-Based Scan - Her gün 05:00'te
        $schedule->command('context7:phase-scan --all')
            ->dailyAt('05:00')
            ->appendOutputTo(storage_path('logs/context7-phase-scan.log'));

        // 📈 Context7: Trend Analysis - Haftalık Cuma 03:00'te
        $schedule->command('context7:trends --days=30')
            ->weekly()
            ->fridays()
            ->at('03:00')
            ->appendOutputTo(storage_path('logs/context7-trends.log'));

        // TestSprite otomatik test - Her 6 saatte bir
        $schedule->exec('cd ' . base_path('testsprite') . ' && ./test-run.sh')
            ->everySixHours()
            ->appendOutputTo(storage_path('logs/testsprite-tests.log'));

        // Context7 standard check - Haftalık
        $schedule->command('standard:check --type=context7')
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->appendOutputTo(storage_path('logs/context7-standard-check.log'));

        // Context7 daily compliance check - Her gün 09:00
        $schedule->exec(base_path('scripts/context7-daily-check.sh'))
            ->dailyAt('09:00')
            ->appendOutputTo(storage_path('logs/context7-daily-check.log'));

        // Context7: Takım Yönetimi Otomasyonu - Görev Deadline Kontrolü
        // Her gün sabah 08:00 ve öğleden sonra 14:00'te kontrol et
        $schedule->command('gorevler:check-deadlines --gun=1')
            ->dailyAt('08:00')
            ->appendOutputTo(storage_path('logs/gorev-deadline-check.log'));

        $schedule->command('gorevler:check-deadlines --gun=1')
            ->dailyAt('14:00')
            ->appendOutputTo(storage_path('logs/gorev-deadline-check.log'));

        // Cortex Avcı Modülü - Her saat başı sıcak fırsat taraması
        $schedule->command('cortex:hunt')
            ->hourly()
            ->appendOutputTo(storage_path('logs/cortex-hunt.log'));

        // Context7: Queue Worker Alert System - Her 5 dakikada bir kontrol
        // C7-QUEUE-WORKER-ALERT-2025-12-01
        $schedule->command('queue:check-worker')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                LogService::error('CheckQueueWorker: Cron job başarısız oldu');
            });

        // 🛡️ Yalıhan Bekçi: MCP Audit Server - Otonom Context7 izleme
        // Her saat başı Telescope ve kod tabanını tara
        $schedule->command('bekci:audit --report')
            ->hourly()
            ->appendOutputTo(storage_path('logs/bekci-audit.log'))
            ->onFailure(function () {
                LogService::error('BekciAudit: MCP Audit taraması başarısız oldu');
            });

        // 🧠 AI: Continuous Optimization - Confidence Threshold Tuning
        // Her gün 03:15'te son 7 günlük veriye göre eşikleri güncelle
        $schedule->command('ai:optimize-thresholds --apply --window=7d')
            ->dailyAt('03:15')
            ->appendOutputTo(storage_path('logs/ai-optimization.log'))
            ->onFailure(function () {
                LogService::error('AiOptimizeThresholds: Eşik optimizasyonu başarısız oldu');
            });

        // 🏆 AI: Provider Optimization - Performance Profiling
        // Her gün 03:30'da sağlayıcı performanslarını (Accept Rate, Latency, Cost) analiz et
        $schedule->command('ai:recompute-provider-profiles --apply --window=7d')
            ->dailyAt('03:30')
            ->appendOutputTo(storage_path('logs/ai-provider-optimization.log'))
            ->onFailure(function () {
                LogService::error('AiRecomputeProviderProfiles: Sağlayıcı profili güncelleme başarısız oldu');
            });

        // 🧹 AI: Data Hygiene - Archive & Cleanup (90+ days)
        // Her gün 04:30'da eski AI telemetri verilerini arşivle
        $schedule->command('ai:data-hygiene')
            ->dailyAt('04:30')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/ai-data-hygiene.log'))
            ->onFailure(function () {
                LogService::error('AiDataHygiene: Veri arşivleme başarısız oldu');
            });

        // 📊 Telemetry & Observability (L5 - Self-Protecting System)
        // Her 10 dakikada bir anomali tespit et (error rate, latency spike, AI cost surge)
        $schedule->command('telemetry:detect-anomalies --alert')
            ->everyTenMinutes()
            ->environments(['production'])
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/telemetry-anomalies.log'))
            ->onFailure(function () {
                LogService::error('TelemetryAnomalies: Anomali tespit sistemi başarısız oldu');
            });

        // 📊 Phase 19: Visibility Intelligence — Daily Score Recalculation
        // Her gün 03:30'da tüm yayındaki ilanların skorlarını yeniden hesapla
        $schedule->command('ranking:recalculate-all')
            ->dailyAt('03:30')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/ranking-recalculate.log'))
            ->onFailure(function () {
                LogService::error('RankingRecalculate: Skor yeniden hesaplama başarısız oldu');
            });

        // 🛡️ Phase 19: Ranking Invariant Validation — Weekly
        // Her Pazar 04:00'te invariantları doğrula
        $schedule->command('ranking:validate-invariants')
            ->weekly()
            ->sundays()
            ->at('04:00')
            ->appendOutputTo(storage_path('logs/ranking-invariants.log'))
            ->onFailure(function () {
                LogService::error('RankingInvariants: İnvariant doğrulama başarısız oldu');
            });

        // 🔄 Kiralama Motoru: Airbnb iCal Senkronizasyonu
        // Her 15 dakikada bir çalıştır, içerideki 'sync_frequency_minutes' limitini Job kontrol eder
        $schedule->command('rental:sync-airbnb')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/rental-sync-airbnb.log'))
            ->onFailure(function () {
                LogService::error('SyncAirbnbCalendarFeed: iCal feed senkronizasyonu başarısız oldu!');
            });

        // 📈 AI: Deal Predictor & Daily Snapshots (SAB v16.5)
        // Her gün 04:00'te portföyü tara ve günlük snapshot üret
        $schedule->command('ai:scan-deals --limit=500')
            ->dailyAt('04:00')
            ->appendOutputTo(storage_path('logs/ai-deal-predictor.log'));

        $schedule->job(new \App\Jobs\AI\DailySnapshotsJob)
            ->dailyAt('04:30')
            ->onOneServer();

        // 🛡️ Phase 4C: Governance Alert Check — Her 5 dakikada bir
        $schedule->job(new \App\Governance\Jobs\GovernanceAlertCheckJob, 'governance')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->name('governance-alert-check');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
