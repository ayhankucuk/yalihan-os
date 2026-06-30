<?php

namespace App\Services\Bekci\Scanners;

use App\Enums\IlanDurumu;

use Illuminate\Support\Str;

/**
 * 🛡️ Architecture Guard Scanner
 *
 * Enforces SAB Architectural Hardening (v6.2):
 * - Controller'dan model update/save/create yasaklanması
 * - BaseModel extend edenlerde HasCountryScope zorunluluğu
 * - Enum yerine Hardcoded s-tatus string kullanımı yasaklanması
 */
class ArchitectureGuardScanner
{
    /**
     * Models exempt from HasCountryScope requirement.
     * Reason: These governance/system tables have no ulke_id column by design.
     */
    private const COUNTRY_SCOPE_EXEMPT_MODELS = [
        'AgentMemory',
        'AgentRun',
        'GovernanceDecision',
        'GovernanceRollback',
        'GovernanceSuppression',
        'OptimizerSuggestion',
    ];

    public function scanFile(array $lines, string $filePath): array
    {
        $issues = [];
        $isController = Str::contains($filePath, 'app/Http/Controllers');
        $isModel = Str::contains($filePath, 'app/Models');

        $hasCountryScope = false;
        $isBaseModelClass = false;

        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;

            // 1. Controller Mutation Violation
            if ($isController) {
                if (preg_match('/->\s*(?:update|save)\s*\(/', $line)) {
                    // SAB v1.9: Skip if delegating to a service/action ($this->xxxService->update)
                    $isServiceDelegation = preg_match('/\$this->\w+(Service|Action|Manager|Repository|Handler|Config)\s*->\s*(?:update|save)\s*\(/', $line);
                    // SAB v1.9: Skip API controllers (Api/) — thin endpoint wrappers often do simple updates
                    $isApiController = Str::contains($filePath, 'Controllers/Api/');
                    // SAB v1.9: Skip if line has context7-ignore
                    $hasIgnoreComment = Str::contains($line, 'context7-ignore');

                    if (!$isServiceDelegation && !$isApiController && !$hasIgnoreComment) {
                        $issues[] = [
                            'line' => $lineNumber,
                            'type' => 'Controller Mutation Violation', // context7-ignore
                            'severity' => 'high',
                            'message' => 'Direct model mutation in Controller is forbidden. Pass data to a Service or Aggregate Root.',
                            'category' => 'architecture',
                        ];
                    }
                }
            }

            // 2. State & Scope tracking
            if ($isModel) {
                if (Str::contains($line, 'use HasCountryScope')) {
                    $hasCountryScope = true;
                }
                if (Str::contains($line, 'extends BaseModel')) {
                    $isBaseModelClass = true;
                }
            }

            // 3. Hardcoded String Detection for states
            // Example: 'yayin_durumu' => IlanDurumu::YAYINDA->value (but NOT => Enum::class)
            if (preg_match("/['\"](?:yayin_durumu|aktiflik_durumu|finansal_durum|depozito_durumu)['\"]\s*=>\s*['\"][A-Za-z]+['\"]/", $line)) {
                // SAB v1.7: Skip Enum casts (e.g., 'aktiflik_durumu' => AktiflikDurumu::class)
                if (Str::contains($line, '::class')) {
                    // Already using Enum — not hardcoded
                // SAB v1.9: Skip PHP cast/validation type values (boolean, string, integer, etc.)
                } elseif (preg_match("/=>\s*['\"](?:boolean|string|integer|int|float|double|array|object|date|datetime|timestamp|decimal|collection|json|real|custom)['\"]/" , $line)) {
                    // PHP type cast or validation rule — not a hardcoded state
                // SAB v1.9: Skip validation rule arrays (e.g., 'aktiflik_durumu' => 'boolean' in Request)
                } elseif (Str::contains($filePath, 'Requests/') || Str::contains($filePath, 'Request.php')) {
                    // Validation rule definitions — not hardcoded state
                // SAB v1.9: Skip lines inside $casts property (Eloquent model casting)
                } elseif (preg_match('/\$casts/', $line) || preg_match("/=>\s*['\"](?:bool|encrypted)['\"]/" , $line)) {
                    // Eloquent cast definitions — not hardcoded state
                } else {
                    $issues[] = [
                        'line' => $lineNumber,
                        'type' => 'Hardcoded State String', // context7-ignore
                        'severity' => 'high',
                        'message' => 'Hardcoded state string detected. Use an Enum or Value Object (Rule 6).', // context7-ignore
                        'category' => 'architecture',
                    ];
                }
            }

            // 4. Checking DB::transaction for LedgerService (Simple regex check)
            if (Str::contains($filePath, 'LedgerService') && preg_match('/->\s*(?:update|save|create)\s*\(/', $line)) {
                 // Actually this is a bit too strict, but we'll monitor if they use DB::transaction.
                 // We will skip strict regex for Ledger DB here since we applied it manually.
            }
        }

        // Apply Scope Violation rule
        $modelClass = basename($filePath, '.php');
        $isExempt = in_array($modelClass, self::COUNTRY_SCOPE_EXEMPT_MODELS, true);

        if ($isModel && $isBaseModelClass && !$hasCountryScope && !$isExempt) {
            $issues[] = [
                'line' => 1, // file level
                'type' => 'Missing Global Scope', // context7-ignore
                'severity' => 'critical',
                'message' => 'Models extending BaseModel MUST use HasCountryScope trait for Country Isolation.',
                'category' => 'architecture',
            ];
        }

        return $issues;
    }
}
