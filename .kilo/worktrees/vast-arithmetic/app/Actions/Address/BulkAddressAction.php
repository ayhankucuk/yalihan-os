<?php

namespace App\Actions\Address;

use App\Models\Address;
use App\Enums\AktiflikDurumu;

class BulkAddressAction
{
    public function handle(array $addressIds, string $action): int
    {
        $query = Address::whereIn('id', $addressIds);

        switch ($action) {
            case 'delete':
                return $query->delete();
            case 'activate':
                return $query->update(['aktiflik_durumu' => AktiflikDurumu::AKTIF->value]);
            case 'deactivate':
                return $query->update(['aktiflik_durumu' => AktiflikDurumu::PASIF->value]);
            default:
                return 0;
        }
    }
}
