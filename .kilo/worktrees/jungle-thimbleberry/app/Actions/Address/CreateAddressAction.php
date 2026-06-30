<?php

namespace App\Actions\Address;

use App\Models\Address;
use Illuminate\Support\Str;

class CreateAddressAction
{
    public function handle(array $data): Address
    {
        // Generate unique identifier if not provided
        if (empty($data['unique_id'])) {
            $data['unique_id'] = 'ADDR-' . strtoupper(Str::random(8));
        }

        return Address::create($data);
    }
}
