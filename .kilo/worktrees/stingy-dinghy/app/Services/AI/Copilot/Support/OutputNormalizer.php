<?php

namespace App\Services\AI\Copilot\Support;

class OutputNormalizer
{
    public const MODE_STRICT = 'strict';
    public const MODE_TOLERANT = 'tolerant';

    /**
     * Direct decision-level status values.
     * Only these map to decision.action.
     */
    private const DECISION_STATUS_MAP = [
        'safe' => 'proceed',
        'warning' => 'proceed_with_caution',
        'caution' => 'proceed_with_caution',
        'unsafe' => 'block',
        'blocked' => 'block',
    ];

    /**
     * Operational/informational status values.
     * These are NOT decision values — moved to meta.
     */
    private const OPERATIONAL_STATUSES = [
        'ok', 'success', 'passed', 'completed',
        'error', 'failed', 'partial',
    ];

    /**
     * Known stage aliases → canonical stage names.
     */
    private const STAGE_ALIASES = [
        'analysis' => 'audit',
        'analyze' => 'audit',
        'review' => 'audit',
        'patch' => 'fix',
        'apply' => 'execution',
        'check' => 'verify',
        'validate' => 'verify',
        'governance' => 'govern',
    ];

    protected string $mode;

    /** @var string[] Tracks what normalizations were applied */
    protected array $changes = [];

    public function __construct(string $mode = self::MODE_TOLERANT)
    {
        $this->mode = $mode;
    }

    /**
     * Normalize raw agent output before contract validation.
     *
     * Strict mode: only maps format, never fills missing data.
     * Tolerant mode: maps format + fills missing required arrays.
     */
    public function normalize(array $payload): array
    {
        $this->changes = [];

        $payload = $this->normalizeStatus($payload);
        $payload = $this->normalizeStage($payload);

        if ($this->mode === self::MODE_TOLERANT) {
            $payload = $this->ensureRequiredArrays($payload);
        }

        if (!empty($this->changes)) {
            $payload = $this->injectNormalizationMeta($payload);
        }

        return $payload;
    }

    /**
     * Returns the list of normalization changes applied in the last normalize() call.
     */
    public function getLastChanges(): array
    {
        return $this->changes;
    }

    /**
     * Map legacy 'status' field → decision.action OR meta.
     *
     * Decision-level statuses (safe, warning, unsafe, etc.) → decision.action
     * Operational statuses (ok, success, passed, etc.) → meta.original_status
     */
    protected function normalizeStatus(array $payload): array
    {
        // context7-ignore: reading legacy field for normalization
        if (!isset($payload['status'])) {
            return $payload;
        }

        // context7-ignore: reading legacy field for normalization
        $raw = strtolower(trim($payload['status']));

        // Decision-level status → decision.action (only if no explicit decision)
        if (isset(self::DECISION_STATUS_MAP[$raw])) {
            if (!isset($payload['decision'])) {
                $payload['decision'] = [
                    'action' => self::DECISION_STATUS_MAP[$raw],
                    'reason' => 'Mapped from legacy status field',
                ];
                $this->changes[] = "status:{$raw}\u{2192}decision.action:" . self::DECISION_STATUS_MAP[$raw];
            }
        }
        // Operational status → meta (informational, not a decision)
        elseif (in_array($raw, self::OPERATIONAL_STATUSES, true)) {
            $payload['meta'] = array_merge($payload['meta'] ?? [], [
                // context7-ignore: preserving original value in meta
                'original_status' => $raw,
            ]);
            $this->changes[] = "status:{$raw}\u{2192}meta.original_status";
        }
        // Unknown status → block (safe default)
        else {
            if (!isset($payload['decision'])) {
                $payload['decision'] = [
                    'action' => 'block',
                    'reason' => "Unknown legacy status value: {$raw}",
                ];
                $this->changes[] = "status:{$raw}\u{2192}decision.action:block (unknown)";
            }
        }

        // context7-ignore: removing legacy field
        unset($payload['status']);
        $this->changes[] = 'status field removed';

        return $payload;
    }

    /**
     * Map legacy stage aliases to canonical stage names.
     * Unknown stages → 'govern' (safe fallback, halts pipeline).
     */
    protected function normalizeStage(array $payload): array
    {
        if (!isset($payload['stage'])) {
            return $payload;
        }

        $stage = strtolower(trim($payload['stage']));

        if (isset(self::STAGE_ALIASES[$stage])) {
            $canonical = self::STAGE_ALIASES[$stage];
            $payload['stage'] = $canonical;
            $this->changes[] = "stage:{$stage}\u{2192}{$canonical}";
        } elseif (!in_array($stage, OutputContract::ALLOWED_STAGES, true)) {
            // Unknown stage → GOVERN fallback (pipeline halt)
            $payload['stage'] = 'govern';
            $payload['warnings'] = array_merge($payload['warnings'] ?? [], [
                "Unknown stage '{$stage}' normalized to 'govern'",
            ]);
            $this->changes[] = "stage:{$stage}\u{2192}govern (unknown, pipeline halt)";
        }

        return $payload;
    }

    /**
     * Fill missing required array fields with empty defaults.
     * Only in tolerant mode. Tracks what was filled for transparency.
     */
    protected function ensureRequiredArrays(array $payload): array
    {
        $defaults = [
            'findings' => [],
            'fixes' => [],
            'execution' => [],
            'verification' => [],
        ];

        foreach ($defaults as $key => $default) {
            if (!array_key_exists($key, $payload)) {
                $payload[$key] = $default;
                $this->changes[] = "auto-filled missing {$key}";
            }
        }

        return $payload;
    }

    /**
     * Inject normalization audit trail into meta.
     */
    protected function injectNormalizationMeta(array $payload): array
    {
        $payload['meta'] = array_merge($payload['meta'] ?? [], [
            'normalization' => [
                'applied' => true,
                'mode' => $this->mode,
                'changes' => $this->changes,
            ],
        ]);

        return $payload;
    }
}
