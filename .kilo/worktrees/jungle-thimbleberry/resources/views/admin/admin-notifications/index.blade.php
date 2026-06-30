@extends('admin.layouts.admin')

@section('title', 'Rezervasyon Bildirimleri')

@push('meta')
    <meta name="description" content="Rezervasyon ve takvim bildirimlerini görüntüleyin ve yönetin">
    <meta property="og:title" content="Rezervasyon Bildirimleri - Yalıhan Emlak">
    <meta property="og:description" content="Rezervasyon ve takvim bildirimlerini görüntüleyin ve yönetin">
    <meta property="og:type" content="website">
@endpush

@section('content')
    <div x-data="{ markingAll: false }">
        <!-- Header -->
        <div class="content-header mb-8">
            <h1 class="text-3xl font-bold flex items-center">
                <div
                    class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                        </path>
                    </svg>
                </div>
                📢 Rezervasyon Bildirimleri
            </h1>
            <div class="flex items-center space-x-3 mt-4">
                <button @click="markingAll = true; markAllAsRead()" :disabled="markingAll"
                    class="inline-flex items-center px-6 py-3 bg-gray-600 dark:bg-gray-700 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700 dark:hover:bg-gray-600 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 dark:shadow-none">
                    <svg class="w-4 h-4 mr-2" :class="markingAll ? 'animate-spin' : ''" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span x-text="markingAll ? 'İşaretleniyor...' : 'Tümünü Okundu İşaretle'"></span>
                </button>
            </div>
        </div>

        <div class="px-6">
            <!-- İstatistik Kartları -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Okunmamış Bildirim -->
                <div
                    class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-xl border border-orange-200 dark:border-orange-800 p-6">
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
                            <h3 class="text-2xl font-bold text-orange-800 dark:text-orange-200">{{ $unreadCount }}</h3>
                            <p class="text-sm text-orange-600 dark:text-orange-400 font-medium">Okunmamış</p>
                        </div>
                    </div>
                </div>

                <!-- Toplam Bildirim -->
                <div
                    class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-6">
                    <div class="flex items-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                                </path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-2xl font-bold text-blue-800 dark:text-blue-200">{{ $notifications->total() }}
                            </h3>
                            <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">Toplam Bildirim</p>
                        </div>
                    </div>
                </div>

                <!-- Okunmuş Bildirim -->
                <div
                    class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl border border-green-200 dark:border-green-800 p-6">
                    <div class="flex items-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-2xl font-bold text-green-800 dark:text-green-200">
                                {{ $notifications->total() - $unreadCount }}</h3>
                            <p class="text-sm text-green-600 dark:text-green-400 font-medium">Okunmuş</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtreler -->
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 mb-6">
                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('admin.admin-notifications.index', ['filter' => 'all', 'channel' => $channel]) }}"
                        class="px-4 py-2 rounded-lg transition-all duration-200 {{ $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 hover:bg-gray-200 dark:hover:bg-gray-600' }} dark:text-slate-300">
                        Tümü
                    </a>
                    <a href="{{ route('admin.admin-notifications.index', ['filter' => 'unread', 'channel' => $channel]) }}"
                        class="px-4 py-2 rounded-lg transition-all duration-200 {{ $filter === 'unread' ? 'bg-orange-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 hover:bg-gray-200 dark:hover:bg-gray-600' }} dark:text-slate-300">
                        Okunmamış
                    </a>
                    <a href="{{ route('admin.admin-notifications.index', ['filter' => 'read', 'channel' => $channel]) }}"
                        class="px-4 py-2 rounded-lg transition-all duration-200 {{ $filter === 'read' ? 'bg-green-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 hover:bg-gray-200 dark:hover:bg-gray-600' }} dark:text-slate-300">
                        Okunmuş
                    </a>
                    <a href="{{ route('admin.admin-notifications.index', ['filter' => $filter, 'channel' => 'reservation']) }}"
                        class="px-4 py-2 rounded-lg transition-all duration-200 {{ $channel === 'reservation' ? 'bg-purple-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 hover:bg-gray-200 dark:hover:bg-gray-600' }} dark:text-slate-300">
                        Rezervasyon
                    </a>
                    <a href="{{ route('admin.admin-notifications.index', ['filter' => $filter, 'channel' => 'calendar']) }}"
                        class="px-4 py-2 rounded-lg transition-all duration-200 {{ $channel === 'calendar' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 hover:bg-gray-200 dark:hover:bg-gray-600' }} dark:text-slate-300">
                        Takvim
                    </a>
                </div>
            </div>

            <!-- Bildirim Listesi -->
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg overflow-hidden">
                @if ($notifications->count() > 0)
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($notifications as $notification)
                            <div
                                class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 {{ !$notification->is_read ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            @if (!$notification->is_read)
                                                <span class="w-2 h-2 bg-blue-600 rounded-full"></span>
                                            @endif
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                                                {{ $notification->title }}
                                            </h3>
                                            <span
                                                class="px-2 py-1 text-xs rounded-full
                                                {{ $notification->channel === 'reservation' ? 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200' : '' }}
                                                {{ $notification->channel === 'calendar' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200' : '' }}
                                                {{ $notification->channel === 'system' ? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200' : '' }}">
                                                {{ ucfirst($notification->channel) }}
                                            </span>
                                        </div>
                                        <p class="text-gray-600 dark:text-slate-200 mb-3">
                                            {{ $notification->message }}
                                        </p>
                                        <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                            <span>{{ $notification->created_at->diffForHumans() }}</span>
                                            @if ($notification->payload && isset($notification->payload['ilan_id']))
                                                <a href="{{ route('admin.ilanlar.show', $notification->payload['ilan_id']) }}"
                                                    class="text-blue-600 dark:text-blue-400 hover:underline">
                                                    İlan #{{ $notification->payload['ilan_id'] }} →
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                    @if (!$notification->is_read)
                                        <form action="{{ route('admin.admin-notifications.mark-read', $notification) }}"
                                            method="POST" class="ml-4">
                                            @csrf
                                            <button type="submit"
                                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 hover:scale-105 active:scale-95">
                                                Okundu İşaretle
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        {{ $notifications->links() }}
                    </div>
                @else
                    <div class="p-12 text-center">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                            </path>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">Henüz bildirim bulunmuyor</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function markAllAsRead() {
            fetch('{{ route('admin.admin-notifications.mark-all-read') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
    </script>
@endsection
