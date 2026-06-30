{{-- Notification Dropdown Component --}}
{{-- Context7: Pure Tailwind CSS, Alpine.js, Dark Mode Support --}}

<div x-data="notificationDropdown()" x-init="init()" class="relative" @click.away="open = false">
    {{-- Notification Bell Button --}}
    <button @click="open = !open"
        class="relative inline-flex items-center justify-center w-10 h-10 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-200 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
        aria-label="Bildirimler" aria-expanded="false" :aria-expanded="open.toString()">
        <i class="fas fa-bell text-lg"></i>

        {{-- Unread Count Badge --}}
        <span x-show="unreadCount > 0" x-text="unreadCount"
            class="absolute -top-1 -right-1 flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-semibold text-white bg-red-600 rounded-full border-2 border-white dark:border-gray-900 animate-pulse"></span>
    </button>

    {{-- Dropdown Menu --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-2 w-80 md:w-96 bg-white dark:bg-slate-900 rounded-lg shadow-xl border border-gray-200 dark:border-slate-800 z-50 max-h-[500px] overflow-hidden flex flex-col dark:border-slate-700"
        style="display: none;">
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                Bildirimler
                <span x-show="unreadCount > 0" x-text="'(' + unreadCount + ')'"
                    class="text-gray-500 dark:text-gray-400"></span>
            </h3>
            <button @click="markAllAsRead()" x-show="unreadCount > 0"
                class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium transition-colors duration-200">
                Tümünü Oku
            </button>
        </div>

        {{-- Loading State --}}
        <div x-show="loading" class="p-4 text-center">
            <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 dark:border-blue-400">
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Yükleniyor...</p>
        </div>

        {{-- Empty State --}}
        <div x-show="!loading && notifications.length === 0" class="p-6 text-center">
            <i class="fas fa-bell-slash text-3xl text-gray-300 dark:text-gray-600 mb-2"></i>
            <p class="text-sm text-gray-500 dark:text-gray-400">Henüz bildirim yok</p>
        </div>

        {{-- Notifications List --}}
        <div x-show="!loading && notifications.length > 0" class="overflow-y-auto flex-1" style="max-height: 400px;">
            <template x-for="notification in notifications" :key="notification.id">
                <div @click="markAsRead(notification.id, notification.data?.action_url)"
                    class="px-4 py-3 border-b border-gray-100 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors duration-200"
                    :class="{ 'bg-blue-50 dark:bg-blue-900/20': !notification.read_at }">
                    <div class="flex items-start gap-3">
                        {{-- Icon --}}
                        <div class="flex-shrink-0 mt-0.5">
                            <template x-if="notification.data?.type === 'matching_listing_found'">
                                <i class="fas fa-bolt text-yellow-500 text-lg"></i>
                            </template>
                            <template x-if="notification.data?.type !== 'matching_listing_found'">
                                <i class="fas fa-info-circle text-blue-500 text-lg"></i>
                            </template>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100"
                                x-text="notification.data?.title || 'Bildirim'"></p>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 line-clamp-2"
                                x-text="notification.data?.message || ''"></p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500" x-text="notification.created_at">
                            </p>
                        </div>

                        {{-- Unread Indicator --}}
                        <div x-show="!notification.read_at" class="flex-shrink-0">
                            <span class="w-2 h-2 bg-blue-600 dark:bg-blue-400 rounded-full block"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div x-show="!loading && notifications.length > 0"
            class="px-4 py-2 border-t border-gray-200 dark:border-slate-800 text-center dark:border-slate-700">
            <a href="{{ route('admin.notifications.index') }}"
                class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium transition-colors duration-200">
                Tümünü Gör
            </a>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function notificationDropdown() {
            return {
                open: false,
                loading: false,
                notifications: [],
                unreadCount: 0,
                pollInterval: null,
                pollingDisabled: false,

                init() {
                    this.fetchNotifications();
                    // Poll every 30 seconds, but only if the tab is visible
                    this.pollInterval = setInterval(() => {
                        if (this.pollingDisabled) {
                            return;
                        }
                        if (document.visibilityState === 'visible') {
                            this.fetchNotifications();
                        } else {
                            console.debug('Notification polling skipped (tab hidden)');
                        }
                    }, 30000);
                },

                async fetchNotifications() {
                    if (this.pollingDisabled) {
                        return;
                    }

                    try {
                        this.loading = true;
                        const result = await window.safeJsonFetch('/api/v1/admin/notifications/unread');

                        if (result.authRequired) {
                            // Auth gerekli — tekrar eden 403 spamini durdur.
                            this.pollingDisabled = true;
                            if (this.pollInterval) {
                                clearInterval(this.pollInterval);
                                this.pollInterval = null;
                            }
                            this.notifications = [];
                            this.unreadCount = 0;
                            return;
                        }

                        if (result.ok && result.data?.success) {
                            // ResponseService format: { success, message, data: { notifications, count } }
                            this.notifications = result.data.data?.notifications || result.data.data || [];
                            this.unreadCount = result.data.data?.count || 0;
                        } else if (result.error) {
                            console.warn('Notification fetch:', result.error);
                            this.notifications = [];
                            this.unreadCount = 0;
                        }
                    } catch (error) {
                        console.warn('Notification fetch error:', error.message);
                        this.notifications = [];
                        this.unreadCount = 0;
                    } finally {
                        this.loading = false;
                    }
                },

                async markAsRead(notificationId, actionUrl = null) {
                    try {
                        const result = await window.safeJsonFetch(`/api/v1/admin/notifications/${notificationId}/read`, {
                            method: 'POST'
                        });

                        if (result.ok) {
                            // Update local state
                            const notification = this.notifications.find(n => n.id === notificationId);
                            if (notification) {
                                notification.read_at = new Date().toISOString();
                            }
                            this.unreadCount = Math.max(0, this.unreadCount - 1);

                            // Navigate to action URL if provided
                            if (actionUrl) {
                                window.location.href = actionUrl;
                            }
                        }
                    } catch (error) {
                        console.error('Mark as read error:', error);
                    }
                },

                async markAllAsRead() {
                    try {
                        const result = await window.safeJsonFetch('/api/v1/admin/notifications/mark-all-read', {
                            method: 'POST'
                        });

                        if (result.ok) {
                            // Update all notifications as read
                            this.notifications.forEach(n => {
                                n.read_at = new Date().toISOString();
                            });
                            this.unreadCount = 0;
                        }
                    } catch (error) {
                        console.error('Mark all as read error:', error);
                    }
                }
            }
        }
    </script>
@endpush
