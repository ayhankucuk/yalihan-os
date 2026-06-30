<?php

namespace App\Services\Admin;

use App\Models\Kisi;
use App\Services\Logging\LogService;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * KisiManagerService
 *
 * Context7: C7-KISI-MANAGER-2025-12-27
 * Kişi CRUD operasyonları (Hash + Role delegation)
 */
class KisiManagerService
{
    use GuardsAgentWrites;
    /**
     * Yeni kişi oluştur
     *
     * @param array $data
     * @return Kisi
     * @throws \Exception
     */
    public function store(array $data): Kisi
    {
        $this->blockAgentWrite('store');

        return DB::transaction(function () use ($data) {
            // ✅ SAB: Hash password in service layer
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $kisi = Kisi::create($data);

            LogService::info('Kisi created', [
                'kisi_id' => $kisi->id,
                'user_id' => auth()->id(),
            ]);

            return $kisi;
        });
    }

    /**
     * Kişi güncelle
     *
     * @param Kisi $kisi
     * @param array $data
     * @return Kisi
     * @throws \Exception
     */
    public function update(Kisi $kisi, array $data): Kisi
    {
        $this->blockAgentWrite('update');

        return DB::transaction(function () use ($kisi, $data) {
            // ✅ SAB: Hash new password if provided
            if (isset($data['password']) && !empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            // Capture ID before update to ensure persistence access
            $kisiId = $kisi->id;

            $kisi->update($data);

            LogService::info('Kisi updated', [
                'kisi_id' => $kisiId,
                'user_id' => auth()->id(),
            ]);

            // ✅ Safe Reload: Use withoutGlobalScopes to find the record even if status changed (e.g. active->passive)
            // Using findOrFail to ensure we return a valid model or fail loudly
            return Kisi::withoutGlobalScopes()->findOrFail($kisiId);
        });
    }

    /**
     * Kişi sil
     *
     * @param Kisi $kisi
     * @return bool
     * @throws \Exception
     */
    public function delete(Kisi $kisi): bool
    {
        $this->blockAgentWrite('delete');

        return DB::transaction(function () use ($kisi) {
            $kisiId = $kisi->id;

            $deleted = $kisi->delete();

            LogService::info('Kisi deleted', [
                'kisi_id' => $kisiId,
                'user_id' => auth()->id(),
            ]);

            return $deleted;
        });
    }

    /**
     * Kişi aktif/pasif durumunu değiştir
     *
     * @param Kisi $kisi
     * @param bool $aktif
     * @return Kisi
     */
    public function toggleActive(Kisi $kisi, bool $aktif): Kisi
    {
        $this->blockAgentWrite('toggleActive');

        $kisi->update(['aktif' => (int) $aktif]);

        LogService::info('Kisi activity toggled', [
            'kisi_id' => $kisi->id,
            'aktif' => $aktif,
            'user_id' => auth()->id(),
        ]);

        return $kisi->fresh();
    }
}
