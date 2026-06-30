<?php

namespace App\Modules\TakimYonetimi\Services;

use App\Modules\TakimYonetimi\Models\Gorev;
use App\Traits\GuardsAgentWrites;

/**
 * GorevService — Application Service
 *
 * SAB v4.1 Kural 1/11: Controller'dan mutation logic taşıması
 * Extracted from: GorevController + GorevApiController
 */
class GorevService
{
    use GuardsAgentWrites;
    /**
     * Assign a task to a user (Admin: danisman_id).
     */
    public function atamaYap(Gorev $gorev, int $userId): Gorev
    {
        $this->blockAgentWrite('atamaYap');

        $gorev->update(['atanan_user_id' => $userId]);

        return $gorev;
    }

    /**
     * Assign a task to a user (API: user_id).
     */
    public function atamaYapApi(Gorev $gorev, int $userId): Gorev
    {
        $this->blockAgentWrite('atamaYapApi');

        $gorev->update(['atanan_user_id' => $userId]);

        return $gorev;
    }

    /**
     * Update task durum (gorev_durumu).
     */
    public function durumGuncelle(Gorev $gorev, string $gorevDurumu): Gorev
    {
        $this->blockAgentWrite('durumGuncelle');

        // 'bekliyor' ve 'beklemede' aynı anlama geliyor, 'beklemede' olarak kaydet
        if ($gorevDurumu === 'bekliyor') {
            $gorevDurumu = 'beklemede';
        }

        $gorev->update(['gorev_durumu' => $gorevDurumu]);

        return $gorev;
    }

    /**
     * Update a task with validated data.
     */
    public function update(Gorev $gorev, array $data): Gorev
    {
        $this->blockAgentWrite('update');

        $gorev->update($data);

        return $gorev;
    }

    /**
     * Delete a task.
     */
    public function destroy(Gorev $gorev): void
    {
        $this->blockAgentWrite('destroy');

        $gorev->delete();
    }
}
