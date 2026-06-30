<?php

namespace App\Actions\Admin\Ups;

use App\Models\TemplateVersion;

class RenameTemplateVersionAction
{
    public function handle(TemplateVersion $version, string $newName): bool
    {
        return $version->update(['version_name' => $newName]);
    }
}
