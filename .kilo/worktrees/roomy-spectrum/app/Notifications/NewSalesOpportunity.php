<?php

namespace App\Notifications;

use App\Models\Talep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * NewSalesOpportunity Notification
 *
 * Context7: Acil Satış Fırsatı Bildirimi
 * Action Score > 110 olan yüksek öncelikli fırsatlar için danışmana bildirim gönderir
 */
class NewSalesOpportunity extends Notification
{
    use Queueable;

    /**
     * Talep nesnesi
     */
    public Talep $talep;

    /**
     * En iyi eşleşme (match)
     */
    public array $topMatch;

    /**
     * Action Score
     */
    public float $actionScore;

    /**
     * Create a new notification instance.
     */
    public function __construct(Talep $talep, array $topMatch, float $actionScore)
    {
        $this->talep = $talep;
        $this->topMatch = $topMatch;
        $this->actionScore = $actionScore;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $ilanBaslik = $this->topMatch['baslik'] ?? 'İlan';
        $ilanFiyat = number_format($this->topMatch['fiyat'] ?? 0, 0, ',', '.');
        $paraBirimi = $this->topMatch['para_birimi'] ?? '₺';
        $matchScore = round($this->topMatch['match_score'] ?? 0, 2);
        $churnScore = round($this->topMatch['churn_score'] ?? 0, 2);

        return (new MailMessage)
            ->subject('🚀 Yüksek Öncelikli Satış Fırsatı!')
            ->greeting('Merhaba ' . $notifiable->name . '!')
            ->line('Yeni bir talep için **yüksek öncelikli** bir satış fırsatı tespit edildi.')
            ->line('**Talep:** ' . $this->talep->baslik)
            ->line('**Eşleşen İlan:** ' . $ilanBaslik)
            ->line('**Fiyat:** ' . $ilanFiyat . ' ' . $paraBirimi)
            ->line('**Action Score:** ' . round($this->actionScore, 2) . ' (Çok Yüksek!)')
            ->line('**Match Score:** ' . $matchScore)
            ->line('**Churn Score:** ' . $churnScore)
            ->action('Talep Detayını Görüntüle', route('admin.talepler.show', $this->talep->id))
            ->line('Bu fırsatı kaçırmayın! Hemen müşteri ile iletişime geçin.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tip' => 'new_sales_opportunity',
            'title' => '🚀 Yüksek Öncelikli Satış Fırsatı',
            'message' => sprintf(
                'Talep "%s" için Action Score %.2f olan yüksek öncelikli bir fırsat tespit edildi.',
                $this->talep->baslik,
                $this->actionScore
            ),
            'talep_id' => $this->talep->id,
            'ilan_id' => $this->topMatch['ilan_id'] ?? null,
            'action_score' => $this->actionScore,
            'match_score' => $this->topMatch['match_score'] ?? 0,
            'churn_score' => $this->topMatch['churn_score'] ?? 0,
            'ilan_baslik' => $this->topMatch['baslik'] ?? null,
            'ilan_fiyat' => $this->topMatch['fiyat'] ?? null,
            'para_birimi' => $this->topMatch['para_birimi'] ?? null,
            'data' => [
                'talep' => [
                    'id' => $this->talep->id,
                    'baslik' => $this->talep->baslik,
                    'kisi_id' => $this->talep->kisi_id,
                ],
                'match' => $this->topMatch,
            ],
        ];
    }
}
