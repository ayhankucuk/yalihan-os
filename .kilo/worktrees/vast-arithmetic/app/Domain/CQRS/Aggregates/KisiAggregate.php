<?php

namespace App\Domain\CQRS\Aggregates;

use App\Domain\CQRS\AggregateRoot;

/**
 * Class KisiAggregate
 *
 * SAB Phase 15 Sprint 1: Kisi (Person/Contact) Domain Event Sourcing
 * Manages person/contact lifecycle through immutable event stream.
 *
 * Domain Events:
 * - KisiOlusturuldu: Initial person registration
 * - KisiIletisimBilgisiGuncellendi: Contact info updated
 * - KisiAdresEklendi: Address added
 * - KisiNotEklendi: Note/comment added
 * - KisiEtiketlendi: Tag/label added
 *
 * @package App\Domain\CQRS\Aggregates
 */
class KisiAggregate extends AggregateRoot
{
    /**
     * Current person state (reconstructed from events)
     *
     * @var array
     */
    protected array $state = [
        'ad_soyad' => null,
        'telefon' => null,
        'eposta' => null,
        'kisi_tipi' => null, // musteri, potansiyel_musteri, tedarikci
        'adres_sayisi' => 0,
        'not_sayisi' => 0,
        'etiketler' => [],
    ];

    /**
     * Create a new person
     *
     * @param array $kisiData
     * @return void
     */
    public function kisiOlustur(array $kisiData): void
    {
        $this->recordEvent('KisiOlusturuldu', [
            'ad_soyad' => $kisiData['ad_soyad'],
            'telefon' => $kisiData['telefon'],
            'eposta' => $kisiData['eposta'] ?? null,
            'kisi_tipi' => $kisiData['kisi_tipi'] ?? 'potansiyel_musteri',
            'kaynak' => $kisiData['kaynak'] ?? 'manuel',
            'olusturulma_zamani' => now()->toIso8601String(),
        ]);

        $this->state['ad_soyad'] = $kisiData['ad_soyad'];
        $this->state['telefon'] = $kisiData['telefon'];
        $this->state['eposta'] = $kisiData['eposta'] ?? null;
        $this->state['kisi_tipi'] = $kisiData['kisi_tipi'] ?? 'potansiyel_musteri';
    }

    /**
     * Update contact information
     *
     * @param array $iletisimBilgisi
     * @return void
     */
    public function iletisimBilgisiGuncelle(array $iletisimBilgisi): void
    {
        $this->recordEvent('KisiIletisimBilgisiGuncellendi', [
            'eski_telefon' => $this->state['telefon'],
            'yeni_telefon' => $iletisimBilgisi['telefon'] ?? $this->state['telefon'],
            'eski_eposta' => $this->state['eposta'],
            'yeni_eposta' => $iletisimBilgisi['eposta'] ?? $this->state['eposta'],
            'guncellenme_zamani' => now()->toIso8601String(),
        ]);

        if (isset($iletisimBilgisi['telefon'])) {
            $this->state['telefon'] = $iletisimBilgisi['telefon'];
        }
        if (isset($iletisimBilgisi['eposta'])) {
            $this->state['eposta'] = $iletisimBilgisi['eposta'];
        }
    }

    /**
     * Add address
     *
     * @param array $adresData
     * @return void
     */
    public function adresEkle(array $adresData): void
    {
        $this->recordEvent('KisiAdresEklendi', [
            'il' => $adresData['il'],
            'ilce' => $adresData['ilce'],
            'mahalle' => $adresData['mahalle'] ?? null,
            'adres_detay' => $adresData['adres_detay'] ?? null,
            'adres_tipi' => $adresData['adres_tipi'] ?? 'ev', // ev, is, diger
            'eklenme_zamani' => now()->toIso8601String(),
        ]);

        $this->state['adres_sayisi']++;
    }

    /**
     * Add note/comment
     *
     * @param string $notIcerik
     * @param string|null $notTipi
     * @return void
     */
    public function notEkle(string $notIcerik, ?string $notTipi = null): void
    {
        $this->recordEvent('KisiNotEklendi', [
            'not_icerik' => $notIcerik,
            'not_tipi' => $notTipi ?? 'genel',
            'eklenme_zamani' => now()->toIso8601String(),
        ]);

        $this->state['not_sayisi']++;
    }

    /**
     * Add tag/label
     *
     * @param string $etiket
     * @return void
     */
    public function etiketEkle(string $etiket): void
    {
        if (!in_array($etiket, $this->state['etiketler'])) {
            $this->recordEvent('KisiEtiketlendi', [
                'etiket' => $etiket,
                'etiketlenme_zamani' => now()->toIso8601String(),
            ]);

            $this->state['etiketler'][] = $etiket;
        }
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
            'KisiOlusturuldu' => $this->applyKisiOlusturuldu($payload),
            'KisiIletisimBilgisiGuncellendi' => $this->applyKisiIletisimBilgisiGuncellendi($payload),
            'KisiAdresEklendi' => $this->applyKisiAdresEklendi($payload),
            'KisiNotEklendi' => $this->applyKisiNotEklendi($payload),
            'KisiEtiketlendi' => $this->applyKisiEtiketlendi($payload),
            default => null,
        };
    }

    /**
     * Apply KisiOlusturuldu event
     *
     * @param array $payload
     * @return void
     */
    protected function applyKisiOlusturuldu(array $payload): void
    {
        $this->state['ad_soyad'] = $payload['ad_soyad'];
        $this->state['telefon'] = $payload['telefon'];
        $this->state['eposta'] = $payload['eposta'] ?? null;
        $this->state['kisi_tipi'] = $payload['kisi_tipi'];
    }

    /**
     * Apply KisiIletisimBilgisiGuncellendi event
     *
     * @param array $payload
     * @return void
     */
    protected function applyKisiIletisimBilgisiGuncellendi(array $payload): void
    {
        $this->state['telefon'] = $payload['yeni_telefon'];
        $this->state['eposta'] = $payload['yeni_eposta'];
    }

    /**
     * Apply KisiAdresEklendi event
     *
     * @param array $payload
     * @return void
     */
    protected function applyKisiAdresEklendi(array $payload): void
    {
        $this->state['adres_sayisi']++;
    }

    /**
     * Apply KisiNotEklendi event
     *
     * @param array $payload
     * @return void
     */
    protected function applyKisiNotEklendi(array $payload): void
    {
        $this->state['not_sayisi']++;
    }

    /**
     * Apply KisiEtiketlendi event
     *
     * @param array $payload
     * @return void
     */
    protected function applyKisiEtiketlendi(array $payload): void
    {
        if (!in_array($payload['etiket'], $this->state['etiketler'])) {
            $this->state['etiketler'][] = $payload['etiket'];
        }
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
