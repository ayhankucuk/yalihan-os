@extends('admin.layouts.admin')

@section('title', 'İletişim Zekası — Müdahale Gerekenler')

@push('styles')
<style>
    .severity-p0 { background: #fee2e2; border-color: #ef4444; }
    .severity-p1 { background: #fef9c3; border-color: #eab308; }
    .severity-p2 { background: #f0fdf4; border-color: #22c55e; }
    .badge-p0 { background: #ef4444; color: white; }
    .badge-p1 { background: #eab308; color: white; }
    .badge-p2 { background: #22c55e; color: white; }
    .communication-row:hover { background: #f9fafb; }
    @media (prefers-color-scheme: dark) {
        .communication-row:hover { background: #1e293b; }
        .severity-p0 { background: rgba(239,68,68,0.15); border-color: #ef4444; }
        .severity-p1 { background: rgba(234,179,8,0.15); border-color: #eab308; }
        .severity-p2 { background: rgba(34,197,94,0.15); border-color: #22c55e; }
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">

    {{-- ── Header ──────────────────────────────────────────────────────────────── --}}
    <div class="mb-8">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                    📧 İletişim Zekası
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Gmail mesajlarından otomatik önceliklendirme — Ayhan müdahalesi gerekenler
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold badge-p0">
                    🔴 P0 <span id="p0-count" class="ml-1">{{ $p0Count }}</span>
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold badge-p1">
                    🟡 P1 <span id="p1-count" class="ml-1">{{ $p1Count }}</span>
                </span>
                <button onclick="refreshTable()" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all shadow-md">
                    🔄 Yenile
                </button>
            </div>
        </div>
    </div>

    {{-- ── Filters ──────────────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex gap-4 items-center">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Severity:</label>
        <select id="filter-severity" onchange="refreshTable()" class="px-3 py-2 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg text-sm">
            <option value="">Tümü</option>
            <option value="P0">🔴 P0</option>
            <option value="P1">🟡 P1</option>
            <option value="P2">🟢 P2</option>
        </select>
        <select id="filter-resolved" onchange="refreshTable()" class="px-3 py-2 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg text-sm">
            <option value="unresolved">Çözülmemiş</option>
            <option value="resolved">Çözülmüş</option>
        </select>
    </div>

    {{-- ── Communications Table ────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Sev.</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Platform</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Misafir</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Konu</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Özet</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Rezervasyon</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Tarih</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">İşlem</th>
                    </tr>
                </thead>
                <tbody id="communications-tbody" class="divide-y divide-gray-100 dark:divide-slate-800">
                    @forelse ($communications as $comm)
                    <tr class="communication-row border-l-4 {{ $comm->severity === 'P0' ? 'border-red-500 severity-p0' : ($comm->severity === 'P1' ? 'border-yellow-500 severity-p1' : 'border-green-500 severity-p2') }}">
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold {{ $comm->severity === 'P0' ? 'badge-p0' : ($comm->severity === 'P1' ? 'badge-p1' : 'badge-p2') }}">
                                {{ $comm->severity }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                {{ $comm->platform === 'airbnb' ? '🏠 Airbnb' : ($comm->platform === 'booking.com' ? '🏨 Booking' : ($comm->platform === 'direct' ? '📧 Direct' : '❓ Email')) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $comm->sender_name ?? 'Bilinmiyor' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $comm->sender_email }}</div>
                        </td>
                        <td class="px-4 py-3 max-w-xs">
                            <div class="text-sm text-gray-900 dark:text-white truncate" title="{{ $comm->subject }}">{{ $comm->subject ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3 max-w-sm">
                            <div class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                {{ $comm->ai_extracted_data['message_summary'] ?? mb_substr($comm->message, 0, 100) }}
                            </div>
                            @if (!empty($comm->ai_extracted_data['intent']))
                            <div class="text-xs text-gray-400 mt-1">
                                Intent: <span class="font-mono">{{ $comm->ai_extracted_data['intent'] }}</span>
                            </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($comm->reservation)
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $comm->reservation?->ilan?->basligi ?? 'Villa #' . $comm->reservation_id }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                    {{ $comm->reservation?->reservation_reference ?? $comm->reservation?->airbnb_confirmation_code ?? '' }}
                                </div>
                            @else
                                <span class="text-sm text-gray-400 dark:text-gray-500">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm text-gray-700 dark:text-gray-300">{{ $comm->created_at->format('d.m.Y') }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $comm->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if (!$comm->resolved_at)
                                <button onclick="resolveComm({{ $comm->id }})" class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded transition-colors">
                                    ✅ Çözüldü
                                </button>
                            @else
                                <span class="text-xs text-gray-400">✓ Çözüldü</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                            📭 Çözülmemiş email mesajı yok. Tüm misafir iletişimleri güncel.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($communications->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-slate-700">
            {{ $communications->links() }}
        </div>
        @endif
    </div>

    {{-- ── Info Box ─────────────────────────────────────────────────────────────── --}}
    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
        <p class="text-sm text-blue-800 dark:text-blue-300">
            <strong>ℹ️ Nasıl Çalışır:</strong>
            Gmail'den gelen mesajlar otomatik olarak okunur, villaya eşleştirilir ve önceliklendirilir.
            🔴 <strong>P0</strong> = Aynı gün müdahale gerekli (kapı sorunu, güvenlik, acil şikayet).
            🟡 <strong>P1</strong> = 24 saat içinde müdahale gerekli.
            🟢 <strong>P2</strong> = İş günü içinde halledilebilir — alarm oluşturmaz.
            Bu sistem şu anda <strong>sadece okur</strong> — misafire otomatik yanıt vermez veya rezervasyon değiştirmez.
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function refreshTable() {
        const severity = document.getElementById('filter-severity').value;
        const isResolved = document.getElementById('filter-resolved').value;
        const url = `/admin/ai/communications.json?severity=${severity}&is_resolved=${isResolved}`;
        fetch(url)
            .then(r => r.json())
            .then(data => {
                const tbody = document.getElementById('communications-tbody');
                if (!data.data || data.data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-12 text-center text-gray-500">📭 Çözülmemiş email mesajı yok.</td></tr>`;
                    return;
                }
                tbody.innerHTML = data.data.map(c => `
                    <tr class="communication-row border-l-4 ${c.severity === 'P0' ? 'border-red-500 severity-p0' : c.severity === 'P1' ? 'border-yellow-500 severity-p1' : 'border-green-500 severity-p2'}">
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold ${c.severity === 'P0' ? 'badge-p0' : c.severity === 'P1' ? 'badge-p1' : 'badge-p2'}">${c.severity}</span>
                        </td>
                        <td class="px-4 py-3 text-sm">${platformIcon(c.platform)}</td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium">${c.sender_name ?? 'Bilinmiyor'}</div>
                            <div class="text-xs text-gray-400">${c.sender_email ?? ''}</div>
                        </td>
                        <td class="px-4 py-3 max-w-xs text-sm truncate">${c.subject ?? '—'}</td>
                        <td class="px-4 py-3 max-w-sm text-sm text-gray-600 line-clamp-2">${c.ai_extracted_data?.message_summary ?? c.message.substring(0, 100)}</td>
                        <td class="px-4 py-3 text-sm">${c.ilan_basligi ? `<div class="font-medium">${c.ilan_basligi}</div><div class="text-xs text-gray-400 font-mono">${c.reservation_ref ?? ''}</div>` : '—'}</td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            <div>${new Date(c.created_at).toLocaleDateString('tr-TR')}</div>
                            <div class="text-xs text-gray-400">${new Date(c.created_at).toLocaleTimeString('tr-TR', {hour:'2-digit', minute:'2-digit'})}</div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            ${!c.resolved_at
                                ? `<button onclick="resolveComm(${c.id})" class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded">✅ Çözüldü</button>`
                                : '<span class="text-xs text-gray-400">✓ Çözüldü</span>'}
                        </td>
                    </tr>
                `).join('');
            });
    }

    function resolveComm(id) {
        fetch(`/admin/ai/communications/${id}/resolve`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }})
            .then(r => r.json())
            .then(() => { refreshTable(); updateCounts(); });
    }

    function updateCounts() {
        // P0/P1 count refresh
        fetch('/admin/ai/communications.json?severity=P0&is_resolved=unresolved')
            .then(r => r.json())
            .then(d => { document.getElementById('p0-count').textContent = d.data.length; });
        fetch('/admin/ai/communications.json?severity=P1&is_resolved=unresolved')
            .then(r => r.json())
            .then(d => { document.getElementById('p1-count').textContent = d.data.length; });
    }

    function platformIcon(platform) {
        const map = { airbnb: '🏠 Airbnb', 'booking.com': '🏨 Booking', direct: '📧 Direct', unknown: '❓ Email' };
        return map[platform] ?? '❓ Email';
    }

    // Auto-refresh every 60 seconds
    setInterval(refreshTable, 60000);
</script>
@endpush
