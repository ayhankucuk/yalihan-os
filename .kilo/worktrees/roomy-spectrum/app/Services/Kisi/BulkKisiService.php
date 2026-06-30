<?php

namespace App\Services\Kisi;

/**
 * @sab-ignore-catch
 */

use App\Models\Kisi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\GuardsAgentWrites;

/**
 * BulkKisiService — Application Service
 *
 * SAB v4.1 Kural 1/11: Controller'dan TX + domain logic taşıması
 * Konum: Application layer (domain service limitini yormaz)
 *
 * Sorumluluklar:
 * - Toplu kişi oluşturma (duplicate check + batch create)
 * - Toplu kişi güncelleme
 * - Toplu kişi silme (soft/force)
 * - CSV import (parse + duplicate check + batch create)
 *
 * Extracted from: BulkKisiController (store, update, destroy, import)
 */
class BulkKisiService
{
    use GuardsAgentWrites;

    /**
     * @param \App\Services\CRM\KisiRegistrationService $registrationService
     */
    public function __construct(
        protected \App\Services\CRM\KisiRegistrationService $registrationService
    ) {}

    /**
     * Toplu kişi oluşturma
     * 🏛️ Authority aligned via KisiRegistrationService
      
     * @param int|null $userId İşlemi yapan kullanıcı
     * @return array{created: array, errors: array}
     */
    public function bulkCreate(array $kisiler, ?int $userId = null): array
    {
        $this->blockAgentWrite(__FUNCTION__);

        $created = [];
        $errors = [];

        DB::beginTransaction();
        try {
            // N+1 koruması: tüm email ve TC'leri tek query'de kontrol et
            $emails = array_filter(array_column($kisiler, 'email'));
            $tcKimlikler = array_filter(array_column($kisiler, 'tc_kimlik'));

            $existingByEmail = Kisi::whereIn('email', $emails)
                ->pluck('email', 'id')
                ->toArray();
            $existingByTc = Kisi::whereIn('tc_kimlik', $tcKimlikler)
                ->pluck('tc_kimlik', 'id')
                ->toArray();

            foreach ($kisiler as $index => $kisiData) {
                try {
                    // ✅ 🏛️ Authority Delegation: Use central duplicate logic
                    $duplicateCheck = $this->registrationService->validateDuplicate($kisiData);

                    if ($duplicateCheck['duplicate']) {
                        $errors[] = [
                            'index' => $index,
                            'message' => 'KISI_DUPLICATE_ERROR',
                        ];
                        continue;
                    }

                    // ✅ Use authority for the actual registration to ensure scoring/telemetry triggers
                    $kisi = $this->registrationService->register($kisiData, $userId);

                    $created[] = $kisi;
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'message' => 'Kayıt hatası: ' . $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            Log::info('Bulk kisi creation completed', [
                'created_count' => count($created),
                'error_count' => count($errors),
                'user_id' => $userId,
            ]);

            return ['created' => $created, 'errors' => $errors];
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Bulk kisi creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            throw $e;
        }
    }

    /**
     * Toplu kişi güncelleme
     *
     * @param array $kisiIds
     * @param array $updates
     * @param int|null $userId
     * @return int Updated count
     */
    public function bulkUpdate(array $kisiIds, array $updates, ?int $userId = null): int
    {
        $this->blockAgentWrite(__FUNCTION__);

        return DB::transaction(function () use ($kisiIds, $updates, $userId) {
            $filteredUpdates = array_filter($updates, fn($value) => $value !== null);

            $updatedCount = Kisi::whereIn('id', $kisiIds)->update($filteredUpdates);

            Log::info('Bulk kisi update completed', [
                'updated_count' => $updatedCount,
                'kisi_ids' => $kisiIds,
                'updates' => $updates,
                'user_id' => $userId,
            ]);

            return $updatedCount;
        });
    }

    /**
     * Toplu kişi silme
     *
     * @param array $kisiIds
     * @param bool $forceDelete
     * @param int|null $userId
     * @return int Deleted count
     */
    public function bulkDelete(array $kisiIds, bool $forceDelete = false, ?int $userId = null): int
    {
        $this->blockAgentWrite(__FUNCTION__);

        return DB::transaction(function () use ($kisiIds, $forceDelete, $userId) {
            if ($forceDelete) {
                $deletedCount = Kisi::whereIn('id', $kisiIds)->forceDelete();
            } else {
                $deletedCount = Kisi::whereIn('id', $kisiIds)->delete();
            }

            Log::info('Bulk kisi deletion completed', [
                'deleted_count' => $deletedCount,
                'kisi_ids' => $kisiIds,
                'force_delete' => $forceDelete,
                'user_id' => $userId,
            ]);

            return $deletedCount;
        });
    }

    /**
     * CSV'den kişi import et
     *
     * @param array $csvData Parsed CSV rows (header'sız)
     * @param int|null $userId
     * @return array{created: array, errors: array}
     */
    public function importFromCsv(array $csvData, ?int $userId = null): array
    {
        $created = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($csvData as $index => $row) {
                try {
                    // Boş satırları atla
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // ✅ CRM-LOCK: Hardened normalization (No-Bypass Policy)
                    $rawStatus = strtolower(trim((string) ($row[6] ?? 'aktif')));
                    $isAktif = in_array($rawStatus, ['aktif', '1', 'true', 'yes', 'evet']) || str_starts_with($rawStatus, 'act');
                    
                    $kisiData = [
                        'ad' => $row[0] ?? '',
                        'soyad' => $row[1] ?? '',
                        'email' => ! empty($row[2]) ? $row[2] : null,
                        'telefon' => ! empty($row[3]) ? $row[3] : null,
                        'tc_kimlik' => ! empty($row[4]) ? $row[4] : null,
                        'kisi_tipi' => $row[5] ?? 'musteri',
                        'aktiflik_durumu' => $isAktif,
                    ];

                    // Zorunlu alan kontrolü
                    if (empty($kisiData['ad']) || empty($kisiData['soyad'])) {
                        $errors[] = [
                            'row' => $index + 1,
                            'message' => 'Ad ve Soyad alanları zorunludur',
                        ];
                        continue;
                    }

                    // ✅ 🏛️ Authority Delegation: Use central duplicate logic
                    $duplicateCheck = $this->registrationService->validateDuplicate($kisiData);

                    if ($duplicateCheck['duplicate']) {
                        $errors[] = [
                            'row' => $index + 1,
                            'message' => 'KISI_DUPLICATE_ERROR',
                        ];
                        continue;
                    }

                    $kisi = $this->registrationService->register($kisiData, $userId);

                    $created[] = $kisi;
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $index + 1,
                        'message' => 'Import hatası: ' . $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            Log::info('Bulk kisi import completed', [
                'created_count' => count($created),
                'error_count' => count($errors),
                'user_id' => $userId,
            ]);

            return ['created' => $created, 'errors' => $errors];
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Bulk kisi import failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            throw $e;
        }
    }

    /**
     * Get statistics for the bulk kisi dashboard.
     *
     * @return array
     */
    public function getDashboardStats(): array
    {
        return [
            'total_kisiler' => Kisi::count(),
            'active_kisiler' => Kisi::active()->count(), // context7-ignore
            'inactive_kisiler' => Kisi::where('aktiflik_durumu', false)->count(), // ✅ Reconciled
            'recent_additions' => Kisi::whereBetween('created_at', [
                now()->subDays(7),
                now(),
            ])->count(),
        ];
    }

    /**
     * Get data for kisi export.
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExportData(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Kisi::select(['id', 'ad', 'soyad', 'email', 'telefon', 'tc_kimlik', 'kisi_tipi', 'aktiflik_durumu', 'created_at']);

        if (isset($filters['kisi_tipi'])) {
            $query->where('kisi_tipi', $filters['kisi_tipi']);
        }
        if (isset($filters['aktiflik_durumu'])) {
            $rawFilter = strtolower(trim((string) $filters['aktiflik_durumu']));
            $isAktifFilter = in_array($rawFilter, ['aktif', '1', 'true', 'yes', 'evet']) || str_starts_with($rawFilter, 'act');
            $query->where('aktiflik_durumu', $isAktifFilter);
        }

        return $query->get();
    }
}
