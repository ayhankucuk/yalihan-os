<?php

namespace App\Actions\Admin\Management;

use App\Models\KategoriYayinTipiFieldDependency;

class UpdateFieldDependencyAction
{
    public function handle(KategoriYayinTipiFieldDependency $field, array $data): KategoriYayinTipiFieldDependency
    {
        $field->update($data);
        return $field->fresh();
    }
}
