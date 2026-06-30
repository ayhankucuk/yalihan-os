<?php

namespace App\Services\Ilan;

use App\Models\Ilan;
use App\Models\IlanFotografi;
use App\Models\YazlikRezervasyon;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * YazlikKiralamaService — Application Service
 *
 * SAB v4.1 Kural 1/11: Controller'dan TX + domain logic taşıması
 * Konum: Application layer (domain service limitini yormaz)
 *
 * Sorumluluklar:
 * - Yazlık kiralama ilanı oluşturma (ilan + fotoğraf)
 * - Yazlık kiralama ilanı güncelleme (ilan + yeni fotoğraf)
 * - Yazlık kiralama ilanı silme (fotoğraf dosyaları + kayıtlar + ilan)
 *
 * Extracted from: YazlikKiralamaController (store, update, destroy)
 */
class YazlikKiralamaService
{
    use GuardsAgentWrites;
    public function __construct(
        private IlanCrudService $ilanCrudService
    ) {}
    /**
     * Yeni yazlık kiralama ilanı oluştur
     *
     * @param array $ilanData Ilan model verileri
     * @param array $uploadedFiles UploadedFile nesneleri
     * @return Ilan
     */
    public function createListing(array $ilanData, array $uploadedFiles = []): Ilan
    {
        $this->blockAgentWrite('createListing');

        return DB::transaction(function () use ($ilanData, $uploadedFiles) {
            $ilan = $this->ilanCrudService->store($ilanData);

            $this->processPhotoUploads($ilan, $uploadedFiles);

            Log::info('Summer rental listing created via CrudService', [
                'ilan_id' => $ilan->id,
                'title' => $ilan->baslik,
                'user_id' => $ilanData['created_by'] ?? null,
            ]);

            return $ilan;
        });
    }

    /**
     * Yazlık kiralama ilanı güncelle
     *
     * @param Ilan $ilan Mevcut ilan
     * @param array $updateData Güncellenecek veriler
     * @param array $uploadedFiles Yeni fotoğraf dosyaları
     * @return Ilan
     */
    public function updateListing(Ilan $ilan, array $updateData, array $uploadedFiles = []): Ilan
    {
        $this->blockAgentWrite('updateListing');

        return DB::transaction(function () use ($ilan, $updateData, $uploadedFiles) {
            $ilan = $this->ilanCrudService->update($ilan, $updateData);

            if (! empty($uploadedFiles)) {
                $maxSira = $ilan->fotograflar()->max('display_order') ?? 0;
                $this->processPhotoUploads($ilan, $uploadedFiles, $maxSira);
            }

            Log::info('Summer rental listing updated via CrudService', [
                'ilan_id' => $ilan->id,
                'title' => $ilan->baslik,
            ]);

            return $ilan;
        });
    }

    /**
     * Yazlık kiralama ilanı sil (fotoğraflar + kayıtlar + ilan)
     *
     * @param Ilan $ilan Silinecek ilan
     * @return void
     */
    public function deleteListing(Ilan $ilan): void
    {
        $this->blockAgentWrite('deleteListing');

        // 1. Dosya yollarını TX öncesi topla
        $fotoYollari = $ilan->fotograflar->pluck('dosya_yolu')->filter()->toArray();

        // 2. DB işlemleri TX içinde — dosya IO burada yok
        DB::transaction(function () use ($ilan) {
            $ilan->fotograflar()->delete();
            $this->ilanCrudService->destroy($ilan);
        });

        // 3. File IO commit sonrası — DB rollback riski yok
        // Orphan files (cleanup hatası) kabul edilebilir; kayıp dosya (rollback sonrası) kabul edilemez
        if (! empty($fotoYollari)) {
            $existingFiles = array_filter($fotoYollari, fn($path) => Storage::disk('public')->exists($path));
            if (! empty($existingFiles)) {
                Storage::disk('public')->delete($existingFiles);
            }
        }

        Log::info('Summer rental listing deleted', [
            'ilan_id' => $ilan->id,
            'title' => $ilan->baslik,
        ]);
    }

    /**
     * Fotoğraf yükleme işlemi
     *
     * @param Ilan $ilan
     * @param array $files UploadedFile nesneleri
     * @param int $startSira Başlangıç sıra numarası
     */
    private function processPhotoUploads(Ilan $ilan, array $files, int $startSira = 0): void
    {
        foreach ($files as $index => $file) {
            $filename = time() . '_' . ($startSira + $index + 1) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('ilanlar/' . $ilan->id, $filename, 'public');

            IlanFotografi::create([
                'ilan_id' => $ilan->id,
                'dosya_adi' => $filename,
                'dosya_yolu' => $path,
                'display_order' => $startSira + $index + 1,
                'is_main' => ($startSira === 0 && $index === 0),
            ]);
        }
    }

    /**
     * Get monthly confirmed revenue.
     */
    public function getMonthlyRevenue(): float
    {
        return (float) \App\Models\YazlikRezervasyon::where(aktiflik_durumu, 'onaylandi')
            ->whereMonth('check_in', date('m'))
            ->whereYear('check_in', date('Y'))
            ->sum('toplam_fiyat');
    }

    /**
     * Update booking aktiflik status.
     *
     * @param  int  $bookingId
     * @param  array{aktiflik_durumu: mixed, iptal_nedeni?: string|null}  $data
     * @return YazlikRezervasyon
     */
    public function updateBookingStatus(int $bookingId, array $data): YazlikRezervasyon
    {
        $booking = YazlikRezervasyon::findOrFail($bookingId);
        $booking->update([
            'aktiflik_durumu' => $data['aktiflik_durumu'] ?? 1,
            'iptal_nedeni' => $data['iptal_nedeni'] ?? null,
            'updated_at' => now(),
        ]);

        return $booking;
    }
}

