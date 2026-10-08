{{-- Failed Events Table --}}
<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Başarısız Olaylar</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    {{ $failedPage['total'] }} toplam başarısız olay
                </p>
            </div>
            <a href="{{ route('admin.hermes.api.failed') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-600">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Yenile
            </a>
        </div>
    </div>

    @if(empty($failedPage['events']))
        <div class="px-6 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Başarısız olay yok — Hermes sağlıklı çalışıyor 🎉</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Olay</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">İlan / Tenant</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Zaman</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Hata</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @foreach($failedPage['events'] as $event)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/40 transition-colors">
                            <td class="px-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $event['event_name'] }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">#{{ $event['id'] }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-700 dark:text-gray-300">
                                    @if($event['ilan_id'])
                                        <span class="font-mono text-xs">İlan #{{ $event['ilan_id'] }}</span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-400 dark:text-gray-500">Tenant {{ $event['tenant_id'] ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $event['occurred_at'] }}</p>
                                @if($event['duration_ms'])
                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $event['duration_ms'] }}s</p>
                                @endif
                            </td>
                            <td class="max-w-xs px-4 py-3">
                                <p class="truncate text-sm text-red-600 dark:text-red-400" title="{{ $event['error'] }}">
                                    {{ $event['error'] ?? 'Bilinmeyen hata' }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if($event['can_replay'])
                                        <form action="{{ route('admin.hermes.replay.event', $event['id']) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50"
                                                    title="Senkron replay">
                                                Sync
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.hermes.replay.event.async', $event['id']) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50"
                                                    title="Kuyruğa gönder — non-blocking">
                                                Async
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">Replay yok</span>
                                    @endif
                                    <button type="button"
                                            onclick="showEventDetail({{ $event['id'] }})"
                                            class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-200 dark:bg-slate-700 dark:text-gray-400 dark:hover:bg-slate-600"
                                            title="Detay">
                                        Detay
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Event Detail Modal --}}
<div id="event-detail-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="closeEventDetail()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl rounded-xl bg-white shadow-xl dark:bg-slate-800">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-700">
                    <h3 id="modal-title" class="text-base font-semibold text-gray-900 dark:text-white">Olay Detayı</h3>
                    <button onclick="closeEventDetail()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="event-detail-content" class="px-6 py-4">
                    {{-- Filled by JS --}}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showEventDetail(eventId) {
        const modal = document.getElementById('event-detail-modal');
        const content = document.getElementById('event-detail-content');
        modal.classList.remove('hidden');
        content.innerHTML = '<p class="text-sm text-gray-500 py-8 text-center">Yükleniyor...</p>';

        fetch(`/admin/hermes/event/${eventId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.log) {
                    content.innerHTML = '<p class="text-red-500 py-8 text-center">Olay bulunamadı.</p>';
                    return;
                }
                const e = data.log;
                const execs = data.executions || [];
                content.innerHTML = `
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">Olay Adı</dt><dd class="mt-0.5 font-medium text-gray-900 dark:text-white">${e.event_name}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">Log ID</dt><dd class="mt-0.5 font-mono text-gray-700 dark:text-gray-300">#${e.id}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">Tenant</dt><dd class="mt-0.5 text-gray-700 dark:text-gray-300">${e.tenant_id ?? '—'}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">İlan</dt><dd class="mt-0.5 text-gray-700 dark:text-gray-300">${e.ilan_id ? '#'+e.ilan_id : '—'}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">Zaman</dt><dd class="mt-0.5 text-gray-700 dark:text-gray-300">${e.occurred_at ?? '—'}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">Süre</dt><dd class="mt-0.5 text-gray-700 dark:text-gray-300">${e.duration_ms ? e.duration_ms+'s' : '—'}</dd></div>
                        <div class="col-span-2"><dt class="text-xs text-gray-500 dark:text-gray-400">Hata</dt><dd class="mt-1 rounded-md bg-red-50 p-2 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">${e.error || 'Bilinmeyen'}</dd></div>
                        <div class="col-span-2"><dt class="text-xs text-gray-500 dark:text-gray-400">Payload</dt><dd class="mt-1 rounded-md bg-gray-50 p-2 font-mono text-xs text-gray-700 dark:bg-slate-900 dark:text-gray-300"><pre class="whitespace-pre-wrap">${JSON.stringify(e, null, 2)}</pre></dd></div>
                    </dl>
                    ${execs.length > 0 ? `
                    <h4 class="mt-4 mb-2 text-sm font-semibold text-gray-900 dark:text-white">Handler Çalışmaları</h4>
                    <table class="w-full text-xs">
                        <thead><tr class="text-left text-gray-500 dark:text-gray-400"><th class="pb-1">Agent</th><th class="pb-1">Durum</th><th class="pb-1">Süre</th><th class="pb-1">Hata</th></tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            ${execs.map(ex => `<tr>
                                <td class="py-1 font-mono">${ex.agent}</td>
                                <td class="py-1"><span class="rounded px-1.5 py-0.5 text-xs font-medium ${statusClass(ex.status)}">${ex.status}</span></td>
                                <td class="py-1">${ex.duration_ms ? ex.duration_ms+'s' : '—'}</td>
                                <td class="py-1 text-red-600 dark:text-red-400">${ex.error || '—'}</td>
                            </tr>`).join('')}
                        </tbody>
                    </table>` : '<p class="mt-3 text-xs text-gray-400 dark:text-gray-500">Handler çalışma kaydı yok.</p>'}
                `;
            })
            .catch(() => {
                content.innerHTML = '<p class="text-red-500 py-8 text-center">Yüklenirken hata oluştu.</p>';
            });
    }

    function closeEventDetail() {
        document.getElementById('event-detail-modal').classList.add('hidden');
    }

    function statusClass(status) {
        const map = {
            'completed': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            'failed': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            'pending': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
            'running': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'skipped': 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-400',
        };
        return map[status] || 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-400';
    }
</script>
