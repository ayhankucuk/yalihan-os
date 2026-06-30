<?php

namespace App\Services;

use App\Models\Deprecated\AdminActivityEvent;
use App\Models\IlanReservation;
use App\Models\Ilan;
use App\Services\Logging\LogService;
use Carbon\Carbon;

/**
 * Admin Activity Event Service
 *
 * Phase U: Telegram ↔ Admin UI Activity Feed (READ-ONLY)
 * Context7 Compliance: Read-only activity logging
 *
 * - UPS SSOT korunur
 * - Cortex observer mode korunur
 * - content_type YOK
 * - Read-only (update/delete yok)
 */
class AdminActivityEventService
{
    protected LogService $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Rezervasyon aktivitesi kaydet
     */
    public function logReservationActivity(
        IlanReservation $reservation,
        string $action,
        string $source,
        ?int $userId = null,
        ?int $telegramUserId = null,
        array $context = []
    ): AdminActivityEvent {
        $summary = $this->buildReservationSummary($reservation, $action);

        return AdminActivityEvent::create([
            'entity_type' => 'reservation',
            'entity_id' => $reservation->id,
            'action' => $action,
            'source' => $source,
            'summary' => $summary,
            'context' => array_merge([
                'reservation_id' => $reservation->id,
                'ilan_id' => $reservation->ilan_id,
                'starts_at' => $reservation->starts_at->toIso8601String(),
                'ends_at' => $reservation->ends_at->toIso8601String(),
                'islem_statusu' => $reservation->islem_statusu,
                'customer_name' => $reservation->customer_name,
            ], $context),
            'user_id' => $userId,
            'telegram_user_id' => $telegramUserId,
        ]);
    }

    /**
     * Takvim aktivitesi kaydet
     */
    public function logCalendarActivity(
        Ilan $ilan,
        string $action,
        Carbon $from,
        Carbon $to,
        string $source,
        ?int $userId = null,
        ?int $telegramUserId = null,
        array $context = []
    ): AdminActivityEvent {
        $summary = $this->buildCalendarSummary($ilan, $action, $from, $to);

        return AdminActivityEvent::create([
            'entity_type' => 'calendar',
            'entity_id' => $ilan->id,
            'action' => $action,
            'source' => $source,
            'summary' => $summary,
            'context' => array_merge([
                'ilan_id' => $ilan->id,
                'starts_at' => $from->toIso8601String(),
                'ends_at' => $to->toIso8601String(),
            ], $context),
            'user_id' => $userId,
            'telegram_user_id' => $telegramUserId,
        ]);
    }

    /**
     * Rezervasyon özeti oluştur
     */
    protected function buildReservationSummary(IlanReservation $reservation, string $action): string
    {
        $actionText = match ($action) {
            'create' => 'oluşturuldu',
            'confirm' => 'onaylandı',
            'cancel' => 'iptal edildi',
            default => $action,
        };

        return sprintf(
            'Rezervasyon #%d %s - İlan #%d (%s - %s)',
            $reservation->id,
            $actionText,
            $reservation->ilan_id,
            $reservation->starts_at->format('d.m.Y H:i'),
            $reservation->ends_at->format('d.m.Y H:i')
        );
    }

    /**
     * Takvim özeti oluştur
     */
    protected function buildCalendarSummary(Ilan $ilan, string $action, Carbon $from, Carbon $to): string
    {
        $actionText = match ($action) {
            'close_calendar' => 'kapatıldı',
            default => $action,
        };

        return sprintf(
            'Takvim %s - İlan #%d (%s - %s)',
            $actionText,
            $ilan->id,
            $from->format('d.m.Y H:i'),
            $to->format('d.m.Y H:i')
        );
    }

    /**
     * Aktivite listesi (filtrelenebilir)
     */
    public function listActivities(array $filters = [], int $limit = 50): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = AdminActivityEvent::query();

        // Entity type filter
        if (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        // Entity ID filter
        if (isset($filters['entity_id'])) {
            $query->where('entity_id', $filters['entity_id']);
        }

        // Action filter
        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        // Source filter
        if (isset($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        // User filter
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Date range filter
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc') // context7-ignore
            ->with('user:id,name')
            ->paginate($limit);
    }

    /**
     * İstatistikler
     */
    public function getStatistics(array $filters = []): array
    {
        $query = AdminActivityEvent::query();

        // Apply same filters as listActivities
        if (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return [
            'total' => (clone $query)->count(),
            'by_action' => (clone $query)->selectRaw('action, count(*) as count')
                ->groupBy('action')
                ->pluck('count', 'action')
                ->toArray(),
            'by_source' => (clone $query)->selectRaw('source, count(*) as count')
                ->groupBy('source')
                ->pluck('count', 'source')
                ->toArray(),
            'by_entity_type' => (clone $query)->selectRaw('entity_type, count(*) as count')
                ->groupBy('entity_type')
                ->pluck('count', 'entity_type')
                ->toArray(),
        ];
    }
}
