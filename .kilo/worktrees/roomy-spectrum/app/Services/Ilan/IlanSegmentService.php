<?php

namespace App\Services\Ilan;

use App\Models\Ilan;
use Illuminate\Support\Facades\DB;

/**
 * Ilan Segment Service
 * Handles segment workflow and document management.
 */
class IlanSegmentService
{
    /**
     * Upload documents for an ilan.
     */
    public function uploadDocuments(Ilan $ilan, array $files): void
    {
        $documents = [];
        foreach ($files as $file) {
            $path = $file->store('ilan-documents/'.$ilan->id, 'public');

            $documents[] = [
                'ilan_id' => $ilan->id,
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($documents)) {
            DB::table('ilan_documents')->insert($documents);
        }
    }
}
