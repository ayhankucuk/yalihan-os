<?php

namespace App\Actions\Admin\PropertyHub;

use App\Models\PropertyConfigVersion;

class ApproveVersionAction
{
    public function handle(PropertyConfigVersion $version, string $targetState): bool
    {
        return $version->update(['yonetim_durumu' => $targetState]);
    }
}
