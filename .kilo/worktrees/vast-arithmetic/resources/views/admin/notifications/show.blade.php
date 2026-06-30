@extends('admin.layouts.admin')

@section('title', 'Bildirim Detayı')

@section('content')
    <!-- ✅ SAB: Page Header - Tailwind CSS -->
    <div class="mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Bildirim Detayı</h1>
                <nav class="flex items-center space-x-2 text-sm" aria-label="Breadcrumb">
                    <a href="{{ route('admin.notifications.index') }}"
                        class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors duration-200">
                        Bildirimler
                    </a>
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $notification->title }}</span>
                </nav>
            </div>
            <div class="flex items-center gap-3">
                @if ($notification->isUnread())
                    <button onclick="markAsRead({{ $notification->id }})"
                        class="inline-flex items-center px-4 py-2.5 bg-orange-600 text-white font-medium rounded-lg shadow-sm hover:bg-orange-700 hover:shadow-md active:scale-95 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200 dark:shadow-none">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Okundu İşaretle
                    </button>
                @else
                    <button onclick="markAsUnread({{ $notification->id }})"
                        class="inline-flex items-center px-4 py-2.5 bg-gray-600 text-white font-medium rounded-lg shadow-sm hover:bg-gray-700 hover:shadow-md active:scale-95 focus:ring-2 focus:ring-gray-500 focus:outline-none transition-all duration-200 dark:shadow-none">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                            </path>
                        </svg>
                        Okunmadı İşaretle
                    </button>
                @endif
                <button onclick="deleteNotification({{ $notification->id }})"
                    class="inline-flex items-center px-4 py-2.5 bg-red-600 text-white font-medium rounded-lg shadow-sm hover:bg-red-700 hover:shadow-md active:scale-95 focus:ring-2 focus:ring-red-500 focus:outline-none transition-all duration-200 dark:shadow-none">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                        </path>
                    </svg>
                    Sil
                </button>
            </div>
        </div>
    </div>

    <!-- ✅ SAB: Main Content - Tailwind CSS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Ana İçerik -->
        <div class="lg:col-span-2">
            <div
                class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <!-- Icon -->
                        <div class="flex-shrink-0">
                            @if ($notification->type === 'success')
                                <div
                                    class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            @elseif($notification->type === 'warning')
                                <div
                                    class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z">
                                        </path>
                                    </svg>
                                </div>
                            @elseif($notification->type === 'error')
                                <div
                                    class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                            @else
                                <div
                                    class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <!-- Başlık ve Durum -->
                        <div class="flex-1 min-w-0">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white truncate dark:text-slate-100">
                                {{ $notification->title }}</h2>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                @if ($notification->isUnread())
                                    <x-badge value="Okunmamış" category="unread" />
                                @else
                                    <x-badge value="Okunmuş" category="status" />
                                @endif
                                <x-badge :value="ucfirst($notification->priority)" category="priority" />
                                <x-badge :value="ucfirst($notification->type)" category="type" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <p class="text-gray-700 dark:text-slate-200 leading-relaxed whitespace-pre-wrap dark:text-slate-300">
                        {{ $notification->message }}</p>

                    @if ($notification->data && count($notification->data) > 0)
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                            <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3 dark:text-slate-100">Ek Bilgiler</h3>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 dark:bg-slate-900">
                                <pre class="text-sm text-gray-700 dark:text-slate-200 whitespace-pre-wrap font-mono dark:text-slate-300">{{ json_encode($notification->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Yan Panel -->
        <div class="space-y-6">
            <!-- Bildirim Bilgileri -->
            <div
                class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white dark:text-slate-100">Bildirim Bilgileri</h3>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Gönderen</dt>
                            <dd class="text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                @if ($notification->sender)
                                    {{ $notification->sender->name }}
                                    <span
                                        class="text-gray-500 dark:text-gray-400">({{ $notification->sender->email }})</span>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">Sistem</span>
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Gönderilme Tarihi</dt>
                            <dd class="text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                {{ $notification->created_at->format('d.m.Y H:i') }}</dd>
                        </div>

                        @if ($notification->read_at)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Okunma Tarihi</dt>
                                <dd class="text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                    {{ $notification->read_at->format('d.m.Y H:i') }}</dd>
                            </div>
                        @endif

                        @if ($notification->sent_at)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Gönderilme Tarihi
                                </dt>
                                <dd class="text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                    {{ $notification->sent_at->format('d.m.Y H:i') }}</dd>
                            </div>
                        @endif

                        @if ($notification->expires_at)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Son Geçerlilik</dt>
                                <dd class="text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                    {{ $notification->expires_at->format('d.m.Y H:i') }}</dd>
                            </div>
                        @endif

                        @if ($notification->channel)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Kanal</dt>
                                <dd class="text-sm text-gray-900 dark:text-white dark:text-slate-100">{{ ucfirst($notification->channel) }}
                                </dd>
                            </div>
                        @endif

                        @if ($notification->read_at)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Okunma Zamanı</dt>
                                <dd class="text-sm text-gray-900 dark:text-white dark:text-slate-100">{{ $notification->read_at->format('d.m.Y H:i') }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- İlgili Varlık -->
            @if ($notification->related)
                <div
                    class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white dark:text-slate-100">İlgili Varlık</h3>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0">
                                <div
                                    class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center dark:bg-slate-900">
                                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                                        </path>
                                    </svg>
                                </div>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate dark:text-slate-100">
                                    {{ class_basename($notification->related_type) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">ID: {{ $notification->related_id }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Hızlı İşlemler -->
            <div
                class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white dark:text-slate-100">Hızlı İşlemler</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @if ($notification->isUnread())
                            <button onclick="markAsRead({{ $notification->id }})"
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-orange-600 text-white font-medium rounded-lg shadow-sm hover:bg-orange-700 hover:shadow-md active:scale-95 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200 dark:shadow-none">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                Okundu İşaretle
                            </button>
                        @else
                            <button onclick="markAsUnread({{ $notification->id }})"
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-gray-600 text-white font-medium rounded-lg shadow-sm hover:bg-gray-700 hover:shadow-md active:scale-95 focus:ring-2 focus:ring-gray-500 focus:outline-none transition-all duration-200 dark:shadow-none">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                                    </path>
                                </svg>
                                Okunmadı İşaretle
                            </button>
                        @endif

                        <button onclick="deleteNotification({{ $notification->id }})"
                            class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-red-600 text-white font-medium rounded-lg shadow-sm hover:bg-red-700 hover:shadow-md active:scale-95 focus:ring-2 focus:ring-red-500 focus:outline-none transition-all duration-200 dark:shadow-none">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                            Bildirimi Sil
                        </button>

                        <a href="{{ route('admin.notifications.index') }}"
                            class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-gray-600 text-white font-medium rounded-lg shadow-sm hover:bg-gray-700 hover:shadow-md active:scale-95 focus:ring-2 focus:ring-gray-500 focus:outline-none transition-all duration-200 dark:shadow-none">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Geri Dön
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // ✅ SAB: Toast notification system
        function showToast(message, type = 'success') {
            if (window.showToast) {
                window.showToast(message, type);
            } else {
                alert(message);
            }
        }

        function showConfirm(message, callback) {
            if (window.showConfirm) {
                window.showConfirm(message, callback);
            } else if (confirm(message)) {
                callback();
            }
        }

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
                        showToast('Bildirim okundu olarak işaretlendi', 'success');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        showToast(data.message || 'Bir hata oluştu', 'error');
                    }
                })
                .catch(error => {
                    showToast('Bir hata oluştu', 'error');
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
                        showToast('Bildirim okunmadı olarak işaretlendi', 'success');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        showToast(data.message || 'Bir hata oluştu', 'error');
                    }
                })
                .catch(error => {
                    showToast('Bir hata oluştu', 'error');
                });
        }

        function deleteNotification(notificationId) {
            showConfirm('Bu bildirimi silmek istediğinizden emin misiniz?', () => {
                fetch(`/admin/notifications/${notificationId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content'),
                            'Content-Type': 'application/json',
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Bildirim başarıyla silindi', 'success');
                            setTimeout(() => {
                                window.location.href = '/admin/notifications';
                            }, 500);
                        } else {
                            showToast(data.message || 'Bir hata oluştu', 'error');
                        }
                    })
                    .catch(error => {
                        showToast('Bir hata oluştu', 'error');
                    });
            });
        }
    </script>
@endpush
