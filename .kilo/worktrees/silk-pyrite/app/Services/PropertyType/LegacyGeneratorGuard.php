<?php

namespace App\Services\PropertyType;

use Illuminate\Support\Facades\Log;

/**
 * Legacy Generator Guard — Karantina + Telemetry
 *
 * SAB Kural 6: Legacy üretim hattı ölçümsüz çalışamaz.
 *
 * - Feature flag arkasında
 * - Her çağrı telemetry log + metric
 * - Allowlist (tenant/route) ile kontrollü açılır
 * - Hard cut: 7–14 gün usage 0 → kaldır
 *
 * @see config/feature-flags.php
 * @see docs/adr/2026-02-22-legacy-generator-quarantine.md
 */
class LegacyGeneratorGuard
{
    public function __construct(
        private readonly PropertyTemplateGeneratorService $generator,
    ) {}

    /**
     * Legacy generator'ı guard ile çalıştır.
     *
     * @throws \RuntimeException flag kapalıysa veya allowlist dışıysa
     */
    public function generate(
        string $kategori,
        string $yayinTipi,
        string $altTur,
        array  $context = [],
    ): ?array {
        $this->assertAllowed($context);

        $metricContext = array_merge($context, [
            'kategori'   => $kategori,
            'yayin_tipi' => $yayinTipi,
            'alt_tur'    => $altTur,
        ]);

        // metric: legacy_generator_call_count
        Log::channel('telemetry')->info('legacy_generator_call', array_merge($metricContext, [
            'event'    => 'legacy_generator_call',
            'basarili' => null, // henüz bilinmiyor
        ]));

        try {
            $result = $this->generator->generate($kategori, $yayinTipi, $altTur);

            // metric: legacy_generator_success_count
            Log::channel('telemetry')->info('legacy_generator_success', array_merge($metricContext, [
                'event'    => 'legacy_generator_success',
                'basarili' => true,
            ]));

            return $result;
        } catch (\Throwable $e) {
            // metric: legacy_generator_fail_count
            Log::channel('telemetry')->warning('legacy_generator_fail', array_merge($metricContext, [
                'event'       => 'legacy_generator_fail',
                'basarili'    => false,
                'hata_mesaji' => $e->getMessage(),
            ]));

            throw $e;
        }
    }

    /**
     * Flag ve allowlist kontrolü — fail-fast.
     *
     * @throws \RuntimeException
     */
    public function assertAllowed(array $context = []): void
    {
        $enabled = config('feature-flags.legacy_generator_enabled', false);

        if (! $enabled) {
            // metric: fallback_trigger_count
            Log::channel('telemetry')->warning('legacy_generator_blocked', array_merge($context, [
                'event'    => 'legacy_generator_blocked',
                'basarili' => false,
                'sebep'    => 'feature_flag_disabled',
            ]));

            throw new \RuntimeException(
                'LegacyGeneratorBlocked: feature-flag kapalı. '
                . 'APP_LEGACY_GENERATOR_ENABLED=true olmadan çağrılamaz.'
            );
        }

        $allowlist = config('feature-flags.legacy_generator_allowlist', []);
        $allowRoutes = $allowlist['routes'] ?? [];

        if (! empty($allowRoutes)) {
            $currentRoute = request()->route()?->getName() ?? '';
            if (! in_array($currentRoute, $allowRoutes, true)) {
                Log::channel('telemetry')->warning('legacy_generator_blocked', array_merge($context, [
                    'event'          => 'legacy_generator_blocked',
                    'basarili'       => false,
                    'sebep'          => 'route_not_in_allowlist',
                    'istek_url'      => request()->fullUrl(),
                    'route'          => $currentRoute,
                    'allowlist'      => $allowRoutes,
                ]));

                throw new \RuntimeException(
                    "LegacyGeneratorBlocked: '{$currentRoute}' route allowlist dışında."
                );
            }
        }
    }

    /**
     * Flag aktif mi? (test/health check için)
     */
    public function isEnabled(): bool
    {
        return (bool) config('feature-flags.legacy_generator_enabled', false);
    }
}
