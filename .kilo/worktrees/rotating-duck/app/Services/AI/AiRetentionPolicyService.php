<?php

namespace App\Services\AI;

use Carbon\Carbon;

class AiRetentionPolicyService
{
    /**
     * Check if a record should be retained based on its date
     *
     * @param Carbon|string $recordDate
     * @param string|null $tableName
     * @return bool
     */
    public function shouldRetain($recordDate, ?string $tableName = null): bool
    {
        $recordDate = $recordDate instanceof Carbon ? $recordDate : Carbon::parse($recordDate);
        $cutoffDate = $this->getCutoffDate($tableName);

        return $recordDate->greaterThan($cutoffDate);
    }

    /**
     * Get the cutoff date for retention
     * Records older than this should be archived
     *
     * @param string|null $tableName
     * @return Carbon
     */
    public function getCutoffDate(?string $tableName = null): Carbon
    {
        $retentionDays = $this->getRetentionDays($tableName);
        
        return now()->subDays($retentionDays);
    }

    /**
     * Get retention days for a specific table
     *
     * @param string|null $tableName
     * @return int
     */
    protected function getRetentionDays(?string $tableName = null): int
    {
        if ($tableName && config("ai-retention.tables.{$tableName}")) {
            return config("ai-retention.tables.{$tableName}.retention_days");
        }

        return config('ai-retention.default_retention_days', 90);
    }

    /**
     * Get all tables subject to retention policy
     *
     * @return array
     */
    public function getRetentionTables(): array
    {
        return array_keys(config('ai-retention.tables', []));
    }

    /**
     * Check if a table has archiving enabled
     *
     * @param string $tableName
     * @return bool
     */
    public function isArchiveEnabled(string $tableName): bool
    {
        return config("ai-retention.tables.{$tableName}.archive_enabled", false);
    }

    /**
     * Get the date column name for a table
     *
     * @param string $tableName
     * @return string
     */
    public function getDateColumn(string $tableName): string
    {
        return config("ai-retention.tables.{$tableName}.date_column", 'created_at');
    }
}
