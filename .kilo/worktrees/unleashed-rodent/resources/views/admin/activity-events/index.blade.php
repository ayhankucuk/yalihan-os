@extends('admin.layouts.admin')

@section('title', 'Aktivite Akışı')

@push('meta')
    <meta name="description" content="Rezervasyon ve takvim aktivitelerini görüntüleyin">
    <meta property="og:title" content="Aktivite Akışı - Yalıhan Emlak">
    <meta property="og:description" content="Rezervasyon ve takvim aktivitelerini görüntüleyin">
    <meta property="og:type" content="website">
@endpush

@section('content')
    <!-- Header -->
    <div class="content-header mb-8">
        <h1 class="text-3xl font-bold flex items-center">
            <div
                class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl flex items-center justify-center mr-4">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            📊 Aktivite Akışı
        </h1>
    </div>

    <div class="px-6">
        <!-- İstatistik Kartları -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Toplam Aktivite -->
            <div
                class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-6">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-blue-800 dark:text-blue-200">{{ $statistics['total'] ?? 0 }}
                        </h3>
                        <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">Toplam Aktivite</p>
                    </div>
                </div>
            </div>

            <!-- Telegram Aktiviteleri -->
            <div
                class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl border border-green-200 dark:border-green-800 p-6">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-green-800 dark:text-green-200">
                            {{ $statistics['by_source']['telegram'] ?? 0 }}</h3>
                        <p class="text-sm text-green-600 dark:text-green-400 font-medium">Telegram</p>
                    </div>
                </div>
            </div>

            <!-- Admin Aktiviteleri -->
            <div
                class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border border-purple-200 dark:border-purple-800 p-6">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-purple-800 dark:text-purple-200">
                            {{ $statistics['by_source']['admin'] ?? 0 }}</h3>
                        <p class="text-sm text-purple-600 dark:text-purple-400 font-medium">Admin Panel</p>
                    </div>
                </div>
            </div>

            <!-- Rezervasyon Aktiviteleri -->
            <div
                class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-xl border border-orange-200 dark:border-orange-800 p-6">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-orange-500 to-amber-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-orange-800 dark:text-orange-200">
                            {{ $statistics['by_entity_type']['reservation'] ?? 0 }}</h3>
                        <p class="text-sm text-orange-600 dark:text-orange-400 font-medium">Rezervasyon</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtreler -->
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 mb-6">
            <form method="GET" action="{{ route('admin.activity-events.index') }}"
                class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Entity Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Entity Type</label>
                    <select name="entity_type"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Tümü</option>
                        <option value="reservation" {{ $filters['entity_type'] === 'reservation' ? 'selected' : '' }}>
                            Rezervasyon</option>
                        <option value="calendar" {{ $filters['entity_type'] === 'calendar' ? 'selected' : '' }}>Takvim
                        </option>
                        <option value="ilan" {{ $filters['entity_type'] === 'ilan' ? 'selected' : '' }}>İlan</option>
                    </select>
                </div>

                <!-- Action -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Action</label>
                    <select name="action"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Tümü</option>
                        <option value="create" {{ $filters['action'] === 'create' ? 'selected' : '' }}>Oluştur</option>
                        <option value="confirm" {{ $filters['action'] === 'confirm' ? 'selected' : '' }}>Onayla</option>
                        <option value="cancel" {{ $filters['action'] === 'cancel' ? 'selected' : '' }}>İptal</option>
                        <option value="close_calendar" {{ $filters['action'] === 'close_calendar' ? 'selected' : '' }}>
                            Takvim Kapat</option>
                    </select>
                </div>

                <!-- Source -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Source</label>
                    <select name="source"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Tümü</option>
                        <option value="telegram" {{ $filters['source'] === 'telegram' ? 'selected' : '' }}>Telegram
                        </option>
                        <option value="admin" {{ $filters['source'] === 'admin' ? 'selected' : '' }}>Admin Panel
                        </option>
                        <option value="system" {{ $filters['source'] === 'system' ? 'selected' : '' }}>Sistem</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 hover:scale-105 active:scale-95">
                        Filtrele
                    </button>
                    <a href="{{ route('admin.activity-events.index') }}"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200 dark:text-slate-300">
                        Temizle
                    </a>
                </div>
            </form>
        </div>

        <!-- Aktivite Listesi -->
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg overflow-hidden">
            @if ($activities->count() > 0)
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($activities as $activity)
                        <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        @php
                                            $actionEmoji = match ($activity->action) {
                                                'create' => '➕',
                                                'confirm' => '✅',
                                                'cancel' => '❌',
                                                'close_calendar' => '🔒',
                                                default => '📋',
                                            };

                                            $sourceBadge = match ($activity->source) {
                                                'telegram'
                                                    => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                                                'admin'
                                                    => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
                                                'system'
                                                    => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200',
                                                default
                                                    => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200',
                                            };
                                        @endphp

                                        <span class="text-2xl">{{ $actionEmoji }}</span>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                                            {{ $activity->summary }}
                                        </h3>
                                        <span class="px-2 py-1 text-xs rounded-full {{ $sourceBadge }}">
                                            {{ ucfirst($activity->source) }}
                                        </span>
                                    </div>

                                    <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400 mb-2">
                                        <span>{{ $activity->created_at->diffForHumans() }}</span>
                                        @if ($activity->user)
                                            <span>👤 {{ $activity->user->name }}</span>
                                        @endif
                                        @if ($activity->telegram_user_id)
                                            <span>📱 Telegram User #{{ $activity->telegram_user_id }}</span>
                                        @endif
                                    </div>

                                    @if ($activity->context)
                                        <div class="mt-3 text-sm text-gray-600 dark:text-slate-200">
                                            @if (isset($activity->context['ilan_id']))
                                                <a href="{{ route('admin.ilanlar.show', $activity->context['ilan_id']) }}"
                                                    class="text-blue-600 dark:text-blue-400 hover:underline">
                                                    İlan #{{ $activity->context['ilan_id'] }} →
                                                </a>
                                            @endif
                                            @if (isset($activity->context['reservation_id']))
                                                <span class="ml-2">Rezervasyon
                                                    #{{ $activity->context['reservation_id'] }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Phase V: Action Buttons --}}
                                    @if ($activity->entity_type === 'reservation' && isset($activity->context['ilan_id']))
                                        @php
                                            $ilanId = $activity->context['ilan_id'];
                                            $reservationId = $activity->context['reservation_id'] ?? null;
                                            // Status from context (logged at time of activity)
                                            $status = $activity->context['status'] ?? null;
                                            // Check if reservation is still active (for action buttons)
                                            $isActive = $status === 'active';
                                        @endphp
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <a href="{{ route('admin.ilanlar.calendar', $ilanId) }}?reservation={{ $reservationId }}"
                                                class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-all duration-200 hover:scale-105 active:scale-95">
                                                🔍 Detay
                                            </a>
                                            @if ($isActive && $reservationId)
                                                <form
                                                    action="{{ route('admin.ilanlar.calendar.confirm', ['ilan' => $ilanId, 'reservation' => $reservationId]) }}"
                                                    method="POST" class="inline" x-data="{ loading: false }"
                                                    @submit.prevent="loading = true; fetch($event.target.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } }).then(r => r.json()).then(data => { loading = false; if(data.success) { window.location.reload(); } else { alert(data.message || 'Hata'); } });">
                                                    @csrf
                                                    <button type="submit" :disabled="loading"
                                                        class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-all duration-200 hover:scale-105 active:scale-95 disabled:opacity-50">
                                                        <span x-show="!loading">✅ Onayla</span>
                                                        <span x-show="loading" x-cloak>⏳ İşleniyor...</span>
                                                    </button>
                                                </form>
                                                <form
                                                    action="{{ route('admin.ilanlar.calendar.cancel', ['ilan' => $ilanId, 'reservation' => $reservationId]) }}"
                                                    method="POST" class="inline" x-data="{ loading: false }"
                                                    @submit.prevent="loading = true; fetch($event.target.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } }).then(r => r.json()).then(data => { loading = false; if(data.success) { window.location.reload(); } else { alert(data.message || 'Hata'); } });">
                                                    @csrf
                                                    <button type="submit" :disabled="loading"
                                                        class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-all duration-200 hover:scale-105 active:scale-95 disabled:opacity-50">
                                                        <span x-show="!loading">❌ İptal</span>
                                                        <span x-show="loading" x-cloak>⏳ İşleniyor...</span>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($activity->entity_type === 'calendar' && isset($activity->context['ilan_id']))
                                        <div class="mt-4">
                                            <a href="{{ route('admin.ilanlar.calendar', $activity->context['ilan_id']) }}"
                                                class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition-all duration-200 hover:scale-105 active:scale-95">
                                                📅 Takvime Git
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    {{ $activities->links() }}
                </div>
            @else
                <div class="p-12 text-center">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 text-lg">Henüz aktivite bulunmuyor</p>
                </div>
            @endif
        </div>
    </div>
@endsection
