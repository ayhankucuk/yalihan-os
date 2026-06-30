<?php

namespace App\Events;

use App\Models\Ilan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * İlan Fiyat Değişikliği Event
 *
 * Context7: Otonom Fiyat Değişim Takibi ve n8n Entegrasyonu
 * İlan fiyatı değiştiğinde bu event fırlatılır ve n8n'e bildirim gönderilir.
 */
class IlanPriceChanged
{
    use Dispatchable, SerializesModels;

    /**
     * Fiyatı değişen ilan
     */
    public Ilan $ilan;

    /**
     * Eski fiyat
     */
    public ?float $oldPrice;

    /**
     * Yeni fiyat
     */
    public ?float $newPrice;

    /**
     * Para birimi
     */
    public string $currency;

    /**
     * Create a new event instance.
     *
     * @param Ilan $ilan
     * @param float|null $oldPrice
     * @param float|null $newPrice
     * @param string $currency
     */
    public function __construct(Ilan $ilan, ?float $oldPrice, ?float $newPrice, string $currency = 'TRY')
    {
        $this->ilan = $ilan;
        $this->oldPrice = $oldPrice;
        $this->newPrice = $newPrice;
        $this->currency = $currency;
    }
}
