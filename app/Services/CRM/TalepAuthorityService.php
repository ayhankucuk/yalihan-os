<?php

namespace App\Services\CRM;

use App\Models\Talep;
use App\Models\User;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Traits\GuardsAgentWrites;

/**
 * 🎯 TalepAuthorityService
 *
 * Canonical authority for the Demand (Talep) lifecycle.
 * Part of CRM Authority Hardening (T2-A).
 */
class TalepAuthorityService
{
    use GuardsAgentWrites;

    public function __construct(
        protected KisiRegistrationService $kisiRegistrationService
    ) {}

    /**
     * 🛰️ Create a new Talep (Authority Entrypoint)
     *
     * Handles customer registration spillover if kisi_id is missing.
     *
     * @param array $data
     * @param User|null $actor
     * @return Talep
     */
    public function createTalep(array $data, ?User $actor = null): Talep
    {
        $this->blockAgentWrite(__FUNCTION__);

        return DB::transaction(function () use ($data, $actor) {
            // 1. Handle Kisi Registration if needed (Sealing Leakage)
            if (empty($data['kisi_id']) && !empty($data['kisi_ad'])) {
                $kisiData = [
                    'ad' => $data['kisi_ad'],
                    'soyad' => $data['kisi_soyad'] ?? null,
                    'telefon' => $data['kisi_telefon'] ?? null,
                    'email' => $data['kisi_email'] ?? null,
                    'kisi_tipi' => 'Potansiyel',
                ];

                $kisi = $this->kisiRegistrationService->register($kisiData, $actor?->id);
                $data['kisi_id'] = $kisi->id;
            }

            // 2. Map data for Talep creation
            $talepData = $this->mapTalepData($data, $actor);

            // 3. Create Talep
            $talep = Talep::create($talepData);

            // 4. Forensic Telemetry
            $this->logActivity('created', $talep, $actor);

            return $talep;
        });
    }

    /**
     * 🛰️ Update an existing Talep (Authority Entrypoint)
     *
     * Fixes the previous "dead surface" in the controller.
     *
     * @param Talep $talep
     * @param array $data
     * @param User|null $actor
     * @return Talep
     */
    public function updateTalep(Talep $talep, array $data, ?User $actor = null): Talep
    {
        $this->blockAgentWrite(__FUNCTION__);

        return DB::transaction(function () use ($talep, $data, $actor) {
            $before = $talep->toArray();

            // Map and Update
            $talepData = $this->mapTalepData($data, $actor);
            $talep->update($talepData);

            // Forensic Telemetry
            $this->logActivity('updated', $talep, $actor, [
                'before' => $before,
                'after' => $talep->toArray(),
            ]);

            return $talep;
        });
    }

    /**
     * 🛰️ Delete a Talep (Authority Entrypoint)
     *
     * @param Talep $talep
     * @param User|null $actor
     * @return bool
     */
    public function deleteTalep(Talep $talep, ?User $actor = null): bool
    {
        $this->blockAgentWrite(__FUNCTION__);

        return DB::transaction(function () use ($talep, $actor) {
            $talepId = $talep->id;
            $baslik = $talep->baslik;

            $result = $talep->delete();

            if ($result) {
                $this->logActivity('deleted', $talep, $actor, [
                    'talep_id' => $talepId,
                    'baslik' => $baslik,
                ]);
            }

            return (bool) $result;
        } );
    }

    /**
     * 🛰️ Set one_cikan flag (Authority Entrypoint for bulk operations)
     *
     * @param Talep $talep
     * @param bool $value
     * @param User|null $actor
     * @return Talep
     */
    public function setOneCikan(Talep $talep, bool $value, ?User $actor = null): Talep
    {
        $this->blockAgentWrite(__FUNCTION__);

        return DB::transaction(function () use ($talep, $value, $actor) {
            $before = $talep->one_cikan;
            $talep->update(['one_cikan' => $value]);

            $this->logActivity('one_cikan_updated', $talep, $actor, [
                'before' => $before,
                'after' => $value,
            ]);

            return $talep;
        });
    }

    /**
     * Map request data to Talep model fields.
     */
    protected function mapTalepData(array $data, ?User $actor = null): array
    {
        return [
            'baslik' => $data['baslik'] ?? null,
            'aciklama' => $data['aciklama'] ?? null,
            'talep_tipi' => $data['tip'] ?? ($data['talep_tipi'] ?? null),
            'alt_kategori_id' => $data['alt_kategori_id'] ?? null,
            'talep_durumu' => $data['talep_durumu'] ?? null,
            'one_cikan' => $data['one_cikan'] ?? false,
            'il_id' => $data['il_id'] ?? null,
            'ilce_id' => $data['ilce_id'] ?? null,
            'mahalle_id' => $data['mahalle_id'] ?? null,
            'kisi_id' => $data['kisi_id'] ?? null,
            'danisman_id' => $data['danisman_id'] ?? ($actor?->id),
            'min_fiyat' => $data['min_fiyat'] ?? null,
            'max_fiyat' => $data['max_fiyat'] ?? null,
            'notlar' => $data['notlar'] ?? null,
        ];
    }

    /**
     * Standardized forensic logging.
     */
    protected function logActivity(string $action, Talep $talep, ?User $actor = null, array $metadata = []): void
    {
        $payload = array_merge([
            'talep_id' => $talep->id,
            'action' => $action,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'timestamp' => now()->toIso8601String(),
        ], $metadata);

        Log::channel('module_changes')->info("Talep Domain: {$action}", $payload);

        LogService::info("CRM Authority: Talep {$action}", [
            'talep_id' => $talep->id,
            'actor' => $actor?->id
        ]);
    }
}
