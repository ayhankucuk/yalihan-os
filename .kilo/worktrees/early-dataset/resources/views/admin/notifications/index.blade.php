@extends('admin.layouts.admin')

@section('title', 'Bildirimler')

@push('meta')
    <meta name="description" content="Sistem bildirimleri ve kullanıcı mesajlarını yönetin. Gerçek zamanlı bildirim takibi ve yönetimi.">
    <meta property="og:title" content="Bildirimler - Yalıhan Emlak">
    <meta property="og:description" content="Sistem bildirimleri ve kullanıcı mesajlarını yönetin">
    <meta property="og:type" content="website">
@endpush

@section('content')
    <div x-data="{ markingAll: false, loading: false }">
    <!-- Context7 Header -->
    <div class="content-header mb-8">
        <h1 class="text-3xl font-bold flex items-center">
            <div
                class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-5 5v-5zM4 19h6v-6H4v6zM4 5h6V1H4v4zM15 3h5v6h-5V3z"></path>
                </svg>
            </div>
            📢 Bildirimler
        </h1>
        <div class="flex items-center space-x-3 mt-4">
            <button @click="markingAll = true; markAllAsRead()"
                    :disabled="markingAll"
                    class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none">
                <svg class="w-4 h-4 mr-2" :class="markingAll ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span x-text="markingAll ? 'İşaretleniyor...' : 'Tümünü Okundu İşaretle'"></span>
            </button>
            @can('create', App\Models\Notification::class)
                <a href="{{ route('admin.notifications.create') }}" class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Yeni Bildirim
                </a>
            @endcan
        </div>
    </div>

    <div class="px-6">
        <!-- Context7 İstatistik Kartları -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Toplam Bildirim Kartı -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200 p-6">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-5 5v-5zM4 19h6v-6H4v6zM4 5h6V1H4v4zM15 3h5v6h-5V3z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-blue-800">{{ $stats['total'] ?? 0 }}</h3>
                        <p class="text-sm text-blue-600 font-medium">Toplam Bildirim</p>
                    </div>
                </div>
            </div>

            <!-- Okunmamış Bildirim Kartı -->
            <div class="bg-gradient-to-r from-orange-50 to-amber-50 rounded-xl border border-orange-200 p-6">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-orange-500 to-amber-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-orange-800">{{ $stats['unread'] ?? 0 }}</h3>
                        <p class="text-sm text-orange-600 font-medium">Okunmamış</p>
                    </div>
                </div>
            </div>

            <!-- Okunmuş Bildirim Kartı -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200 p-6">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-green-800">{{ $stats['read'] ?? 0 }}</h3>
                        <p class="text-sm text-green-600 font-medium">Okunmuş</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtreler -->
        <div class="bg-gray-50 dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 p-6 mb-8 dark:shadow-none dark:border-slate-700">
            <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">🔍 Filtreler</h2>
            <form method="GET"
                  action="{{ route('admin.notifications.index') }}"
                  class="grid grid-cols-1 md:grid-cols-4 gap-4"
                  @submit="loading = true">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">Tür</label>
                    <select style="color-scheme: light dark;" name="type" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                        <option value="">Tümü</option>
                        <option value="info" {{ request('type') == 'info' ? 'selected' : '' }}>Bilgi</option>
                        <option value="success" {{ request('type') == 'success' ? 'selected' : '' }}>Başarı</option>
                        <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>Uyarı</option>
                        <option value="error" {{ request('type') == 'error' ? 'selected' : '' }}>Hata</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">Öncelik</label>
                    <select style="color-scheme: light dark;" name="priority" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                        <option value="">Tümü</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Düşük</option>
                        <option value="normal" {{ request('priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Yüksek</option>
                        <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Acil</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">Aktiflik</label>
                    <select style="color-scheme: light dark;" name="aktiflik_durumu" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                        <option value="all" {{ request('aktiflik_durumu', request('status')) == 'all' ? 'selected' : '' }}>Tümü</option>
                        <option value="unread" {{ request('aktiflik_durumu', request('status')) == 'unread' ? 'selected' : '' }}>Okunmamış</option>
                        <option value="read" {{ request('aktiflik_durumu', request('status')) == 'read' ? 'selected' : '' }}>Okunmuş</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit"
                            :disabled="loading"
                            class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:outline-none transition-all duration-200 w-full touch-target-optimized dark:shadow-none">
                        <svg class="w-4 h-4 mr-2" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <span x-text="loading ? 'Filtreleniyor...' : 'Filtrele'"></span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Bildirim Listesi -->
        <div class="bg-gray-50 dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
            <div class="p-6">
                @if (isset($notifications) && $notifications->count() > 0)
                    <div class="space-y-4">
                        @foreach ($notifications as $notification)
                            <div
                                class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors {{ $notification->isUnread() ? 'bg-blue-50 border-blue-200' : '' }} dark:border-slate-700">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-3 flex-1">
                                        <!-- Icon -->
                                        <div class="flex-shrink-0">
                                            @if ($notification->type === 'success')
                                                <div
                                                    class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-green-600" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </div>
                                            @elseif($notification->type === 'warning')
                                                <div
                                                    class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-yellow-600" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z">
                                                        </path>
                                                    </svg>
                                                </div>
                                            @elseif($notification->type === 'error')
                                                <div
                                                    class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-red-600" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </div>
                                            @else
                                                <div
                                                    class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-blue-600" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                        </path>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Content -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center space-x-2 mb-1">
                                                <h3 class="text-sm font-medium text-gray-900 truncate dark:text-slate-100 dark:text-white">
                                                    {{ $notification->title }}
                                                </h3>
                                                @if ($notification->isUnread())
                                                    <x-badge value="Yeni" category="unread" />
                                                @endif
                                                <x-badge :value="ucfirst($notification->priority)" category="priority" />
                                            </div>
                                            <p class="text-sm text-gray-600 mb-2">
                                                {{ Str::limit($notification->message, 100) }}</p>
                                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                                <span>{{ $notification->time_ago }}</span>
                                                @if ($notification->sender)
                                                    <span>Gönderen: {{ $notification->sender->name }}</span>
                                                @endif
                                                <span>Kanal: {{ ucfirst($notification->channel) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('admin.notifications.show', $notification) }}"
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            Detay
                                        </a>
                                        @if ($notification->isUnread())
                                            <button onclick="markAsRead({{ $notification->id }})"
                                                class="text-green-600 hover:text-green-800 text-sm font-medium">
                                                Okundu İşaretle
                                            </button>
                                        @else
                                            <button onclick="markAsUnread({{ $notification->id }})"
                                                class="text-gray-600 hover:text-gray-800 text-sm font-medium">
                                                Okunmadı İşaretle
                                            </button>
                                        @endif
                                        <button onclick="deleteNotification({{ $notification->id }})"
                                            class="text-red-600 hover:text-red-800 text-sm font-medium">
                                            Sil
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $notifications->appends(request()->query())->links() }}
                    </div>
                @else
                    <x-neo.empty-state title="Bildirim bulunamadı" description="Henüz hiç bildirim bulunmuyor." />
                @endif
            </div>
        </div>
    </div>
    </div> {{-- x-data wrapper close --}}
@endsection

@push('scripts')
    <script>
        function markAsRead(notificationId) {
            fetch(`/admin/notifications/${notificationId}/mark-read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Hata: ' + data.message);
                    }
                });
        }

        function markAsUnread(notificationId) {
            fetch(`/admin/notifications/${notificationId}/mark-unread`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Hata: ' + data.message);
                    }
                });
        }

        function markAllAsRead() {
            if (confirm('Tüm bildirimleri okundu olarak işaretlemek istediğinizden emin misiniz?')) {
                fetch('/admin/notifications/mark-all-read', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Hata: ' + data.message);
                        }
                    });
            }
        }

        function deleteNotification(notificationId) {
            if (confirm('Bu bildirimi silmek istediğinizden emin misiniz?')) {
                fetch(`/admin/notifications/${notificationId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Hata: ' + data.message);
                        }
                    });
            }
        }
    </script>
@endpush
