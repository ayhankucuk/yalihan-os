<?php

namespace App\Domain\CRM\Services;

use App\Domain\CRM\Contracts\TalepRepositoryInterface;
use App\Models\Talep;
use App\Models\User;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\DB;

/**
 * DeleteTalepUseCase — Application Service (Domain Layer)
 *
 * Handles safe soft-deletion of Talepler with forensic logging and transaction boundary.
 */
class DeleteTalepUseCase
{
    public function __construct(
        private readonly TalepRepositoryInterface $talepRepository,
    ) {}

    /**
     * Delete a Talep.
     */
    public function execute(Talep $talep, ?User $actor = null): bool
    {
        return DB::transaction(function () use ($talep, $actor) {
            $talepId = $talep->id;
            $baslik = $talep->baslik;
            $kisiId = $talep->kisi_id;

            $result = (bool) $talep->delete();

            if ($result) {
                LogService::info('CRM Domain: Talep deleted', [
                    'talep_id' => $talepId,
                    'baslik'   => $baslik,
                    'kisi_id'  => $kisiId,
                    'actor_id' => $actor?->id,
                ]);
            }

            return $result;
        });
    }
}
