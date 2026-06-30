<?php

namespace App\Services\AI;

/**
 * @sab-ignore-catch
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiArchiveService
{
    protected AiRetentionPolicyService $retentionPolicy;

    public function __construct(AiRetentionPolicyService $retentionPolicy)
    {
        $this->retentionPolicy = $retentionPolicy;
    }

    /**
     * Archive old records from a table
     *
     * @param string $tableName
     * @return array Stats: moved, duration_ms
     */
    public function archiveOldRecords(string $tableName): array
    {
        $startTime = microtime(true);
        $movedCount = 0;

        if (!$this->retentionPolicy->isArchiveEnabled($tableName)) {
            return ['moved' => 0, 'duration_ms' => 0, 'skipped' => 'Archive not enabled'];
        }

        $archiveTable = "{$tableName}_archive";
        $cutoffDate = $this->retentionPolicy->getCutoffDate($tableName);
        $dateColumn = $this->retentionPolicy->getDateColumn($tableName);
        $batchSize = config('ai-retention.archive.batch_size', 500);

        try {
            do {
                $moved = DB::transaction(function () use ($tableName, $archiveTable, $dateColumn, $cutoffDate, $batchSize) {
                    // Get old records
                    $oldRecords = DB::table($tableName)
                        ->where($dateColumn, '<=', $cutoffDate)
                        ->limit($batchSize)
                        ->get();

                    if ($oldRecords->isEmpty()) {
                        return 0;
                    }

                    $recordIds = $oldRecords->pluck('id')->toArray();

                    // Insert into archive with archived_at timestamp
                    $archiveData = $oldRecords->map(function ($record) {
                        $data = (array) $record;
                        $data['archived_at'] = now();
                        return $data;
                    })->toArray();

                    DB::table($archiveTable)->insert($archiveData);

                    // Verify insertion
                    if (config('ai-retention.archive.verify_before_delete', true)) {
                        $archivedCount = DB::table($archiveTable)
                            ->whereIn('id', $recordIds)
                            ->count();

                        if ($archivedCount !== count($recordIds)) {
                            throw new \Exception("Archive verification failed for {$tableName}");
                        }
                    }

                    // Delete from original table
                    DB::table($tableName)->whereIn('id', $recordIds)->delete();

                    return count($recordIds);
                });

                $movedCount += $moved;

            } while ($moved > 0);

        } catch (\Exception $e) {
            Log::error("Archive failed for {$tableName}", [
                'error' => $e->getMessage(),
                'moved_before_error' => $movedCount
            ]);

            throw $e;
        }

        $durationMs = round((microtime(true) - $startTime) * 1000);

        return [
            'moved' => $movedCount,
            'duration_ms' => $durationMs,
        ];
    }

    /**
     * Archive all retention tables
     *
     * @return array
     */
    public function archiveAllTables(): array
    {
        $results = [];
        $tables = $this->retentionPolicy->getRetentionTables();

        foreach ($tables as $table) {
            try {
                $results[$table] = $this->archiveOldRecords($table);
            } catch (\Exception $e) {
                $results[$table] = [
                    'error' => $e->getMessage(),
                    'moved' => 0,
                    'duration_ms' => 0
                ];
            }
        }

        return $results;
    }
}
