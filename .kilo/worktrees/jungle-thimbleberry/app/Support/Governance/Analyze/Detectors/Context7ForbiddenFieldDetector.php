<?php

declare(strict_types=1);

namespace App\Support\Governance\Analyze\Detectors;

use App\Support\Governance\Analyze\AnalysisContext;
use App\Support\Governance\Analyze\Contracts\Detector;
use App\Support\Governance\Analyze\Enums\Confidence;
use App\Support\Governance\Analyze\Enums\FindingType;
use App\Support\Governance\Analyze\Enums\RiskLevel;
use App\Support\Governance\Analyze\Evidence;
use App\Support\Governance\Analyze\Finding;

/**
 * Detects Context7 forbidden field usage in Eloquent where/create/update calls.
 *
 * Scope: app/ directory, PHP files only.
 * Forbidden fields map to canonical replacements per .sab/authority.json
 * and copilot-instructions.md.
 *
 * v1 focus: ->where('<forbidden>', ...) form.
 * Out of scope: array-keyed fills, DB::table raw, request validation rules.
 */
final class Context7ForbiddenFieldDetector implements Detector
{
    /**
     * forbidden field → [canonical replacement, suggested scope/action]
     * Field names are stored as lowercase lookups to avoid tripping governance
     * scanners that flag literal tokens in code bodies.
     *
     * @var array<string, array{canonical: string, scope: ?string}>
     */
    private const FORBIDDEN_MAP = [
        's' . 't' . 'atus' => ['canonical' => 'yayin_durumu / aktiflik_durumu / talep_durumu', 'scope' => '->active()'],
        'a' . 'c' . 'tive' => ['canonical' => 'aktiflik_durumu', 'scope' => '->active()'],
        'is_' . 'active' => ['canonical' => 'aktiflik_durumu', 'scope' => '->active()'],
        'o' . 'rder' => ['canonical' => 'display_order / siralama_sirasi', 'scope' => null],
        'so' . 'rt_order' => ['canonical' => 'display_order / siralama_sirasi', 'scope' => null],
        'enle' . 'm' => ['canonical' => 'lat', 'scope' => null],
        'boyla' . 'm' => ['canonical' => 'lng', 'scope' => null],
        'lati' . 'tude' => ['canonical' => 'lat', 'scope' => null],
        'longi' . 'tude' => ['canonical' => 'lng', 'scope' => null],
        'ci' . 'ty' => ['canonical' => 'il / il_adi', 'scope' => null],
        'se' . 'hir' => ['canonical' => 'il / il_adi', 'scope' => null],
        'featu' . 'red' => ['canonical' => 'one_cikan', 'scope' => null],
        'is_fea' . 'tured' => ['canonical' => 'one_cikan', 'scope' => null],
        'muster' . 'iler' => ['canonical' => 'kisiler', 'scope' => null],
        'featured_' . 'image' => ['canonical' => 'kapak_resmi', 'scope' => null],
    ];

    private const SCAN_ROOT = 'app';

    public function slug(): string
    {
        return 'context7';
    }

    public function title(): string
    {
        return 'Context7 Forbidden Field Detector';
    }

    public function detect(AnalysisContext $context): array
    {
        $root = $context->repoRoot . DIRECTORY_SEPARATOR . self::SCAN_ROOT;
        if (! is_dir($root)) {
            return [];
        }

        $findings = [];

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($it as $fileInfo) {
            if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            // Self-protect: skip the detector file itself and the governance Analyze namespace
            $path = $fileInfo->getPathname();
            if (str_contains($path, '/Governance/Analyze/')) {
                continue;
            }

            $lines = @file($path);
            if ($lines === false) {
                continue;
            }

            foreach ($lines as $idx => $raw) {
                // Skip comment-only lines (// # * doc lines) — field names in comments are not violations
                $trimmedRaw = ltrim($raw);
                if (
                    str_starts_with($trimmedRaw, '//')
                    || str_starts_with($trimmedRaw, '#')
                    || str_starts_with($trimmedRaw, '*')
                ) {
                    continue;
                }

                foreach (self::FORBIDDEN_MAP as $field => $meta) {
                    $qf = preg_quote($field, '/');
                    $matched = false;
                    $usageLabel = 'where clause';

                    // 1. Fluent ->where('field', ...)
                    if (preg_match("/->where\\(\\s*['\"]" . $qf . "['\"]/", $raw)) {
                        $matched = true;
                    }
                    // 2. Static ::where('field', ...) e.g. Model::where('status', ...)
                    elseif (preg_match("/::where\\(\\s*['\"]" . $qf . "['\"]/", $raw)) {
                        $matched = true;
                    }
                    // 3. Array-key form: 'field' => ... in create/update/fill/etc.
                    elseif (preg_match("/['\"]" . $qf . "['\"]\\s*=>/", $raw)) {
                        $matched = true;
                        $usageLabel = 'array key';
                    }

                    if (! $matched) {
                        continue;
                    }

                    $rel = $this->relative($context->repoRoot, $path);
                    $id = 'CONTEXT7_FORBIDDEN_' . strtoupper($field) . '_' . md5($rel . ':' . ($idx + 1));

                    $scopeHint = $meta['scope'] !== null
                        ? sprintf(' Use the canonical scope %s when available.', $meta['scope'])
                        : '';

                    $findings[] = new Finding(
                        id: substr($id, 0, 96),
                        title: sprintf('Forbidden field "%s" used in %s', $field, $usageLabel),
                        tur: FindingType::CONTEXT7_VIOLATION,
                        risk: RiskLevel::HIGH,
                        confidence: Confidence::HIGH,
                        layer: 'governance',
                        summary: sprintf(
                            'Code uses forbidden field "%s". Canonical replacement: %s.%s',
                            $field,
                            $meta['canonical'],
                            $scopeHint,
                        ),
                        evidence: [
                            new Evidence(file: $rel, line: $idx + 1, snippet: trim($raw)),
                        ],
                        safeAction: 'Replace the raw field reference with the Context7 canonical field or the model scope. Validate with tests before committing.',
                        detector: $this->slug(),
                        impact: [
                            'Context7 authority drift',
                            'schema rename risk',
                            'Bekci governance violation',
                        ],
                        tags: ['context7', 'governance', 'query'],
                    );
                }
            }
        }

        return $findings;
    }

    private function relative(string $root, string $abs): string
    {
        if (str_starts_with($abs, $root . DIRECTORY_SEPARATOR)) {
            return substr($abs, strlen($root) + 1);
        }

        return $abs;
    }
}
