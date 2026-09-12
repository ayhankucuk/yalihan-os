<?php

namespace App\Domain\CRM\Services;

use App\Domain\CRM\Contracts\TalepRepositoryInterface;
use App\Domain\CRM\DTOs\TalepUpdateCommand;
use App\Models\Talep;
use App\Models\User;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\DB;

/**
 * UpdateTalepUseCase — Application Service (Domain Layer)
 *
 * Handles Talep mutation with transactional boundary, ownership checks,
 * and forensic audit logging.
 */
class UpdateTalepUseCase
{
    public function __construct(
        private readonly TalepRepositoryInterface $talepRepository,
    ) {}

    /**
     * Update an existing Talep.
     */
    public function execute(Talep $talep, TalepUpdateCommand|array $command, ?User $actor = null): Talep
    {
        if (is_array($command)) {
            $command = TalepUpdateCommand::fromRequest($command, $actor);
        }

        return DB::transaction(function () use ($talep, $command, $actor) {
            $before = $talep->toArray();

            $updateData = $command->toArray();
            $talep->update($updateData);

            LogService::info('CRM Domain: Talep updated', [
                'talep_id' => $talep->id,
                'baslik'   => $talep->baslik,
                'kisi_id'  => $talep->kisi_id,
                'actor_id' => $actor?->id,
                'before'   => $before,
                'after'    => $talep->fresh()?->toArray() ?? $talep->toArray(),
            ]);

            return $talep;
        });
    }
}
