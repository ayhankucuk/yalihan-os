<?php

namespace App\Domain\Kisi\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Class KisiReadRepository
 * @package App\Domain\Kisi\Repositories
 * @description CQRS Okuma Katmanı: Kişi segmentasyon ve hızlı analiz sorgu deposu.
 */
final class KisiReadRepository
{
    private string $table = 'kisiler_read_model';

    public function __construct(private readonly int $tenantId) {}

    /**
     * Müşteri segmentine göre kişileri hızlıca listeler (Dropdown ve Autocomplete için).
     *
     * @param string $segment
     * @return Collection
     */
    public function getByMusteriSegmenti(string $segment): Collection
    {
        return DB::table($this->table)
            ->where('tenant_id', $this->tenantId)
            ->where('musteri_segmenti', $segment)
            ->where('aktiflik_durumu', 'aktif')
            ->orderBy('ad_soyad', 'asc')
            ->get(['id', 'ad_soyad', 'telefon_numarasi', 'eposta_adresi']); // Selective Select: Sadece ihtiyaç duyulan alanlar
    }
}
