<?php

namespace App\Services\Reservation;

use App\Modules\TakimYonetimi\Models\Gorev;
use App\Models\PropertyReservation;
use App\Models\Ilan;
use App\Events\GorevCreated;
use Illuminate\Support\Facades\Log;

/**
 * OperationalGorevService — Automated operational task creation.
 *
 * Does NOT use GuardsAgentWrites — this service is specifically for
 * AI-triggered automation. It is separate from GorevService which
 * handles manual CRM task management.
 *
 * CHECKOUT-D2: Extend Gorev + OperationalGorevService
 * Baseline: 88ccfc8
 *
 * Idempotency: Each task type per reservation is created at most once.
 * The caller (job) is responsible for idempotency enforcement.
 *
 * Tenant isolation: Enforced via PropertyReservation->ilan->tenant_id chain.
 * All Gorev queries and creations are tenant-scoped.
 */
class OperationalGorevService
{
    /**
     * Task type constants — matches Gorev::getTipler() operational entries.
     */
    public const TASK_HAZIRLIK = 'hazirlik';
    public const TASK_TEMIZLIK = 'temizlik';
    public const TASK_KONTROL = 'kontrol';
    public const TASK_HAVUZ = 'havuz';
    public const TASK_BAHCE = 'bahce';

    /**
     * Create a pre-arrival readiness task for a reservation.
     *
     * Deadline: check-in time on start_date - 2 hours.
     * Priority: yuksek (must be done before guest arrives).
     *
     * @param PropertyReservation $reservation
     * @param Ilan $ilan
     * @param int $creatorUserId  User who initiates (system = 0)
     * @return Gorev|null  null if idempotency check prevents duplicate
     */
    public function createPreArrivalTask(
        PropertyReservation $reservation,
        Ilan $ilan,
        int $creatorUserId = 0,
    ): ?Gorev {
        return $this->createOperationalTask(
            reservation: $reservation,
            ilan: $ilan,
            taskType: self::TASK_HAZIRLIK,
            title: $this->buildTitle(self::TASK_HAZIRLIK, $ilan, $reservation),
            description: $this->buildHazirlikDescription($reservation, $ilan),
            priority: 'yuksek',
            deadlineOffset: 0,  // deadline = start_date 00:00, done 2h before check-in
            creatorUserId: $creatorUserId,
        );
    }

    /**
     * Create a post-checkout turnover cleaning task.
     *
     * Deadline: same day as end_date (guest must depart by 11:00, cleaner by end of day).
     *
     * @param PropertyReservation $reservation
     * @param Ilan $ilan
     * @param int $creatorUserId
     * @return Gorev|null
     */
    public function createTurnoverTask(
        PropertyReservation $reservation,
        Ilan $ilan,
        int $creatorUserId = 0,
    ): ?Gorev {
        return $this->createOperationalTask(
            reservation: $reservation,
            ilan: $ilan,
            taskType: self::TASK_TEMIZLIK,
            title: $this->buildTitle(self::TASK_TEMIZLIK, $ilan, $reservation),
            description: $this->buildTemizlikDescription($reservation, $ilan),
            priority: 'yuksek',
            deadlineOffset: 0,  // deadline = end_date 23:59
            creatorUserId: $creatorUserId,
        );
    }

    /**
     * Check if an operational task already exists for this reservation and type.
     * Used for idempotency enforcement before task creation.
     *
     * @param int $reservationId
     * @param string $taskType
     * @return bool
     */
    public function taskExists(int $reservationId, string $taskType): bool
    {
        return Gorev::query()
            ->where('reservation_id', $reservationId)
            ->where('gorev_tipi', $taskType)
            ->exists();
    }

    /**
     * Find an existing operational task by reservation and type.
     *
     * @param int $reservationId
     * @param string $taskType
     * @return Gorev|null
     */
    public function findTask(int $reservationId, string $taskType): ?Gorev
    {
        return Gorev::query()
            ->where('reservation_id', $reservationId)
            ->where('gorev_tipi', $taskType)
            ->orderBy('id')
            ->first();
    }

    /**
     * Core task creation logic with idempotency check.
     *
     * @throws \RuntimeException if tenant mismatch detected
     */
    protected function createOperationalTask(
        PropertyReservation $reservation,
        Ilan $ilan,
        string $taskType,
        string $title,
        string $description,
        string $priority,
        int $deadlineOffset,
        int $creatorUserId,
    ): ?Gorev {
        // Idempotency: return existing task if already created
        if ($this->taskExists($reservation->id, $taskType)) {
            Log::info('OperationalGorevService: task already exists, skipping', [
                'reservation_id' => $reservation->id,
                'task_type' => $taskType,
            ]);
            return null;
        }

        // Tenant isolation: verify reservation and ilan belong to the same tenant
        $reservationTenant = $reservation->tenant_id;
        $ilanTenant = $ilan->tenant_id;
        if ($reservationTenant !== $ilanTenant) {
            Log::error('OperationalGorevService: tenant mismatch — cross-tenant task creation blocked', [
                'reservation_id' => $reservation->id,
                'reservation_tenant_id' => $reservationTenant,
                'ilan_id' => $ilan->id,
                'ilan_tenant_id' => $ilanTenant,
            ]);
            throw new \RuntimeException(
                "Tenant isolation violation: reservation tenant {$reservationTenant} != ilan tenant {$ilanTenant}"
            );
        }

        // Build deadlines
        $startDate = $reservation->start_date instanceof \Carbon\Carbon
            ? $reservation->start_date
            : \Carbon\Carbon::parse($reservation->start_date);
        $endDate = $reservation->end_date instanceof \Carbon\Carbon
            ? $reservation->end_date
            : \Carbon\Carbon::parse($reservation->end_date);

        // baslangic_tarihi: the day before for hazirlik, same day for temizlik
        $baslangic = $taskType === self::TASK_HAZIRLIK
            ? $startDate->copy()->subDay()->startOfDay()
            : $endDate->copy()->startOfDay();

        // bitis_tarihi (deadline): 2 hours before check-in time on start_date for hazirlik,
        // end of end_date for temizlik
        $checkInTime = $ilan->check_in_time ?? '14:00';
        $checkOutTime = $ilan->check_out_time ?? '11:00';

        $bitis = $taskType === self::TASK_HAZIRLIK
            ? $startDate->copy()->setTimeFromTimeString($checkInTime)->subHours(2)
            : $endDate->copy()->setTimeFromTimeString($checkOutTime)->addHours(12);  // end of day

        $gorev = Gorev::create([
            'baslik' => $title,
            'aciklama' => $description,
            'oncelik' => $priority,
            'atanan_user_id' => null,  // Assigned by Ayhan manually, or AI in future
            'olusturan_user_id' => $creatorUserId ?: null,
            'kisi_id' => null,
            'lead_id' => null,
            'proje_id' => null,
            'ilan_id' => $ilan->id,
            'reservation_id' => $reservation->id,
            'baslangic_tarihi' => $baslangic,
            'bitis_tarihi' => $bitis,
            'tamamlanma_yuzdesi' => 0,
            'notlar' => null,
            'gorev_durumu' => 'bekliyor',
            'gorev_tipi' => $taskType,
        ]);

        Log::info('OperationalGorevService: task created', [
            'gorev_id' => $gorev->id,
            'reservation_id' => $reservation->id,
            'ilan_id' => $ilan->id,
            'task_type' => $taskType,
            'tenant_id' => $reservationTenant,
            'baslangic_tarihi' => $baslangic->toDateString(),
            'bitis_tarihi' => $bitis->toDateString(),
        ]);

        // Dispatch GorevCreated event → NotifyN8nOnGorevCreated listener → n8n → Telegram/WhatsApp
        GorevCreated::dispatch($gorev);

        return $gorev;
    }

    protected function buildTitle(string $taskType, Ilan $ilan, PropertyReservation $reservation): string
    {
        $ilanAd = $ilan->baslik ?? $ilan->title ?? "Ilan #{$ilan->id}";
        $guestName = $reservation->guest_name ?? 'Guest';

        return match ($taskType) {
            self::TASK_HAZIRLIK => "Hazırlık: {$ilanAd} — {$guestName} girişi",
            self::TASK_TEMIZLIK => "Temizlik/Turnover: {$ilanAd} — {$guestName} çıkışı",
            self::TASK_KONTROL => "Kontrol: {$ilanAd} — {$guestName} çıkışı",
            self::TASK_HAVUZ => "Havuz Bakım: {$ilanAd}",
            self::TASK_BAHCE => "Bahçe Bakım: {$ilanAd}",
            default => "Görev: {$ilanAd}",
        };
    }

    protected function buildHazirlikDescription(PropertyReservation $reservation, Ilan $ilan): string
    {
        $startDate = $reservation->start_date instanceof \Carbon\Carbon
            ? $reservation->start_date->format('d.m.Y')
            : $reservation->start_date;
        $checkInTime = $ilan->check_in_time ?? '14:00';

        $description = "## Hazırlık Görevi\n\n";
        $description .= "**Mülk:** {$ilan->baslik}\n";
        $description .= "**Misafir:** {$reservation->guest_name}\n";
        $description .= "**Giriş Tarihi:** {$startDate} / {$checkInTime}\n";
        $description .= "**Misafir Sayısı:** {$reservation->guest_count}\n";

        if ($reservation->notes) {
            $description .= "**Özel İstekler:** {$reservation->notes}\n";
        }

        $description .= "\n### Checklist\n";
        $description .= "- [ ] Temizlik kontrolü\n";
        $description .= "- [ ] Havuz/tesisat kontrolü\n";
        $description .= "- [ ] Anahtar/kod hazırlığı\n";
        $description .= "- [ ] WiFi bilgileri hazır\n";
        $description .= "- [ ] Aydınlatma/iklim kontrolü\n";

        return $description;
    }

    protected function buildTemizlikDescription(PropertyReservation $reservation, Ilan $ilan): string
    {
        $endDate = $reservation->end_date instanceof \Carbon\Carbon
            ? $reservation->end_date->format('d.m.Y')
            : $reservation->end_date;
        $checkOutTime = $ilan->check_out_time ?? '11:00';

        $description = "## Temizlik / Turnover Görevi\n\n";
        $description .= "**Mülk:** {$ilan->baslik}\n";
        $description .= "**Çıkış Tarihi:** {$endDate} / {$checkOutTime}\n";
        $description .= "**Misafir:** {$reservation->guest_name}\n";
        $description .= "**Misafir Sayısı:** {$reservation->guest_count}\n";

        $description .= "\n### Checklist\n";
        $description .= "- [ ] Tüm odalar temizlendi\n";
        $description .= "- [ ] Zeminler süpürüldü/silindi\n";
        $description .= "- [ ] Banyo dezenfekte edildi\n";
        $description .= "- [ ] Mutfak temizlendi\n";
        $description .= "- [ ] Havlu/çarşaf değişimi\n";
        $description .= "- [ ] Çöp boşaltıldı\n";
        $description .= "- [ ] Havuz bakımı\n";
        $description .= "- [ ] Bahçe kontrolü\n";
        $description .= "- [ ] Anahtar/ Access code iade kontrolü\n";

        return $description;
    }
}
