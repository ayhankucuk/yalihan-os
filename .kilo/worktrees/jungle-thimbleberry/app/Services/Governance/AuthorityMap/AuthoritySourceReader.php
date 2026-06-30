<?php

namespace App\Services\Governance\AuthorityMap;

use Illuminate\Support\Facades\File;
use RuntimeException;

class AuthoritySourceReader
{
    /**
     * Reads the .sab/authority.json file and returns its parsed content.
     * 
     * @param string $path
     * @return array
     * @throws RuntimeException
     */
    public function read(string $path = '.sab/authority.json'): array
    {
        $resolvedPath = base_path($path);
        
        if (!File::exists($resolvedPath)) {
            throw new RuntimeException("Authority source not found at: {$resolvedPath}");
        }

        $content = File::get($resolvedPath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Authority source JSON is invalid: " . json_last_error_msg());
        }

        return $data;
    }
}
