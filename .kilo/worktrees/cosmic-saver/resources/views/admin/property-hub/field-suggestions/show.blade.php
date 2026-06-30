@extends('admin.layouts.admin')

@section('title', 'Öneri Detayı - Property Hub')

@section('content')
    <div x-data="suggestionDetail()" class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                    <a href="{{ route('admin.property-hub.index') }}"
                        class="hover:text-blue-600 transition-all duration-200">Property Hub</a>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <a href="{{ route('admin.property-hub.field-suggestions.index') }}"
                        class="hover:text-blue-600 transition-all duration-200">AI Alan Önerileri</a>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span>{{ $suggestion->label }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $suggestion->label }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    <code class="bg-gray-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-xs">{{ $suggestion->slug }}</code>
                    · ID: {{ $suggestion->id }}
                    · {{ $suggestion->created_at->diffForHumans() }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                @if ($suggestion->isPending())
                    <button @click="performAction('approve')" :disabled="actionLoading"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition-all duration-200">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Onayla
                    </button>
                    <button @click="performAction('reject')" :disabled="actionLoading"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 transition-all duration-200">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reddet
                    </button>
                @elseif ($suggestion->isApproved())
                    <button @click="performAction('apply')" :disabled="actionLoading"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-all duration-200">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Uygula
                    </button>
                @elseif ($suggestion->isApplied())
                    <button @click="performAction('rollback')" :disabled="actionLoading"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50 transition-all duration-200">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        Geri Al
                    </button>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Info --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Suggestion Details --}}
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 dark:shadow-none">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Öneri Bilgileri</h2>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Slug</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-slate-100">
                                    <code
                                        class="bg-gray-100 dark:bg-slate-800 px-2 py-1 rounded">{{ $suggestion->slug }}</code>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Etiket</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-slate-100">{{ $suggestion->label }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Alan Tipi</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-slate-100">
                                    {{ $suggestion->field_type ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Grup</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-slate-100">
                                    {{ $suggestion->group_name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kaynak</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-slate-100">{{ $suggestion->source ?? '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Sebep</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-slate-100">{{ $suggestion->reason ?? '—' }}
                                </dd>
                            </div>
                            @if ($suggestion->feature)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Bağlı Özellik</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-slate-100">
                                        {{ $suggestion->feature->name }} ({{ $suggestion->feature->slug }})
                                    </dd>
                                </div>
                            @endif
                            @if ($suggestion->appliedAssignment)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Uygulanmış Atama</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-slate-100">
                                        ID: {{ $suggestion->applied_assignment_id }}
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                {{-- Score Breakdown --}}
                @if ($suggestion->score_json)
                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 dark:shadow-none">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Skor Dağılımı</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                @foreach ($suggestion->score_json as $dimension => $score)
                                    <div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span
                                                class="font-medium text-gray-700 dark:text-slate-300">{{ ucfirst(str_replace('_', ' ', $dimension)) }}</span>
                                            <span class="text-gray-500 dark:text-gray-400">{{ $score }}/100</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-slate-800 rounded-full h-2">
                                            <div class="h-2 rounded-full transition-all duration-500
                                                @if ($score >= 70) bg-green-500
                                                @elseif ($score >= 40) bg-amber-500
                                                @else bg-gray-400 @endif"
                                                style="width: {{ min($score, 100) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-700 flex justify-between">
                                <span class="text-sm font-bold text-gray-900 dark:text-slate-100">Toplam Skor</span>
                                <span
                                    class="text-lg font-bold
                                    @if ($suggestion->total_score >= 70) text-green-600 dark:text-green-400
                                    @elseif ($suggestion->total_score >= 40) text-amber-600 dark:text-amber-400
                                    @else text-gray-600 dark:text-gray-400 @endif">
                                    {{ $suggestion->total_score }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Action History --}}
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 dark:shadow-none">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">İşlem Geçmişi</h2>
                    </div>
                    <div class="p-6">
                        @forelse ($suggestion->actions as $action)
                            <div
                                class="flex gap-4 {{ !$loop->last ? 'pb-4 mb-4 border-b border-gray-100 dark:border-slate-800' : '' }}">
                                <div class="flex-shrink-0">
                                    @switch($action->action)
                                        @case('approve')
                                            <div
                                                class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                                <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                        @break

                                        @case('reject')
                                            <div
                                                class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                                <svg class="h-4 w-4 text-red-600 dark:text-red-400" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </div>
                                        @break

                                        @case('apply')
                                            <div
                                                class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                                <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                            </div>
                                        @break

                                        @case('rollback')
                                            <div
                                                class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                                <svg class="h-4 w-4 text-amber-600 dark:text-amber-400" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                            </div>
                                        @break
                                    @endswitch
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-slate-100">
                                            @switch($action->action)
                                                @case('approve')
                                                    Onaylandı
                                                @break

                                                @case('reject')
                                                    Reddedildi
                                                @break

                                                @case('apply')
                                                    Uygulandı
                                                @break

                                                @case('rollback')
                                                    Geri Alındı
                                                @break
                                            @endswitch
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $action->user?->name ?? 'Sistem' }}
                                            · {{ $action->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                    @if ($action->note)
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $action->note }}</p>
                                    @endif
                                </div>
                            </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">Henüz işlem yapılmamış</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="space-y-6">
                    {{-- Status Card --}}
                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 dark:shadow-none p-6">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Durum</h3>
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        @switch($suggestion->oneri_durumu)
                            @case('pending') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 @break
                            @case('approved') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 @break
                            @case('applied') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 @break
                            @case('rejected') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 @break
                            @case('rolled_back') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @break
                        @endswitch">
                            @switch($suggestion->oneri_durumu)
                                @case('pending')
                                    Bekleyen
                                @break

                                @case('approved')
                                    Onaylı
                                @break

                                @case('applied')
                                    Uygulanmış
                                @break

                                @case('rejected')
                                    Reddedilmiş
                                @break

                                @case('rolled_back')
                                    Geri Alınmış
                                @break
                            @endswitch
                        </span>
                    </div>

                    {{-- Priority Card --}}
                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 dark:shadow-none p-6">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Öncelik</h3>
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        @switch($suggestion->priority)
                            @case('critical') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 @break
                            @case('high') bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 @break
                            @case('medium') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 @break
                            @default bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                        @endswitch">
                            @switch($suggestion->priority)
                                @case('critical')
                                    Kritik
                                @break

                                @case('high')
                                    Yüksek
                                @break

                                @case('medium')
                                    Orta
                                @break

                                @default
                                    Düşük
                            @endswitch
                        </span>
                    </div>

                    {{-- Score Card --}}
                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 dark:shadow-none p-6">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Toplam Skor</h3>
                        <div
                            class="text-3xl font-bold
                        @if ($suggestion->total_score >= 70) text-green-600 dark:text-green-400
                        @elseif ($suggestion->total_score >= 40) text-amber-600 dark:text-amber-400
                        @else text-gray-600 dark:text-gray-400 @endif">
                            {{ $suggestion->total_score }}
                        </div>
                    </div>

                    {{-- Timestamps --}}
                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 dark:shadow-none p-6">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Tarihler</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Oluşturulma</dt>
                                <dd class="text-gray-900 dark:text-slate-100">
                                    {{ $suggestion->created_at->format('d.m.Y H:i') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Güncelleme</dt>
                                <dd class="text-gray-900 dark:text-slate-100">
                                    {{ $suggestion->updated_at->format('d.m.Y H:i') }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Note Input for Actions --}}
                    @if ($suggestion->isPending() || $suggestion->isApproved() || $suggestion->isApplied())
                        <div
                            class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 dark:shadow-none p-6">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">İşlem Notu</h3>
                            <textarea x-model="actionNote" rows="3" placeholder="İsteğe bağlı not..."
                                class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                        </div>
                    @endif

                    {{-- Conflicts --}}
                    @if ($suggestion->conflicts_json)
                        <div
                            class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700 p-6">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Çakışmalar</h3>
                            <div class="space-y-2">
                                @foreach ($suggestion->conflicts_json as $conflict)
                                    <div
                                        class="text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-lg">
                                        {{ is_string($conflict) ? $conflict : json_encode($conflict) }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Notification --}}
            <div x-show="notification" x-transition
                class="fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg text-white text-sm z-50"
                :class="notificationType === 'success' ? 'bg-green-600' : 'bg-red-600'" x-text="notification"
                @click="notification = null">
            </div>
        </div>

        <script>
            function suggestionDetail() {
                return {
                    actionNote: '',
                    actionLoading: false,
                    notification: null,
                    notificationType: 'success',

                    showNotification(message, type = 'success') {
                        this.notification = message;
                        this.notificationType = type;
                        setTimeout(() => this.notification = null, 4000);
                    },

                    async performAction(action) {
                        const labels = {
                            approve: 'onaylamak',
                            reject: 'reddetmek',
                            apply: 'uygulamak',
                            rollback: 'geri almak',
                        };

                        if (!confirm(`Bu öneriyi ${labels[action]} istiyor musunuz?`)) return;

                        this.actionLoading = true;
                        try {
                            const response = await fetch(
                                `/admin/property-hub/field-suggestions/{{ $suggestion->id }}/${action}`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        note: this.actionNote || null
                                    }),
                                });

                            const data = await response.json();
                            if (data.success) {
                                this.showNotification(data.message || 'İşlem başarılı');
                                setTimeout(() => window.location.reload(), 1000);
                            } else {
                                this.showNotification(data.message || 'Hata oluştu', 'error');
                            }
                        } catch (e) {
                            this.showNotification('Ağ hatası', 'error');
                        } finally {
                            this.actionLoading = false;
                        }
                    }
                };
            }
        </script>
    @endsection
