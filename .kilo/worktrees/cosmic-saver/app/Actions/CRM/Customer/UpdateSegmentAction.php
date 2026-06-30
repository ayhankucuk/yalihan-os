<?php

namespace App\Actions\CRM\Customer;

use App\Models\Kisi;
use Exception;

class UpdateSegmentAction
{
    /**
     * @throws Exception
     */
    public function handle(Kisi $kisi, string $segment): Kisi
    {
        $kisi->update([
            'segment' => $segment,
        ]);

        return $kisi->fresh();
    }
}
