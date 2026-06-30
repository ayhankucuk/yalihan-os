<?php

namespace App\Actions\CRM\Pipeline;

use App\Models\Kisi;
use Exception;

class UpdateCrmStageAction
{
    /**
     * @throws Exception
     */
    public function handle(Kisi $kisi, string $stage): Kisi
    {
        $kisi->update([
            'crm_surec_asamasi' => $stage,
        ]);

        return $kisi->fresh();
    }
}
