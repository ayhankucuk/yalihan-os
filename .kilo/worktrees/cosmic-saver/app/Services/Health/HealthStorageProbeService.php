<?php

namespace App\Services\Health;

use Illuminate\Support\Facades\Storage;

class HealthStorageProbeService
{
    public function probeLocalDisk(string $testFile = '.health_check_test.txt'): bool
    {
        $disk = Storage::disk('local');
        $disk->put($testFile, 'test');
        $disk->delete($testFile);

        return true;
    }
}
