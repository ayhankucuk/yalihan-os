<?php

namespace App\Domain\CQRS\Aggregates;

use App\Domain\CQRS\AggregateRoot;

/**
 * Class IlanAggregate
 *
 * SAB Phase 15 Sprint 1: Ilan (Property) Domain Event Sourcing
 * Manages property lifecycle through immutable event stream.
 *
 * Domain Events:
 * - IlanOlusturuldu: Initial property registration
 * - IlanFiyatiDegistirildi: Price change
 * - IlanDurumuDegistirildi: Status change (aktif, pasif, satildi)
 * - IlanGorselleriGuncellendi: Images updated
 * - IlanYayindanKaldirildi: Property unpublished
 *
 * @package App\Domain\CQRS\Aggregates
 */
class IlanAggregate extends AggregateRoot
{
    /**
     * Current property state (reconstructed from events)
     *
     * @var array
     */
    protected array $state = [
        'baslik' => null,
        'fiyat' => null,
        'ilan_durumu' => null,
        'yayin_durumu' => null,
        'gorsel_sayisi' => 0,
        'son_guncelleme' => null,
    ];

    /**
     * Create a new property
     *
     * @param array $ilanData
     * @return void
     */
    public function ilanOlustur(array $ilanData): void
    {
        $this->recordEvent('IlanOlusturuldu', [
            'baslik' => $ilanData['baslik'],
            'fiyat' => $ilanData['fiyat'],
            'ana_kategori_id' => $ilanData['ana_kategori_id'],
            'alt_kategori_id' => $ilanData['alt_kategori_id'] ?? null,
            'il' => $ilanData['il'],
            'ilce' => $ilanData['ilce'],
            'ilan_durumu' => 'taslak',
            'yayin_durumu' => 0,
            'olusturulma_zamani' => now()->toIso8601String(),
        ]);

        $this->state['baslik'] = $ilanData['baslik'];
        $this->state['fiyat'] = $ilanData['fiyat'];
        $this->state['ilan_durumu'] = 'taslak';
        $this->state['yayin_durumu'] = 0;
    }

    /**
     * Change property price
     *
     * @param int $yeniFiyat
     * @param string|null $degisiklikNedeni
     * @return void
     */
    public function fiyatDegistir(int $yeniFiyat, ?string $degisiklikNedeni = null): void
    {
        $this->recordEvent('IlanFiyatiDegistirildi', [
            'eski_fiyat' => $this->state['fiyat'],
            'yeni_fiyat' => $yeniFiyat,
            'degisiklik_nedeni' => $degisiklikNedeni,
            'degistirilme_zamani' => now()->toIso8601String(),
        ]);

        $this->state['fiyat'] = $yeniFiyat;
        $this->state['son_guncelleme'] = now()->toIso8601String();
    }

    /**
     * Change property status
     *
     * @param string $yeniDurum (taslak, aktif, pasif, satildi, kiralandi)
     * @return void
     */
    public function durumDegistir(string $yeniDurum): void
    {
        $this->recordEvent('IlanDurumuDegistirildi', [
            'eski_durum' => $this->state['ilan_durumu'],
            'yeni_durum' => $yeniDurum,
            'degistirilme_zamani' => now()->toIso8601String(),
        ]);

        $this->state['ilan_durumu'] = $yeniDurum;
        $this->state['son_guncelleme'] = now()->toIso8601String();
    }

    /**
     * Update property images
     *
     * @param int $gorselSayisi
     * @param array $gorselListesi
     * @return void
     */
    public function gorselleriGuncelle(int $gorselSayisi, array $gorselListesi): void
    {
        $this->recordEvent('IlanGorselleriGuncellendi', [
            'eski_gorsel_sayisi' => $this->state['gorsel_sayisi'],
            'yeni_gorsel_sayisi' => $gorselSayisi,
            'gorsel_listesi' => $gorselListesi,
            'guncellenme_zamani' => now()->toIso8601String(),
        ]);

        $this->state['gorsel_sayisi'] = $gorselSayisi;
        $this->state['son_guncelleme'] = now()->toIso8601String();
    }

    /**
     * Unpublish property
     *
     * @param string $neden
     * @return void
     */
    public function yayindanKaldir(string $neden): void
    {
        $this->recordEvent('IlanYayindanKaldirildi', [
            'neden' => $neden,
            'kaldirilma_zamani' => now()->toIso8601String(),
        ]);

        $this->state['yayin_durumu'] = 0;
        $this->state['ilan_durumu'] = 'pasif';
        $this->state['son_guncelleme'] = now()->toIso8601String();
    }

    /**
     * Apply event to reconstruct state
     *
     * @param string $eventType
     * @param array $payload
     * @return void
     */
    protected function applyEvent(string $eventType, array $payload): void
    {
        match ($eventType) {
            'IlanOlusturuldu' => $this->applyIlanOlusturuldu($payload),
            'IlanFiyatiDegistirildi' => $this->applyIlanFiyatiDegistirildi($payload),
            'IlanDurumuDegistirildi' => $this->applyIlanDurumuDegistirildi($payload),
            'IlanGorselleriGuncellendi' => $this->applyIlanGorselleriGuncellendi($payload),
            'IlanYayindanKaldirildi' => $this->applyIlanYayindanKaldirildi($payload),
            default => null,
        };
    }

    /**
     * Apply IlanOlusturuldu event
     *
     * @param array $payload
     * @return void
     */
    protected function applyIlanOlusturuldu(array $payload): void
    {
        $this->state['baslik'] = $payload['baslik'];
        $this->state['fiyat'] = $payload['fiyat'];
        $this->state['ilan_durumu'] = $payload['ilan_durumu'];
        $this->state['yayin_durumu'] = $payload['yayin_durumu'];
    }

    /**
     * Apply IlanFiyatiDegistirildi event
     *
     * @param array $payload
     * @return void
     */
    protected function applyIlanFiyatiDegistirildi(array $payload): void
    {
        $this->state['fiyat'] = $payload['yeni_fiyat'];
        $this->state['son_guncelleme'] = $payload['degistirilme_zamani'];
    }

    /**
     * Apply IlanDurumuDegistirildi event
     *
     * @param array $payload
     * @return void
     */
    protected function applyIlanDurumuDegistirildi(array $payload): void
    {
        $this->state['ilan_durumu'] = $payload['yeni_durum'];
        $this->state['son_guncelleme'] = $payload['degistirilme_zamani'];
    }

    /**
     * Apply IlanGorselleriGuncellendi event
     *
     * @param array $payload
     * @return void
     */
    protected function applyIlanGorselleriGuncellendi(array $payload): void
    {
        $this->state['gorsel_sayisi'] = $payload['yeni_gorsel_sayisi'];
        $this->state['son_guncelleme'] = $payload['guncellenme_zamani'];
    }

    /**
     * Apply IlanYayindanKaldirildi event
     *
     * @param array $payload
     * @return void
     */
    protected function applyIlanYayindanKaldirildi(array $payload): void
    {
        $this->state['yayin_durumu'] = 0;
        $this->state['ilan_durumu'] = 'pasif';
        $this->state['son_guncelleme'] = $payload['kaldirilma_zamani'];
    }

    /**
     * Get current aggregate state
     *
     * @return array
     */
    public function getState(): array
    {
        return $this->state;
    }
}
