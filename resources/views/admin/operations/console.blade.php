{{-- admin/operations/console.blade.php — Sprint 15 Runtime Operations Console --}}
@extends('admin.layouts.admin')

@section('title', 'Runtime Operations Console | Yalıhan Emlak')

@php
    $primaryNavy = 'bg-[#0A1628]';
    $goldAccent = 'text-[#C9A84C]';
    $creamText = 'text-[#F5F0E8]';
    $surfaceCard = 'bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700';
    $surfaceHover = 'hover:bg-slate-50 dark:hover:bg-slate-800';
@endphp

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Runtime Operations Console
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Tüm capability execution'larını izle, kurtar ve ölç
            </p>
        </div>
        <div class="flex items-center gap-3">
            <select id="tenantSelector" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                <option value="1">Tenant 1 — Yalıhan</option>
            </select>
            <button onclick="refreshAll()"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Yenile
            </button>
        </div>
    </div>

    {{-- BAI Summary Banner --}}
    <div class="mb-6 rounded-xl {{ $primaryNavy }} p-5">
        <div class="mb-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#C9A84C]">Business Automation Index</span>
                <span class="rounded bg-[#C9A84C]/20 px-2 py-0.5 text-xs font-medium text-[#C9A84C]">BAI</span>
            </div>
            <span id="baiUpdatedAt" class="text-xs text-slate-400">—</span>
        </div>
        <div class="grid grid-cols-2 gap-6 md:grid-cols-4">
            <div>
                <p class="text-3xl font-bold text-white" id="baiTotalExec">—</p>
                <p class="mt-1 text-xs text-slate-400">Toplam Execution</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-emerald-400" id="baiSuccessRate">—%</p>
                <p class="mt-1 text-xs text-slate-400">Başarı Oranı</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-amber-400" id="baiRecoveryQueue">—</p>
                <p class="mt-1 text-xs text-slate-400">Recovery Kuyruğu</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-red-400" id="baiFailedCount">—</p>
                <p class="mt-1 text-xs text-slate-400">Başarısız</p>
            </div>
        </div>
    </div>

    {{-- Metric Cards Row --}}
    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/40">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="cardActive">—</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Aktif Execution</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-emerald-100 p-2 dark:bg-emerald-900/40">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="cardSuccess">—</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Tamamlanan</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-red-100 p-2 dark:bg-red-900/40">
                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="cardFailed">—</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Başarısız</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-amber-100 p-2 dark:bg-amber-900/40">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="cardRetry">—</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Retry Bekliyor</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mb-6 border-b border-gray-200 dark:border-slate-700">
        <nav class="-mb-px flex gap-6" id="tabNav">
            <button onclick="switchTab('executions')" id="tabExecutions"
                class="border-b-2 border-gold-500 pb-3 text-sm font-medium text-gold-600 dark:text-gold-400">
                Executions
            </button>
            <button onclick="switchTab('recovery')" id="tabRecovery"
                class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">
                Recovery Kuyruğu
            </button>
            <button onclick="switchTab('capability')" id="tabCapability"
                class="border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">
                Capability Health
            </button>
        </nav>
    </div>

    {{-- Tab: Executions --}}
    <div id="panelExecutions">
        <div class="mb-4 flex items-center gap-3">
            <select id="execStatusFilter" onchange="loadExecutions()"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                <option value="">Tüm Durumlar</option>
                <option value="REQUESTED">Bekliyor</option>
                <option value="RUNNING">Çalışıyor</option>
                <option value="COMPLETED">Tamamlandı</option>
                <option value="FAILED">Başarısız</option>
                <option value="CANCELLED">İptal</option>
            </select>
            <select id="execCapFilter" onchange="loadExecutions()"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                <option value="">Tüm Capability</option>
                <option value="publish">Publish</option>
                <option value="archive">Archive</option>
                <option value="restore">Restore</option>
            </select>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-slate-700 dark:bg-slate-900">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">UUID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Capability</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Durum</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Hata Sınıfı</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Süre</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Tarih</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">İşlem</th>
                    </tr>
                </thead>
                <tbody id="executionsTableBody" class="divide-y divide-gray-200 dark:divide-slate-700">
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            Yükleniyor...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tab: Recovery Queue --}}
    <div id="panelRecovery" class="hidden">
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-slate-700 dark:bg-slate-900">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">UUID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Capability</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Hata Sınıfı</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Retry</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Sonraki Retry</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Hata Mesajı</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Kurtar</th>
                    </tr>
                </thead>
                <tbody id="recoveryTableBody" class="divide-y divide-gray-200 dark:divide-slate-700">
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            Yükleniyor...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tab: Capability Health --}}
    <div id="panelCapability" class="hidden">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3" id="capabilityGrid">
            <div class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Yükleniyor...</div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    const tenantId = {{ $tenantId }};
    const API_BASE = '/admin/operations/api';
    let currentTab = 'executions';

    document.addEventListener('DOMContentLoaded', () => {
        refreshAll();
    });

    function refreshAll() {
        loadOverview();
        loadExecutions();
    }

    async function loadOverview() {
        try {
            const res = await fetch(`${API_BASE}/overview?tenant_id=${tenantId}`);
            const data = await res.json();

            // BAI Summary
            const m = data.metrics;
            document.getElementById('baiTotalExec').textContent = m.total_executions ?? 0;
            document.getElementById('baiSuccessRate').textContent =
                Math.round((m.success_rate ?? 0) * 100) + '%';
            document.getElementById('baiRecoveryQueue').textContent = data.summary.recovery_queue ?? 0;
            document.getElementById('baiFailedCount').textContent = data.summary.failed_count ?? 0;
            document.getElementById('baiUpdatedAt').textContent = data.timestamp ?? '—';

            // Cards
            document.getElementById('cardActive').textContent = data.active_executions?.length ?? 0;
            document.getElementById('cardSuccess').textContent =
                Math.round((m.success_rate ?? 0) * (m.total_executions ?? 0));
            document.getElementById('cardFailed').textContent = data.summary.failed_count ?? 0;
            document.getElementById('cardRetry').textContent = data.summary.recovery_queue ?? 0;
        } catch (e) {
            console.error('Overview load failed', e);
        }
    }

    async function loadExecutions() {
        const status = document.getElementById('execStatusFilter').value;
        const capability = document.getElementById('execCapFilter').value;
        const params = new URLSearchParams({ tenant_id: tenantId, limit: 50 });
        if (status) params.set('status', status);
        if (capability) params.set('capability', capability);

        try {
            const res = await fetch(`${API_BASE}/executions?${params}`);
            const data = await res.json();
            renderExecutions(data.executions ?? []);
        } catch (e) {
            console.error('Executions load failed', e);
        }
    }

    function renderExecutions(execs) {
        const tbody = document.getElementById('executionsTableBody');
        if (!execs.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Kayıt bulunamadı</td></tr>';
            return;
        }
        tbody.innerHTML = execs.map(e => `
            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800">
                <td class="whitespace-nowrap px-4 py-3">
                    <code class="text-xs text-gray-600 dark:text-gray-400">${e.uuid.slice(0, 8)}…</code>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-white">${e.capability}</td>
                <td class="whitespace-nowrap px-4 py-3">${statusBadge(e)}</td>
                <td class="whitespace-nowrap px-4 py-3 text-sm">${classificationBadge(e.failure_classification)}</td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">${e.duration_ms ? e.duration_ms + 'ms' : '—'}</td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">${e.created_at ? new Date(e.created_at).toLocaleString('tr-TR') : '—'}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right">
                    ${e.execution_status === 'FAILED' ? `<button onclick="recoverExecution('${e.uuid}')" class="rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700 hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-400">Kurtar</button>` : ''}
                </td>
            </tr>
        `).join('');
    }

    async function loadRecoveryQueue() {
        try {
            const res = await fetch(`${API_BASE}/recovery-queue?tenant_id=${tenantId}&limit=50`);
            const data = await res.json();
            renderRecoveryQueue(data.queue ?? []);
        } catch (e) {
            console.error('Recovery queue load failed', e);
        }
    }

    function renderRecoveryQueue(queue) {
        const tbody = document.getElementById('recoveryTableBody');
        if (!queue.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Recovery kuyruğu boş — tüm başarısız execution\'lar ya kurtarıldı ya da kalıcı hata.</td></tr>';
            return;
        }
        tbody.innerHTML = queue.map(e => `
            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800">
                <td class="whitespace-nowrap px-4 py-3"><code class="text-xs text-gray-600 dark:text-gray-400">${e.uuid.slice(0, 8)}…</code></td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-white">${e.capability}</td>
                <td class="whitespace-nowrap px-4 py-3 text-sm">${classificationBadge(e.failure_classification)}</td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">${e.retry_count}/${e.max_retries}</td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">${e.next_retry_at ? new Date(e.next_retry_at).toLocaleString('tr-TR') : 'Hemen'}</td>
                <td class="max-w-xs truncate px-4 py-3 text-sm text-gray-500 dark:text-gray-400">${e.error_message ?? '—'}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right">
                    <button onclick="recoverExecution('${e.uuid}')"
                        class="rounded bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-400">
                        Kurtar
                    </button>
                </td>
            </tr>
        `).join('');
    }

    async function loadCapabilityMetrics() {
        try {
            const res = await fetch(`${API_BASE}/metrics/capability?tenant_id=${tenantId}`);
            const data = await res.json();
            renderCapabilityMetrics(data.capabilities ?? {});
        } catch (e) {
            console.error('Capability metrics load failed', e);
        }
    }

    function renderCapabilityMetrics(capabilities) {
        const grid = document.getElementById('capabilityGrid');
        const entries = Object.entries(capabilities);

        if (!entries.length) {
            grid.innerHTML = '<div class="col-span-3 px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Henüz capability verisi yok.</div>';
            return;
        }

        grid.innerHTML = entries.map(([cap, m]) => {
            const sr = Math.round((m.success_rate ?? 0) * 100);
            const srColor = sr >= 80 ? 'text-emerald-500' : sr >= 50 ? 'text-amber-500' : 'text-red-500';
            return `
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white uppercase">${cap}</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Başarı Oranı</span>
                            <span class="font-medium ${srColor}">${sr}%</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Toplam</span>
                            <span class="font-medium text-gray-900 dark:text-white">${m.count}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Ortalama Süre</span>
                            <span class="font-medium text-gray-900 dark:text-white">${m.avg_duration_ms ? m.avg_duration_ms + 'ms' : '—'}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Replay Oranı</span>
                            <span class="font-medium text-amber-500">${Math.round((m.replay_rate ?? 0) * 100)}%</span>
                        </div>
                    </div>
                    <div class="mt-3 h-1.5 w-full rounded-full bg-gray-100 dark:bg-slate-700">
                        <div class="h-1.5 rounded-full ${sr >= 80 ? 'bg-emerald-500' : sr >= 50 ? 'bg-amber-500' : 'bg-red-500'}" style="width: ${sr}%"></div>
                    </div>
                </div>
            `;
        }).join('');
    }

    async function recoverExecution(uuid) {
        if (!confirm('Bu başarısız execution için recovery başlatılsın mı?')) return;
        try {
            const res = await fetch(`${API_BASE}/executions/${uuid}/recover`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '' },
                body: JSON.stringify({ reason: 'Operatör manuel müdahalesi' }),
            });
            const data = await res.json();
            if (res.ok) {
                alert('Recovery başarıyla başlatıldı: ' + data.recovery?.uuid);
                refreshAll();
            } else {
                alert('Recovery başlatılamadı: ' + (data.error ?? 'Bilinmeyen hata'));
            }
        } catch (e) {
            alert('Recovery başlatılamadı: ' + e.message);
        }
    }

    function switchTab(tab) {
        currentTab = tab;
        ['executions', 'recovery', 'capability'].forEach(t => {
            document.getElementById(`panel${t.charAt(0).toUpperCase() + t.slice(1)}`)?.classList.add('hidden');
            document.getElementById(`tab${t.charAt(0).toUpperCase() + t.slice(1)}`)?.classList.replace('border-gold-500', 'border-transparent');
            document.getElementById(`tab${t.charAt(0).toUpperCase() + t.slice(1)}`)?.classList.replace('text-gold-600', 'text-gray-500');
        });
        document.getElementById(`panel${tab.charAt(0).toUpperCase() + tab.slice(1)}`)?.classList.remove('hidden');
        document.getElementById(`tab${tab.charAt(0).toUpperCase() + tab.slice(1)}`)?.classList.replace('border-transparent', 'border-gold-500');
        document.getElementById(`tab${tab.charAt(0).toUpperCase() + tab.slice(1)}`)?.classList.replace('text-gray-500', 'text-gold-600');

        if (tab === 'recovery') loadRecoveryQueue();
        if (tab === 'capability') loadCapabilityMetrics();
    }

    function statusBadge(e) {
        const map = {
            'REQUESTED': ['bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', 'Bekliyor'],
            'RUNNING': ['bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400', 'Çalışıyor'],
            'COMPLETED': ['bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400', 'Tamamlandı'],
            'FAILED': ['bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400', 'Başarısız'],
            'CANCELLED': ['bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400', 'İptal'],
        };
        const [cls, label] = map[e.execution_status] ?? ['bg-gray-100 text-gray-700', e.execution_status];
        return `<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${cls}">${label}</span>`;
    }

    function classificationBadge(cls) {
        if (!cls) return '<span class="text-gray-400 text-xs">—</span>';
        const map = {
            'TRANSIENT': ['bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400', 'TRANSIENT'],
            'PERMANENT': ['bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400', 'PERMANENT'],
            'CONFIG': ['bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400', 'CONFIG'],
            'UNKNOWN': ['bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400', 'UNKNOWN'],
        };
        const [styleCls, label] = map[cls] ?? ['bg-gray-100 text-gray-600', cls];
        return `<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${styleCls}">${label}</span>`;
    }
</script>
@endpush
