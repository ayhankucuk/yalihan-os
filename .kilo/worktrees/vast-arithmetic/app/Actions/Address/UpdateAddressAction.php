<?php

namespace App\Actions\Address;

use App\Models\Address;

class UpdateAddressAction
{
    public function handle(Address $address, array $data): Address
    {
        $address->update($data);
        return $address->fresh();
    }
}
