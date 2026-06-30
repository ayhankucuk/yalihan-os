<?php

namespace App\Actions\Admin\Ups;

use App\Services\Ups\UpsHealthOptimizerService;

class RepairUpsHealthAction
{
    protected UpsHealthOptimizerService $optimizer;

    public function __construct(UpsHealthOptimizerService $optimizer)
    {
        $this->optimizer = $optimizer;
    }

    /**
     * Executes the UPS health repair/optimization logic.
     *
     * @return array
     */
    public function handle(): array
    {
        return $this->optimizer->optimizeAll();
    }
}
