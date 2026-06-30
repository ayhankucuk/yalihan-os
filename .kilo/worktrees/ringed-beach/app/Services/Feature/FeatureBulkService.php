<?php

namespace App\Services\Feature;

/**
 * FeatureBulkService (Dummy Patch)
 *
 * This file was missing and causing BindingResolutionException.
 * Re-created as a dummy to allow app boot.
 */
class FeatureBulkService
{
    public function assignCategory($items, $categoryId) { return ['updated_count' => 0]; }
    public function toggleAktiflikDurumu($items, $durum) { return ['updated_count' => 0]; }
    public function bulkDelete($items) { return ['deleted_count' => 0]; }
    public function sirala($items) { return true; }
}
