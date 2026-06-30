<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Observability;

use App\Models\PropertyConfigVersion;
use Illuminate\Support\Facades\Storage;

/**
 * Class GovernanceExportService
 *
 * Generates signed "Proof of Governance" reports.
 */
class GovernanceExportService
{
    public function __construct(
        private readonly GovernanceTimelineService $timelineService
    ) {}

    /**
     * Export a signed governance certificate for a version.
     */
    public function export(PropertyConfigVersion $version): array
    {
        $timeline = $this->timelineService->getLineage((string) ($version->tenant_id ?? 'SYSTEM'), 50);

        $report = [
            'sertifika_kimligi' => hash('sha256', implode('|', [
                (string) ($version->tenant_id ?? 'SYSTEM'),
                (string) $version->version_hash,
                (string) $version->signature,
            ])),
            'tenant_id' => $version->tenant_id,
            'version_hash' => $version->version_hash,
            'uygulama_zamani' => $version->applied_at?->toIso8601String(),
            'snapshot_imza' => $version->signature,
            'risk_seviyesi' => $version->risk_report['level'] ?? 'UNKNOWN',
            'denetim_izleri' => [
                'olusturma_zamani' => $version->created_at?->toIso8601String(),
                'yonetim_durumu' => $version->yonetim_durumu,
            ],
            'timeline_snapshot' => [
                'dugumler' => $timeline['nodes'] ?? [],
                'baglantilar' => $timeline['edges'] ?? [],
            ],
            'butunluk_dogrulandi' => true,
        ];

        $normalizedReport = json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $report['paket_hash_sha256'] = hash('sha256', $normalizedReport ?: '');
        $report['paket_imza'] = hash_hmac('sha256', $report['paket_hash_sha256'], (string) config('app.key'));
        $report['timeline_hash_sha256'] = hash('sha256', json_encode($report['timeline_snapshot'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');

        $filename = "governance/export/{$version->tenant_id}/governance_package_{$version->version_hash}.json";
        Storage::put($filename, json_encode($report, JSON_PRETTY_PRINT));

        return $report;
    }

    public function verify(string $relativePath): array
    {
        if (!Storage::exists($relativePath)) {
            return [
                'basarili' => false,
                'hata_mesaji' => 'Paket bulunamadi.',
            ];
        }

        $decoded = json_decode((string) Storage::get($relativePath), true) ?: [];
        $signature = (string) ($decoded['paket_imza'] ?? '');
        $paketHash = (string) ($decoded['paket_hash_sha256'] ?? '');

        $payload = $decoded;
        unset($payload['paket_hash_sha256'], $payload['paket_imza'], $payload['timeline_hash_sha256']);

        $recomputedHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        $recomputedSignature = hash_hmac('sha256', $recomputedHash, (string) config('app.key'));

        return [
            'basarili' => hash_equals($paketHash, $recomputedHash) && hash_equals($signature, $recomputedSignature),
            'hesaplanan_hash' => $recomputedHash,
            'hesaplanan_imza' => $recomputedSignature,
        ];
    }

    public function replay(array $paket): array
    {
        return [
            'tenant_id' => $paket['tenant_id'] ?? 'SYSTEM',
            'version_hash' => $paket['version_hash'] ?? null,
            'timeline_snapshot' => $paket['timeline_snapshot'] ?? ['dugumler' => [], 'baglantilar' => []],
            'risk_seviyesi' => $paket['risk_seviyesi'] ?? 'UNKNOWN',
            'butunluk_dogrulandi' => (bool) ($paket['butunluk_dogrulandi'] ?? false),
        ];
    }
}
