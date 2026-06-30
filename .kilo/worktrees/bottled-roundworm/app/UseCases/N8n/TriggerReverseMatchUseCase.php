<?php

namespace App\UseCases\N8n;

use App\Enums\IlanDurumu;

use App\Events\IlanCreated;
use App\Models\Ilan;
use App\UseCases\N8n\DTOs\TriggerReverseMatchDTO;

class TriggerReverseMatchUseCase
{
    /**
     * @throws \InvalidArgumentException
     */
    public function handle(TriggerReverseMatchDTO $dto): Ilan
    {
        // İlan'ı bul
        $ilan = Ilan::findOrFail($dto->ilanId);

        // Sadece IlanDurumu::YAYINDA->value ilanlar için event fire et
        if ($ilan->yayin_durumu !== IlanDurumu::YAYINDA->value) {
            throw new \InvalidArgumentException('Sadece aktif ilanlar için tersine eşleştirme yapılabilir', 400);
        }

        // IlanCreated event'ini manuel tetikle
        event(new IlanCreated($ilan));

        return $ilan;
    }
}
