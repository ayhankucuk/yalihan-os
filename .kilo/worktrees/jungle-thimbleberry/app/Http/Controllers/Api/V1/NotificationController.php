<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\NotificationResource;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List user notifications
     */
    public function index(Request $request)
    {
        $limit = $request->query('limit', 20);

        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate($limit);

        return ResponseService::success(
            NotificationResource::collection($notifications)->response()->getData(true),
            'Bildirimler getirildi'
        );
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return ResponseService::notFound('Bildirim bulunamadı');
        }

        $notification->markAsRead();

        return ResponseService::success(null, 'Bildirim okundu olarak işaretlendi');
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
        return ResponseService::success(null, 'Tüm bildirimler okundu olarak işaretlendi');
    }
}
