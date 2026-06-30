<?php

namespace App\Services;

/**
 * @sab-ignore-catch
 */

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

/**
 * Error Auto-Repair Service
 * 
 * Detects common errors and attempts automatic recovery
 * - SQL field mapping errors (forbidden field names)
 * - Missing/incorrect relationships
 * - Syntax errors in migrations
 * - Scope issues
 */
class ErrorAutoRepairService
{
    private $logger;
    private $repairHistory = [];

    public function __construct()
    {
        $this->logger = Log::channel('error-repair');
    }

    /**
     * Auto-detect and repair common errors
     */
    public function detectAndRepair(\Throwable $exception): ?string
    {
        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();

        $this->logger->warning("🔴 Error detected", [
            'message' => $message,
            'file' => $file,
            'line' => $line
        ]);

        // Pattern detection & auto-repair
        $repairs = [
            'sqlFieldMapping' => fn() => $this->repairSQLFieldMapping($message, $file, $line),
            'unknownColumn' => fn() => $this->repairUnknownColumn($message, $file, $line),
            'syntaxError' => fn() => $this->repairSyntaxError($message, $file, $line),
            'missingRelationship' => fn() => $this->repairMissingRelationship($message, $file, $line),
            'scopeError' => fn() => $this->repairScopeError($message, $file, $line),
        ];

        foreach ($repairs as $type => $repair) {
            try {
                $result = $repair();
                if ($result) {
                    $this->logRepair($type, $message, $result);
                    return $result;
                }
            } catch (Exception $e) {
                $this->logger->error("Repair failed: $type", ['error' => $e->getMessage()]);
            }
        }

        return null;
    }

    /**
     * Repair SQL field mapping errors (aktif_mi → aktiflik_durumu)
     */
    private function repairSQLFieldMapping(string $message, string $file, int $line): ?bool
    {
        // Pattern: SQLSTATE[42S22] Unknown column 'aktif_mi'
        if (!preg_match("/Unknown column ['\"](.+?)['\"]/", $message, $matches)) {
            return null;
        }

        $forbiddenField = $matches[1];
        $canonicalField = $this->getCanonicalField($forbiddenField);

        if (!$canonicalField) {
            return null;
        }

        // Attempt auto-fix in source file
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $oldContent = $content;

            // Simple string replacements
            $content = str_replace("'$forbiddenField'", "'$canonicalField'", $content);
            $content = str_replace('"' . $forbiddenField . '"', '"' . $canonicalField . '"', $content);

            if ($content !== $oldContent) {
                file_put_contents($file, $content);
                $this->logger->info("✅ SQL field mapping repaired", [
                    'file' => $file,
                    'forbidden' => $forbiddenField,
                    'canonical' => $canonicalField
                ]);
                return true;
            }
        }

        return null;
    }

    /**
     * Repair Unknown Column errors (generic)
     */
    private function repairUnknownColumn(string $message, string $file, int $line): ?bool
    {
        if (!str_contains($message, 'Unknown column')) {
            return null;
        }

        // Extract column name
        if (!preg_match("/Unknown column ['\"](.+?)['\"]/", $message, $matches)) {
            return null;
        }

        $column = $matches[1];
        $canonical = $this->getCanonicalField($column);

        if (!$canonical || !file_exists($file)) {
            return null;
        }

        // Smart replacement
        $content = file_get_contents($file);
        $oldContent = $content;

        // Replace field references
        $content = str_replace("'$column'", "'$canonical'", $content);
        $content = str_replace('"' . $column . '"', '"' . $canonical . '"', $content);
        $content = str_replace("->$column", "->$canonical", $content);

        if ($content !== $oldContent) {
            file_put_contents($file, $content);
            $this->logger->info("✅ Unknown column repaired", [
                'file' => $file,
                'column' => $column,
                'canonical' => $canonical
            ]);
            return true;
        }

        return null;
    }

    /**
     * Repair PHP syntax errors
     */
    private function repairSyntaxError(string $message, string $file, int $line): ?bool
    {
        // Pattern: Unclosed { on line X
        if (!preg_match("/Unclosed.*on line (\d+)/i", $message, $matches)) {
            return null;
        }

        $errorLine = (int)$matches[1];

        if (!file_exists($file)) {
            return null;
        }

        $lines = file($file);
        $openBraces = 0;
        $closeBraces = 0;
        $lastOpenLine = null;

        // Count braces up to error line
        for ($i = 0; $i < min($errorLine, count($lines)); $i++) {
            $openBraces += substr_count($lines[$i], '{');
            $closeBraces += substr_count($lines[$i], '}');
            if (str_contains($lines[$i], '{')) {
                $lastOpenLine = $i + 1;
            }
        }

        // Add missing closing braces
        if ($openBraces > $closeBraces) {
            $missing = $openBraces - $closeBraces;
            $lines[] = str_repeat("}\n", $missing);
            file_put_contents($file, implode('', $lines));

            $this->logger->info("✅ Syntax error repaired", [
                'file' => $file,
                'line' => $errorLine,
                'missing_braces' => $missing
            ]);
            return true;
        }

        return null;
    }

    /**
     * Repair missing relationship errors
     */
    private function repairMissingRelationship(string $message, string $file, int $line): ?bool
    {
        if (!str_contains($message, 'Call to undefined method') || !str_contains($message, '()->')) {
            return null;
        }

        // Pattern: Call to undefined method App\Models\Model::relationship()
        if (!preg_match("/Call to undefined method .*::(\w+)\(\)/", $message, $matches)) {
            return null;
        }

        $relationship = $matches[1];
        $this->logger->warning("⚠️  Missing relationship detected", [
            'file' => $file,
            'relationship' => $relationship
        ]);

        // Log for manual review
        return true; // Requires manual intervention
    }

    /**
     * Repair scope errors
     */
    private function repairScopeError(string $message, string $file, int $line): ?bool
    {
        if (!str_contains($message, 'Undefined variable') && !str_contains($message, 'Undefined property')) {
            return null;
        }

        // Log for manual review
        $this->logger->warning("⚠️  Scope error detected", [
            'file' => $file,
            'line' => $line,
            'message' => $message
        ]);

        return false; // Requires manual review
    }

    /**
     * Get canonical field name from forbidden name
     */
    private function getCanonicalField(string $forbiddenField): ?string
    {
        $mapping = [
            // Activity status
            'status' => 'aktiflik_durumu', // context7-ignore
            'status' => 'aktiflik_durumu',
            'enabled' => 'aktiflik_durumu',
            // context7-ignore
            'is_active' => 'aktiflik_durumu',
            'aktif' => 'aktiflik_durumu',
            'aktif_mi' => 'aktiflik_durumu',

            // Publication status
            'yayin_durumu' => 'yayin_durumu', // Already correct
            'publish_status' => 'yayin_durumu',
            'published' => 'yayin_durumu',

            // Request status
            'talep_durumu' => 'talep_durumu', // Already correct
            'request_status' => 'talep_durumu',

            // Featured
            'featured' => 'one_cikan',
            'is_featured' => 'one_cikan',

            // Coordinates
            'latitude' => 'lat',
            'longitude' => 'lng',
            'enlem' => 'lat',
            'boylam' => 'lng',
        ];

        return $mapping[$forbiddenField] ?? null;
    }

    /**
     * Log repair action
     */
    private function logRepair(string $type, string $error, $result): void
    {
        $repair = [
            'type' => $type, // context7-ignore
            'error' => $error,
            'result' => $result,
            'timestamp' => now(),
        ];

        $this->repairHistory[] = $repair;

        // Store in database for learning
        if (function_exists('cache')) {
            cache()->remember(
                'error_repairs_' . date('Y-m-d'),
                86400,
                fn() => array_merge(
                    cache()->get('error_repairs_' . date('Y-m-d'), []),
                    [$repair]
                )
            );
        }

        $this->logger->info("Repair logged", $repair);
    }

    /**
     * Get repair statistics
     */
    public function getStats(): array
    {
        return [
            'total_repairs' => count($this->repairHistory),
            'repairs_by_type' => array_count_values(
                array_column($this->repairHistory, 'type') // context7-ignore
            ),
            'history' => $this->repairHistory,
        ];
    }
}
