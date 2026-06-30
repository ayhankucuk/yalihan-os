<?php

namespace App\Services\Listing;

use DomainException;

/**
 * ListingStateMachine
 * SAB §5: Listing State Machine — 6 aşamalı geçiş kontrolü.
 * Taslak → Yayında direkt geçiş YASAKTIR.
 * Tüm geçişler burada doğrulanır. Controller state değiştiremez.
 */
class ListingStateMachine
{
    // Canonical integer mapping (SSOT state machine enum)
    const TASLAK          = 0;
    const BEKLEMEDE       = 1;
    const YAYINDA         = 2;
    const ARSIV           = 3;
    const PASIF           = 4;

    /**
     * Enum string -> StateMachine int dönüşümü
     * 🛡️ Phase 8: Shadow state mapping (Alignment with IlanDurumu enum)
     */
    public function normalizeToInt(mixed $durum): int
    {
        if (is_int($durum)) {
            return $durum;
        }

        $deger = mb_strtolower(trim((string) $durum));

        return match ($deger) {
            'taslak', 'draft'                         => self::TASLAK,
            'beklemede', 'pending', 'review'           => self::BEKLEMEDE,
            'yayinda', 'aktif', 'active', 'live'       => self::YAYINDA,
            'arsiv', 'arsive', 'archived', 'sold', 
            'satildi', 'satisildi', 'kirasildi'        => self::ARSIV,
            'pasif', 'passive', 'paused'               => self::PASIF,
            default => throw new \InvalidArgumentException(
                "Bilinmeyen yayin_durumu: '{$durum}'. Canonical değerleri: taslak|beklemede|yayinda|arsiv|pasif"
            ),
        };
    }

    /**
     * Geçerli durum geçiş matrisi
     * Hangi durumdan hangilerine geçilebilir?
     */
    protected array $allowedTransitions = [
        self::TASLAK    => [self::BEKLEMEDE],
        self::BEKLEMEDE => [self::YAYINDA, self::PASIF],
        self::YAYINDA   => [self::ARSIV, self::PASIF],
        self::ARSIV     => [self::TASLAK], // Yeniden ilana alma
        self::PASIF     => [self::TASLAK, self::YAYINDA],
    ];

    /**
     * İnsan okuyabilen durum isimleri (hata mesajları için)
     */
    protected array $durumIsimleri = [
        self::TASLAK    => 'Taslak',
        self::BEKLEMEDE => 'Beklemede',
        self::YAYINDA   => 'Yayında',
        self::ARSIV     => 'Arşiv',
        self::PASIF     => 'Pasif',
    ];

    /**
     * Geçişi doğrula
     *
     * @throws DomainException Geçersiz geçişte Fail-Fast
     */
    public function gecisYap(int $mevcutDurum, int $hedefDurum): void
    {
        if (!array_key_exists($mevcutDurum, $this->allowedTransitions)) {
            throw new DomainException(
                "Bilinmeyen durum: {$mevcutDurum}. Geçerli durumlar: " . implode(', ', array_keys($this->allowedTransitions))
            );
        }

        $izinliler = $this->allowedTransitions[$mevcutDurum];

        if (!in_array($hedefDurum, $izinliler, true)) {
            $mevcutIsim = $this->durumIsimleri[$mevcutDurum] ?? $mevcutDurum;
            $hedefIsim  = $this->durumIsimleri[$hedefDurum]  ?? $hedefDurum;

            throw new DomainException(
                "Geçersiz durum geçişi: {$mevcutIsim} → {$hedefIsim}. " .
                "İzin verilenler: " . implode(', ', array_map(
                    fn(int $d) => $this->durumIsimleri[$d] ?? $d,
                    $izinliler
                ))
            );
        }
    }

    /**
     * Mevcut durumdan izin verilen geçişleri döner
     */
    public function izinlenenGecisler(int $mevcutDurum): array
    {
        return array_map(
            fn(int $d) => ['deger' => $d, 'isim' => $this->durumIsimleri[$d] ?? $d],
            $this->allowedTransitions[$mevcutDurum] ?? []
        );
    }

    /**
     * Durum adını döner
     */
    public function durumIsmi(int $durum): string
    {
        return $this->durumIsimleri[$durum] ?? "Bilinmeyen ({$durum})";
    }

    /**
     * Yayında'ya geçiş için validation engine kontrolü
     * QualityEngine entegrasyonu için hook noktası
     */
    public function yayinIcinKontrolEt(int $kaliteSkoru, int $tamamlanmaSkoru): void
    {
        if ($tamamlanmaSkoru < 100) {
            throw new DomainException(
                "Yayın için tamamlanma skoru %100 olmalıdır. Mevcut: %{$tamamlanmaSkoru}."
            );
        }

        if ($kaliteSkoru < 40) {
            throw new DomainException(
                "Yayın için minimum kalite skoru %40 olmalıdır. Mevcut: %{$kaliteSkoru}."
            );
        }
    }
}
