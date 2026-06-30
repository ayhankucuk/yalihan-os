<?php

namespace App\Actions\Admin\Management;

use App\Models\KategoriYayinTipiFieldDependency;

class DeleteFieldDependencyAction
{
    public function handle(KategoriYayinTipiFieldDependency $field): bool
    {
        return $field->delete();
    }
}
