<?php

namespace App\Actions\Admin\Ilan;

use App\Models\Ilan;

class StoreStructuredDataAction
{
    public function handle(Ilan $ilan, array $structuredData, string $scope): bool
    {
        return $ilan->update([
            'structured_data' => $structuredData,
            'structured_data_scope' => $scope,
            'schema_version' => 1,
        ]);
    }
}
