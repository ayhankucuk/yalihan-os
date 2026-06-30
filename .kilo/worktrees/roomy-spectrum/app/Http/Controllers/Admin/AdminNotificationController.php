<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Models\AdminNotification;
use App\Actions\Admin\Notification\MarkAllAdminNotificationsReadAction;
use App\Services\AdminNotificationService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;

/**
 * Admin Notification Controller
 *
 * Phase S: Rezervasyon Bildirimleri + Otomasyon
 * Context7 Compliance: Admin bildirimleri için controller
 */
class AdminNotificationController extends AdminController
{
    protected AdminNotificationService $notificationService;

    public function __construct(AdminNotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
        $this->middleware('can:manage-notifications');
    }

    /**
     * Bildirim listesi sayfası
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        $filter = $request->get('filter', 'all'); // all, unread, read
        $channel = $request->get('channel'); // calendar, reservation, system

        $query = AdminNotification::forUser($userId);

        if ($filter === 'unread') {
            $query->unread();
        } elseif ($filter === 'read') {
            $query->read();
        }

        if ($channel) {
            $query->forChannel($channel);
        }

        $notifications = $query->orderBy('created_at', 'desc') // context7-ignore
            ->paginate(20);

        $unreadCount = $this->notificationService->getUnreadCount($userId);

        return view('admin.admin-notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'filter' => $filter,
            'channel' => $channel,
        ]);
    }

    /**
     * Bildirimi okundu olarak işaretle
     */
    public function markAsRead(AdminNotification $adminNotification)
    {
        if ($adminNotification->user_id !== auth()->id()) {
            return ResponseService::forbidden('Bu bildirim size ait değil');
        }

        $adminNotification->markAsRead();

        return ResponseService::redirectSuccess(
            route('admin.admin-notifications.index'),
            'Bildirim okundu olarak işaretlendi'
        );
    }

    /**
     * Tüm bildirimleri okundu olarak işaretle
     */
    public function markAllAsRead(MarkAllAdminNotificationsReadAction $action)
    {
        $action->handle((int) auth()->id());

        return ResponseService::redirectSuccess(
            route('admin.admin-notifications.index'),
            'Tüm bildirimler okundu olarak işaretlendi'
        );
    }

    /**
     * API: Bildirim listesi
     */
    public function apiIndex(Request $request)
    {
        $userId = auth()->id();
        $filter = $request->get('filter', 'all');
        $channel = $request->get('channel');

        $query = AdminNotification::forUser($userId);

        if ($filter === 'unread') {
            $query->unread();
        } elseif ($filter === 'read') {
            $query->read();
        }

        if ($channel) {
            $query->forChannel($channel);
        }

        $notifications = $query->orderBy('created_at', 'desc') // context7-ignore
            ->paginate(20);

        return ResponseService::success([
            'notifications' => $notifications,
            'unread_count' => $this->notificationService->getUnreadCount($userId),
        ], 'Bildirimler başarıyla getirildi');
    }

    /**
     * API: Okunmamış bildirim sayısı
     */
    public function apiUnreadCount()
    {
        $count = $this->notificationService->getUnreadCount(auth()->id());

        return ResponseService::success([
            'count' => $count,
        ], 'Okunmamış bildirim sayısı');
    }
}
