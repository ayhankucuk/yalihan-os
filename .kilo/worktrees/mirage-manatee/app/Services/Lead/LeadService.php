<?php

namespace App\Services\Lead;

use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Lead Service
 * Handles customer inquiries and leads.
 */
class LeadService
{
    /**
     * Create a new lead/inquiry.
     *
     * @param array $data
     * @return int
     */
    public function createLead(array $data): int
    {
        try {
            return DB::table('leads')->insertGetId(array_merge($data, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        } catch (\Exception $e) {
            Log::error('Lead creation failed in service', ['data' => $data, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
