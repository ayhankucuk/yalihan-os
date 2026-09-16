<?php

namespace App\Domain\CRM\Services;

use App\Domain\CRM\DTOs\TalepCreateCommand;
use App\Models\Talep;
use App\Models\User;
use App\Services\CRM\KisiRegistrationService;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CreateTalepUseCase — Application Service (Domain Layer)
 *
 * Handles Talep creation with automatic Kişi spillover (registration if kisi_id missing).
 * All mutations are wrapped in a database transaction.
 *
 * Strangler Fig: enable via config('crm.use_domain_talep', false)
 */
class CreateTalepUseCase
{
    public function __construct(
        private readonly KisiRegistrationService $kisiRegistrationService,
    ) {}

    /**
     * Execute Talep creation.
     *
     * If kisi_id is missing but Kişi fields are present, registers a new Kişi first.
     *
     * @throws \Exception on any failure (transaction always rolls back)
     */
    public function execute(TalepCreateCommand|array $command, ?User $actor = null): Talep
    {
        if (is_array($command)) {
            $command = TalepCreateCommand::fromRequest($command, $actor);
        }

        return DB::transaction(function () use ($command, $actor) {

            // 1. Kişi spillover — register if not provided
            $kisiId = $command->kisiId;
            if (empty($kisiId) && !empty($command->baslik)) {
                // Only attempt spillover if Kişi name fields exist in the original data
                // The command already resolved kisiId, so if it's empty we still need kisi info
                // which must come from the original request array — not the command
                // This is handled in executeFromSpillover below
                throw new \InvalidArgumentException('kisi_id is required when creating a Talep');
            }

            // 2. Build Talep data from command
            $talepData = $this->buildTalepData($command);

            // 3. Create Talep
            $talep = Talep::create($talepData);

            // 4. Forensic telemetry
            $this->logActivity('created', $talep, $actor);

            return $talep;
        });
    }

    /**
     * Execute creation with automatic Kişi registration spillover.
     * Use this when kisi_id is not provided but Kişi fields are.
     *
     * @param array $data Raw request data (may contain kisi_ad, kisi_soyad, etc.)
     * @param User|null $actor
     * @return Talep
     */
    public function executeFromSpillover(array $data, ?User $actor = null): Talep
    {
        return DB::transaction(function () use ($data, $actor) {

            // 1. Register Kişi if spillover data present
            $kisiId = $data['kisi_id'] ?? null;
            if (empty($kisiId) && !empty($data['kisi_ad'])) {
                $kisiData = [
                    'ad'       => $data['kisi_ad'],
                    'soyad'    => $data['kisi_soyad'] ?? null,
                    'telefon'  => $data['kisi_telefon'] ?? null,
                    'email'    => $data['kisi_email'] ?? null,
                    'kisi_tipi'=> 'lead',  // maps to KisiTipi::LEAD
                ];
                $kisi = $this->kisiRegistrationService->register($kisiData, $actor?->id);
                $kisiId = $kisi->id;
            }

            if (empty($kisiId)) {
                throw new \InvalidArgumentException('kisi_id or kisi_ad is required when creating a Talep');
            }

            // 2. Build command and create
            $data['kisi_id'] = $kisiId;
            $command = TalepCreateCommand::fromRequest($data, $actor);
            $talepData = $this->buildTalepData($command);
            $talep = Talep::create($talepData);

            // 3. Forensic telemetry
            $this->logActivity('created', $talep, $actor);

            return $talep;
        });
    }

    /**
     * Update an existing Talep.
     */
    public function update(Talep $talep, TalepCreateCommand|array $command, ?User $actor = null): Talep
    {
        if (is_array($command)) {
            $command = TalepCreateCommand::fromRequest($command, $actor);
        }

        return DB::transaction(function () use ($talep, $command, $actor) {
            $before = $talep->toArray();

            $talepData = $this->buildTalepData($command);
            $talep->update($talepData);

            $this->logActivity('updated', $talep, $actor, [
                'before' => $before,
                'after'  => $talep->toArray(),
            ]);

            return $talep;
        });
    }

    /**
     * Soft-delete a Talep.
     */
    public function delete(Talep $talep, ?User $actor = null): bool
    {
        return DB::transaction(function () use ($talep, $actor) {
            $result = (bool) $talep->delete();

            if ($result) {
                $this->logActivity('deleted', $talep, $actor, [
                    'talep_id' => $talep->id,
                    'baslik'   => $talep->baslik,
                ]);
            }

            return $result;
        });
    }

    // ─── Private Helpers ───────────────────────────────────────────────────

    private function buildTalepData(TalepCreateCommand $command): array
    {
        return [
            'baslik'           => $command->baslik,
            'aciklama'         => $command->aciklama,
            'talep_tipi'       => $command->talepTipi ?? 'satis',
            'alt_kategori_id'  => $command->altKategoriId,
            'talep_durumu'     => $command->talepDurumu,
            'il_id'            => $command->ilId,
            'ilce_id'          => $command->ilceId,
            'mahalle_id'       => $command->mahalleId,
            'kisi_id'          => $command->kisiId,
            'danisman_id'      => $command->danismanId ?? $command->actor?->id,
            'min_fiyat'       => $command->minFiyat,
            'max_fiyat'       => $command->maxFiyat,
            'notlar'           => $command->notlar,
        ];
    }

    private function logActivity(string $action, Talep $talep, ?User $actor, array $extra = []): void
    {
        \App\Services\Logging\LogService::info("CRM Authority: Talep {$action}", array_merge([
            'talep_id' => $talep->id,
            'baslik'   => $talep->baslik,
            'kisi_id'  => $talep->kisi_id,
            'actor_id' => $actor?->id,
        ], $extra));
    }
}
