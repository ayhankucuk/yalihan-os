<?php

namespace App\Actions\CRM\Customer;

use App\Models\Kisi;
use App\Services\CRM\KisiScoringService;
use Exception;

class UpdatePipelineStageAction
{
    public function __construct(private KisiScoringService $scoringService) {}

    /**
     * @throws Exception
     */
    public function handle(Kisi $kisi, int $stage): Kisi
    {
        $kisi->update([
            'pipeline_stage' => $stage,
            'son_etkilesim' => now(),
        ]);

        // Skorları yeniden hesapla
        $kisi->update(['skor' => $this->scoringService->calculateScore($kisi)]);

        return $kisi->fresh();
    }
}
