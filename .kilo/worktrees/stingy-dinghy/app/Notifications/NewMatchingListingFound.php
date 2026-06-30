<?php

namespace App\Notifications;

use App\Models\Ilan;
use App\Models\Talep;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * New Matching Listing Found Notification
 *
 * Context7: Tersine Eşleştirme Bildirimi
 *
 * Yeni ilan eklendiğinde, bu ilana uygun talep bulunduğunda
 * danışmana gönderilen bildirim.
 */
class NewMatchingListingFound extends Notification
{
    use Queueable;

    /**
     * Eşleşen ilan
     */
    protected Ilan $ilan;

    /**
     * Eşleşen talep
     */
    protected Talep $talep;

    /**
     * Eşleşme skoru
     */
    protected float $score;

    /**
     * Create a new notification instance.
     */
    public function __construct(Ilan $ilan, Talep $talep, float $score)
    {
        $this->ilan = $ilan;
        $this->talep = $talep;
        $this->score = $score;
    }

    /**
     * Get the notification's delivery channels.
     *
     * Context7: Database notification kullanılıyor
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * Database notification için kullanılır
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        $kisiAdi = $this->talep->kisi
            ? ($this->talep->kisi->ad.' '.$this->talep->kisi->soyad)
            : 'Müşteri';

        return [
            'tip' => 'matching_listing_found',
            'title' => 'Yeni Eşleşme Bulundu',
            'message' => "Müşteriniz {$kisiAdi} için yeni bir eşleşme bulundu: {$this->ilan->baslik} (Uyum: %".round($this->score).')',
            'ilan_id' => $this->ilan->id,
            'talep_id' => $this->talep->id,
            'score' => $this->score,
            'ilan_baslik' => $this->ilan->baslik,
            'talep_baslik' => $this->talep->baslik ?? 'Talep #'.$this->talep->id,
            'kisi_adi' => $kisiAdi,
            'action_url' => route('admin.ilanlar.show', $this->ilan->id),
        ];
    }
}
