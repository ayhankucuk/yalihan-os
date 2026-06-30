<?php

namespace App\Actions\Address;

use App\Models\Address;
use Exception;

class DestroyAddressAction
{
    /**
     * @throws Exception
     */
    public function handle(Address $address): bool
    {
        return $address->delete();
    }
}
