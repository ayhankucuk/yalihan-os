<?php

namespace App\Http\Controllers\Admin;

use App\Models\Notification\OutboundNotification;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Outbound Notification Controller
 * 
 * Part of N2: Notification Operations & Visibility
 * Responsible for monitoring and retrying system notifications.
 * @sab-ignore-thin
 */
class OutboundNotificationController extends AdminController
{
    public function __construct(
        protected \App\Services\Notification\NotificationRetryService $retryService
    ) {
        parent::__construct();
        $this->middleware('can:manage-notifications');
    }

    /**
     * Display a listing of outbound notifications.
     */
    public function index(Request $request)
    {
        $query = OutboundNotification::query();

        $stats = $this->calculateDailyMetrics();

        // P0: Filters (Context7: 'durum' kullanıldı)
        $this->applyFilters($query, $request);

        $logs = $query->latest()->paginate(25);

        return view('admin.notifications.outbound.index', [
            'logs' => $logs,
            'stats' => $stats,
            'filters' => $request->only(['channel', 'durum', 'recipient', 'template_key'])
        ]);
    }

    /**
     * Calculate bugünkü istatistikler (P2)
     */
    protected function calculateDailyMetrics(): array
    {
        $today = now()->startOfDay();
        $total = OutboundNotification::where('created_at', '>=', $today)->count();
        $failed = OutboundNotification::where('created_at', '>=', $today)->where('gonderim_durumu', OutboundNotification::STATE_FAILED)->count();
        
        return [
            'total_today' => $total,
            'failed_today' => $failed,
            'success_rate' => $total > 0 ? round((($total - $failed) / $total) * 100, 1) : 0
        ];
    }

    /**
     * Apply request filters to the query.
     */
    protected function applyFilters($query, Request $request): void
    {
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('durum')) {
            $query->where('gonderim_durumu', $request->durum);
        }

        if ($request->filled('recipient')) {
            $query->where('recipient', 'like', '%' . $request->recipient . '%');
        }

        if ($request->filled('template_key')) {
            $query->where('template_key', 'like', '%' . $request->template_key . '%');
        }
    }

    /**
     * Display the specified notification details.
     */
    public function show(int $id)
    {
        $log = OutboundNotification::findOrFail($id);

        // N2: Sensitive Data Masking
        $maskedPayload = $this->maskSensitiveData($log->payload_data ?? []);
        $maskedResponse = $this->maskSensitiveData($log->provider_response ?? []);

        return view('admin.notifications.outbound.show', [
            'log' => $log,
            'maskedPayload' => $maskedPayload,
            'maskedResponse' => $maskedResponse
        ]);
    }

    /**
     * Retry a failed notification.
     */
    public function retry(int $id)
    {
        try {
            $log = OutboundNotification::findOrFail($id);

            if (!$this->retryService->canRetry($log)) {
                return ResponseService::error('Bu bildirim mevcut durumda tekrar denenemez.');
            }

            // N2: Reset state via service and dispatch again
            $this->retryService->resetForManualRetry($log);
            $success = $log->resend();

            if ($success) {
                return ResponseService::redirectSuccess(
                    route('admin.outbound-notifications.show', $id),
                    'Bildirim yeniden gönderim sırasına alındı.'
                );
            }

            return ResponseService::error('Bildirim yeniden gönderilemedi. Dispatcher hatası.');

        } catch (\Exception $e) {
            Log::error("[OutboundNotificationController] Retry failed: " . $e->getMessage());
            return ResponseService::serverError('Yeniden deneme sırasında sistem hatası oluştu.');
        }
    }

    /**
     * Manual Test Trigger (P1)
     */
    public function testSend(Request $request)
    {
        $validated = $request->validate([
            'channel' => 'required|in:email,whatsapp,telegram,instagram,webhook',
            'recipient' => 'required|string',
            'template_key' => 'required|string',
            'message' => 'required|string'
        ]);

        try {
            $authority = app(\App\Contracts\Notification\NotificationAuthorityInterface::class);
            
            // Note: Direct dispatch for test purposes
            $notification = \App\DTOs\Notification\GenericNotification::make(
                $validated['channel'],
                $validated['recipient'],
                $validated['template_key'],
                ['body' => $validated['message'], 'is_test' => true]
            );

            $success = app(\App\Services\Notification\NotificationDispatcher::class)->dispatch($notification);

            if ($success) {
                return ResponseService::redirectSuccess(
                    route('admin.outbound-notifications.index'),
                    'Test bildirimi başarıyla gönderildi.'
                );
            }

            return ResponseService::error('Test bildirimi gönderilemedi.');

        } catch (\Exception $e) {
            Log::error("[OutboundNotificationController] Test send failed: " . $e->getMessage());
            return ResponseService::serverError('Test gönderimi sırasında hata oluştu.');
        }
    }

    /**
     * Mask sensitive keys in data arrays.
     */
    protected function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = ['token', 'password', 'key', 'secret', 'access_token', 'auth_token'];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            } elseif (in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '******** (Maskelenmiş)';
            }
        }

        return $data;
    }
}
