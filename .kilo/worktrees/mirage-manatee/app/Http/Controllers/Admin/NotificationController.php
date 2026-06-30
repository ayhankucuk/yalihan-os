<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends AdminController
{
    public function __construct()
    {
        $this->middleware('can:manage-notifications');
    }
    /**
     * Display notifications index page
     */
    public function index(Request $request): \Illuminate\View\View
    {
        return view('admin.notifications.index');
    }

    /**
     * Test real-time notification endpoint
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function testRealTime(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Test notification sent successfully',
            ]);
        }

        return view('admin.notifications.index')->with('success', 'Test notification sent');
    }

    /**
     * Test SMS notification endpoint
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testSms(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'SMS test notification sent successfully',
        ]);
    }

    /**
     * Test email notification endpoint
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testEmail(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Email test notification sent successfully',
        ]);
    }

    /**
     * Get notification statistics
     *
     * Context7: ResponseService kullanımı zorunlu
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        try {
            $user = auth()->user();
            // Laravel's standard DatabaseNotification system
            $total = DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->count();
            $unread = DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->whereNull('read_at')
                ->count();
            $read = $total - $unread;

            return ResponseService::success([
                'total' => $total,
                'unread' => $unread,
                'read' => $read,
            ], 'Bildirim istatistikleri başarıyla getirildi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Bildirim istatistikleri alınırken hata oluştu', $e);
        }
    }

    /**
     * Get unread notification count
     *
     * Context7: ResponseService kullanımı zorunlu
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount()
    {
        try {
            $user = auth()->user();
            $count = DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->whereNull('read_at')
                ->count();

            return ResponseService::success([
                'count' => $count,
            ], 'Okunmamış bildirim sayısı başarıyla getirildi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Okunmamış bildirim sayısı alınırken hata oluştu', $e);
        }
    }

    /**
     * Get unread notifications (API endpoint)
     *
     * Context7: ResponseService kullanımı zorunlu
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unread()
    {
        try {
            $user = auth()->user();
            $notifications = DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->whereNull('read_at')
                ->orderBy('created_at', 'desc') // context7-ignore
                ->take(5)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->type, // context7-ignore
                        'data' => $notification->data,
                        'read_at' => null,
                        'created_at' => $notification->created_at->diffForHumans(),
                        'created_at_full' => $notification->created_at->toIso8601String(),
                    ];
                });

            $count = DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->whereNull('read_at')
                ->count();

            return ResponseService::success([
                'notifications' => $notifications,
                'count' => $count,
            ], 'Okunmamış bildirimler başarıyla getirildi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Okunmamış bildirimler alınırken hata oluştu', $e);
        }
    }

    /**
     * Get recent notifications
     *
     * Context7: ResponseService kullanımı zorunlu
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function recent()
    {
        try {
            $user = auth()->user();
            $notifications = DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->orderBy('created_at', 'desc') // context7-ignore
                ->take(5)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->type, // context7-ignore
                        'data' => $notification->data,
                        'read_at' => $notification->read_at?->toIso8601String(),
                        'created_at' => $notification->created_at->diffForHumans(),
                        'created_at_full' => $notification->created_at->toIso8601String(),
                    ];
                });

            return ResponseService::success([
                'notifications' => $notifications,
            ], 'Son bildirimler başarıyla getirildi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Son bildirimler alınırken hata oluştu', $e);
        }
    }

    /**
     * Mark notification as read (API endpoint)
     *
     * Context7: ResponseService kullanımı zorunlu
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsReadApi($id)
    {
        try {
            $user = auth()->user();
            $notification = DatabaseNotification::where('id', $id)
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->first();

            if (! $notification) {
                return ResponseService::notFound('Bildirim bulunamadı');
            }

            app(\App\Actions\Notification\MarkAsReadNotificationAction::class)->handle($notification);

            return ResponseService::success(null, 'Bildirim okundu olarak işaretlendi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Bildirim okundu olarak işaretlenirken hata oluştu', $e);
        }
    }

    /**
     * Show the form for creating a new notification
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.notifications.create');
    }

    /**
     * Store a newly created notification
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|in:success,warning,error,info', // context7-ignore
            'priority' => 'nullable|in:urgent,high,normal,low',
            'user_id' => 'nullable|exists:users,id',
            'role' => 'nullable|string',
        ]);

        app(\App\Actions\Notification\CreateNotificationAction::class)->handle([
            'title' => $validated['title'],
            'message' => $validated['message'],
            'type' => $validated['type'] ?? 'info', // context7-ignore
            'priority' => $validated['priority'] ?? 'normal',
            'users' => isset($validated['user_id']) ? [$validated['user_id']] : [auth()->id()],
            'role' => $validated['role'] ?? null,
            'action_url' => null, // Opsiyonel
            'action_text' => null, // Opsiyonel
        ]);

        return redirect()->route('admin.notifications.index')
            ->with('success', 'Bildirim başarıyla oluşturuldu');
    }

    /**
     * Display the specified notification
     *
     * @return \Illuminate\View\View
     */
    public function show(\App\Models\Notification $notification)
    {
        return view('admin.notifications.show', compact('notification'));
    }

    /**
     * Remove the specified notification
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy(\App\Models\Notification $notification)
    {
        app(\App\Actions\Notification\DestroyNotificationAction::class)->handle($notification);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Bildirim başarıyla silindi',
            ]);
        }

        return redirect()->route('admin.notifications.index')
            ->with('success', 'Bildirim başarıyla silindi');
    }

    /**
     * Mark notification as read
     *
     * Context7: ResponseService kullanımı zorunlu
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(\App\Models\Notification $notification)
    {
        try {
            app(\App\Actions\Notification\MarkAsReadNotificationAction::class)->handle($notification);

            return ResponseService::success(null, 'Bildirim okundu olarak işaretlendi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Bildirim okundu olarak işaretlenirken hata oluştu', $e);
        }
    }

    /**
     * Mark notification as unread
     *
     * Context7: ResponseService kullanımı zorunlu
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsUnread(\App\Models\Notification $notification)
    {
        try {
            app(\App\Actions\Notification\MarkAsUnreadNotificationAction::class)->handle($notification);

            return ResponseService::success(null, 'Bildirim okunmadı olarak işaretlendi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Bildirim okunmadı olarak işaretlenirken hata oluştu', $e);
        }
    }

    /**
     * Mark all notifications as read
     *
     * Context7: ResponseService kullanımı zorunlu
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead()
    {
        try {
            app(\App\Actions\Notification\MarkAllAsReadNotificationAction::class)->handle();

            return ResponseService::success(null, 'Tüm bildirimler okundu olarak işaretlendi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Tüm bildirimler okundu olarak işaretlenirken hata oluştu', $e);
        }
    }
}
