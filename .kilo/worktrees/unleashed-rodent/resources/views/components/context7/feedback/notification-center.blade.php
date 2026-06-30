@props([
    'notifications' => [],
    'maxHeight' => '400px',
])

<div x-data="{
    open: false,
    notifications: @js($notifications),
    unreadCount: {{ count(array_filter($notifications, fn($n) => !$n['read'])) }},
    markAsRead(id) {
        const notification = this.notifications.find(n => n.id === id);
        if (notification) {
            notification.read = true;
            this.unreadCount = this.notifications.filter(n => !n.read).length;
        }
    },
    markAllAsRead() {
        this.notifications.forEach(n => n.read = true);
        this.unreadCount = 0;
    },
    clearAll() {
        this.notifications = [];
        this.unreadCount = 0;
    }
}" class="relative">
    <!-- Notification Button -->
    <button @click="open = !open"
        class="relative p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM9 12l2 2 4-4">
            </path>
        </svg>

        <!-- Unread count badge -->
        <span x-show="unreadCount > 0" x-text="unreadCount"
            class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center notification-count"></span>
    </button>

    <!-- Notification Panel -->
    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50 dark:bg-slate-900"
        style="max-height: {{ $maxHeight }};">
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-slate-100 dark:text-white">Bildirimler</h3>
                <div class="flex items-center space-x-2">
                    <button @click="markAllAsRead()" class="text-sm text-blue-600 hover:text-blue-800"
                        x-show="unreadCount > 0">
                        Tümünü Okundu İşaretle
                    </button>
                    <button @click="clearAll()" class="text-sm text-red-600 hover:text-red-800"
                        x-show="notifications.length > 0">
                        Temizle
                    </button>
                </div>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="overflow-y-auto" style="max-height: calc({{ $maxHeight }} - 60px);">
            <template x-if="notifications.length === 0">
                <div class="px-4 py-8 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-5 5v-5zM9 12l2 2 4-4"></path>
                    </svg>
                    <p class="mt-2 text-sm">Henüz bildirim yok</p>
                </div>
            </template>

            <template x-for="notification in notifications" :key="notification.id">
                <div @click="markAsRead(notification.id)"
                    class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors dark:border-slate-800"
                    :class="{ 'bg-blue-50': !notification.read }">
                    <div class="flex items-start space-x-3">
                        <!-- Icon -->
                        <div class="flex-shrink-0">
                            <div class="w-2 h-2 rounded-full mt-2"
                                :class="{
                                    'bg-blue-500': notification.type === 'info',
                                    'bg-green-500': notification.type === 'success',
                                    'bg-yellow-500': notification.type === 'warning',
                                    'bg-red-500': notification.type === 'error'
                                }"
                                x-show="!notification.read"></div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white" x-text="notification.title"></p>
                            <p class="text-sm text-gray-500 mt-1" x-text="notification.message"></p>
                            <p class="text-xs text-gray-400 mt-1" x-text="formatTime(notification.timestamp)"></p>
                        </div>

                        <!-- Actions -->
                        <div class="flex-shrink-0">
                            <button @click.stop="removeNotification(notification.id)"
                                class="text-gray-400 hover:text-gray-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-200 dark:border-slate-700" x-show="notifications.length > 0">
            <a href="#" class="block text-center text-sm text-blue-600 hover:text-blue-800">
                Tüm bildirimleri görüntüle
            </a>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        // Add utility functions to Alpine.js
        document.addEventListener('alpine:init', () => {
            Alpine.data('notificationCenter', () => ({
                formatTime(date) {
                    const now = new Date();
                    const diff = now - new Date(date);
                    const minutes = Math.floor(diff / 60000);
                    const hours = Math.floor(diff / 3600000);
                    const days = Math.floor(diff / 86400000);

                    if (minutes < 1) return 'Şimdi';
                    if (minutes < 60) return `${minutes} dakika önce`;
                    if (hours < 24) return `${hours} saat önce`;
                    return `${days} gün önce`;
                },

                removeNotification(id) {
                    this.notifications = this.notifications.filter(n => n.id !== id);
                    this.unreadCount = this.notifications.filter(n => !n.read).length;
                }
            }));
        });

        // Real-time notification handling
        document.addEventListener('DOMContentLoaded', function() {
            if (window.realTimeManager) {
                // Listen for new notifications
                window.realTimeManager.pusher.connection.bind('connected', () => {
                    console.log('Real-time notifications status');
                });
            }
        });
    </script>
@endpush
