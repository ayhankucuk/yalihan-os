<?php

namespace App\Services\Reliability;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FilePipeline
{
    private array $pendingDeletes = [];
    private array $uploadedFiles = [];

    /**
     * Execute a callback in a database transaction with file safety guarantees.
     *
     * @param \Closure $callback
     * @return mixed
     * @throws \Throwable
     */
    public function transaction(\Closure $callback)
    {
        DB::beginTransaction();

        try {
            $result = $callback($this);
            
            DB::commit();

            // Transaction committed: perform actual deletions
            foreach ($this->pendingDeletes as $file) {
                if (Storage::disk($file['disk'])->exists($file['path'])) {
                    Storage::disk($file['disk'])->delete($file['path']);
                    Log::debug("🗑️ Physical file deleted on transaction commit", $file);
                }
            }

            return $result;

        } catch (\Throwable $e) {
            DB::rollBack();

            // Transaction rolled back: delete newly uploaded files to avoid orphans
            foreach ($this->uploadedFiles as $file) {
                if (Storage::disk($file['disk'])->exists($file['path'])) {
                    Storage::disk($file['disk'])->delete($file['path']);
                    Log::debug("🛡️ Orphan file cleaned up on transaction rollback", $file);
                }
            }

            throw $e;
        } finally {
            $this->pendingDeletes = [];
            $this->uploadedFiles = [];
        }
    }

    /**
     * Securely upload/put content into storage. Cleans up if rolled back.
     *
     * @param string $path
     * @param string $content
     * @param string $disk
     * @return void
     */
    public function secureUpload(string $path, string $content, string $disk = 'public'): void
    {
        Storage::disk($disk)->put($path, $content);
        $this->uploadedFiles[] = ['path' => $path, 'disk' => $disk];
    }

    /**
     * Securely upload an uploaded file instance. Cleans up if rolled back.
     *
     * @param string $path
     * @param mixed $file
     * @param string $name
     * @param string $disk
     * @return string|false
     */
    public function securePutFileAs(string $path, $file, string $name, string $disk = 'public')
    {
        $savedPath = Storage::disk($disk)->putFileAs($path, $file, $name);
        if ($savedPath) {
            $this->uploadedFiles[] = ['path' => $savedPath, 'disk' => $disk];
        }
        return $savedPath;
    }

    /**
     * Mark a file for deletion. Actual deletion only happens if transaction commits.
     *
     * @param string $path
     * @param string $disk
     * @return void
     */
    public function secureDelete(string $path, string $disk = 'public'): void
    {
        $this->pendingDeletes[] = ['path' => $path, 'disk' => $disk];
    }
}
