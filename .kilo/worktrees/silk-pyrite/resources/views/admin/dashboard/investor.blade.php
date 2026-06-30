@extends('admin.layouts.admin')

@section('title', 'Investor Dashboard — CQRS Read Model')

@push('head')
    <x-csp-script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js" />
@endpush

@section('content')
<div
    x-data="investorDashboard()"
    x-init="init()"
    class="min-h-screen bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 p-6 space-y-6"
>

    {{-- ─── HEADER ────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow border border-gray-100 dark:border-slate-800 p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                📊 Investor Dashboard
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                CQRS Read Model — Projection verisi · <span x-text="lastRefresh" class="font-mono"></span>
            </p>
        </div>

        {{-- Health Badge --}}
        <button
            @click="healthOpen = !healthOpen"
            :class="health.calisma_durumu === 'ok'
                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'"
            class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-all hover:scale-105 cursor-pointer"
        >
            <span x-text="health.calisma_durumu === 'ok' ? '✅ Sistem Sağlıklı' : '⚠️ DLQ Uyarısı'"></span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
    </div>

    {{-- ─── HEALTH DRILLDOWN ──────────────────────────────────────── --}}
    <div x-show="healthOpen" x-transition class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-slate-100 mb-4">🔍 Sistem Sağlık Detayı</h2>
        <template x-if="healthLoading">
            <div class="space-y-2">
                <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded animate-pulse w-2/3"></div>
                <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded animate-pulse w-1/2"></div>
            </div>
        </template>
        <template x-if="!healthLoading">
            <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 dark:bg-slate-800 rounded-lg p-3">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">Senkronize İlan</dt>
                    <dd class="text-2xl font-bold text-gray-900 dark:text-white" x-text="health.meta?.listings_synced ?? 0"></dd>
                </div>
                <div class="bg-gray-50 dark:bg-slate-800 rounded-lg p-3">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">DLQ Büyüklüğü</dt>
                    <dd class="text-2xl font-bold" :class="(health.meta?.dlq_boyutu ?? 0) > 0 ? 'text-red-600' : 'text-green-600'" x-text="health.meta?.dlq_boyutu ?? 0"></dd>
                </div>
                <div class="bg-gray-50 dark:bg-slate-800 rounded-lg p-3">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">İşlenen Event</dt>
                    <dd class="text-2xl font-bold text-gray-900 dark:text-white" x-text="health.meta?.islenen_event ?? 0"></dd>
                </div>
                <div class="bg-gray-50 dark:bg-slate-800 rounded-lg p-3 col-span-1">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">Açıklama</dt>
                    <dd class="text-sm text-gray-700 dark:text-gray-300 mt-1" x-text="health.meta?.aciklama ?? '—'"></dd>
                </div>
            </dl>
        </template>
    </div>

    {{-- ─── KPI KARTLARI ──────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <template x-for="(kpi, key) in kpis" :key="key">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-5 flex flex-col gap-2">

                {{-- Loading --}}
                <template x-if="kpiLoading">
                    <div>
                        <div class="h-3 bg-gray-200 dark:bg-slate-700 rounded animate-pulse mb-2 w-2/3"></div>
                        <div class="h-8 bg-gray-200 dark:bg-slate-700 rounded animate-pulse w-1/2"></div>
                        <div class="h-3 bg-gray-100 dark:bg-slate-800 rounded animate-pulse mt-2 w-full"></div>
                    </div>
                </template>

                {{-- Error --}}
                <template x-if="!kpiLoading && kpiError">
                    <div class="text-center py-2">
                        <p class="text-sm text-red-500 dark:text-red-400">⚠️ Veri alınamadı</p>
                        <button @click="loadKpis()" class="mt-1 text-xs text-blue-600 underline">Tekrar dene</button>
                    </div>
                </template>

                {{-- Empty --}}
                <template x-if="!kpiLoading && !kpiError && kpi.value == 0 && kpi.calisma_durumu === 'stale'">
                    <div class="text-center py-2">
                        <p class="text-xs text-gray-400 dark:text-gray-500">Henüz veri yok</p>
                        <p class="text-xs text-gray-300 dark:text-gray-600 mt-1">Projection rebuild çalıştırın</p>
                    </div>
                </template>

                {{-- Success --}}
                <template x-if="!kpiLoading && !kpiError">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide" x-text="kpiLabel(key)"></p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1" x-text="kpiFormat(key, kpi.value)"></p>
                        <div class="flex items-center justify-between mt-2">
                            <span
                                class="text-xs px-2 py-0.5 rounded-full font-medium"
                                :class="kpi.calisma_durumu === 'ok'
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'"
                                x-text="kpi.calisma_durumu === 'ok' ? 'Güncel' : 'Eski Veri'"
                            ></span>
                            <span class="text-xs text-gray-400 dark:text-gray-500" x-text="timeAgo(kpi.last_updated_at)"></span>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    {{-- ─── GRAFİKLER ────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Günlük Lead Trendi --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 dark:text-slate-100 mb-4">📈 7 Günlük Lead Trendi</h3>
            <template x-if="chartLoading">
                <div class="h-48 bg-gray-100 dark:bg-slate-800 rounded-lg animate-pulse"></div>
            </template>
            <template x-if="!chartLoading && leads.length === 0">
                <div class="h-48 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                    <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <p class="text-sm">Lead verisi bulunamadı</p>
                    <p class="text-xs mt-1">Projection güncellenince görünecek</p>
                </div>
            </template>
            <canvas id="leadsTrendChart" x-show="!chartLoading && leads.length > 0" class="max-h-48"></canvas>
        </div>

        {{-- İlan Dağılımı --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 dark:text-slate-100 mb-4">🏠 İlan Dağılımı</h3>
            <template x-if="kpiLoading">
                <div class="h-48 bg-gray-100 dark:bg-slate-800 rounded-lg animate-pulse"></div>
            </template>
            <canvas id="listingDistChart" x-show="!kpiLoading" class="max-h-48"></canvas>
        </div>
    </div>

    {{-- ─── AKTİVİTE AKIŞI ───────────────────────────────────────── --}}
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6">
        <h3 class="text-base font-semibold text-gray-800 dark:text-slate-100 mb-4">⚡ Aktivite Akışı</h3>

        <template x-if="activityLoading">
            <div class="space-y-3">
                <template x-for="i in 5" :key="i">
                    <div class="h-10 bg-gray-100 dark:bg-slate-800 rounded-lg animate-pulse"></div>
                </template>
            </div>
        </template>

        <template x-if="!activityLoading && activity.length === 0">
            <div class="text-center py-10 text-gray-400 dark:text-gray-500">
                <svg class="w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <p class="text-sm">Henüz aktivite yok</p>
                <p class="text-xs mt-1">Event geldiğinde burada görünecek</p>
            </div>
        </template>

        <ul x-show="!activityLoading && activity.length > 0" class="divide-y divide-gray-100 dark:divide-slate-800">
            <template x-for="item in activity" :key="item.id ?? item.occurred_at">
                <li class="py-3 flex items-center justify-between">
                    <span class="text-sm text-gray-800 dark:text-slate-200" x-text="item.event_type ?? item.description ?? '—'"></span>
                    <span class="text-xs text-gray-400 dark:text-gray-500 font-mono" x-text="timeAgo(item.occurred_at)"></span>
                </li>
            </template>
        </ul>
    </div>

    {{-- ─── TOAST SISTEMI (Merkezi) ───────────────────────────────── --}}
    <div
        x-data="toastManager()"
        @toast:show.window="add($event.detail)"
        class="fixed bottom-6 right-6 z-50 space-y-2"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-show="toast.visible"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                :class="{
                    'bg-green-600': toast.tip === 'success',
                    'bg-red-600': toast.tip === 'error',
                    'bg-amber-500': toast.tip === 'warning',
                    'bg-blue-600': toast.tip === 'info',
                }"
                class="flex items-center gap-3 text-white px-4 py-3 rounded-xl shadow-lg max-w-sm text-sm"
            >
                <span x-text="toast.mesaj"></span>
                <button @click="remove(toast.id)" class="ml-auto opacity-70 hover:opacity-100">✕</button>
            </div>
        </template>
    </div>

</div>
@endsection

@push('scripts')
<script type="module">
import ApiClient from '/build/core/api-client.js';

function investorDashboard() {
    return {
        kpis:          {},
        kpiLoading:    true,
        kpiError:      false,
        health:        { calisma_durumu: 'ok', meta: {} },
        healthLoading: true,
        healthOpen:    false,
        activity:      [],
        activityLoading: true,
        leads:         [],
        chartLoading:  true,
        lastRefresh:   '—',

        async init() {
            await Promise.all([
                this.loadKpis(),
                this.loadHealth(),
                this.loadActivity(),
                this.loadLeads(),
            ]);
        },

        async loadKpis() {
            this.kpiLoading = true;
            this.kpiError   = false;
            const { ok, data } = await ApiClient.get('/api/v1/dashboard/kpis');
            if (ok && data?.data) {
                this.kpis = data.data;
                this.lastRefresh = new Date().toLocaleTimeString('tr-TR');
                this.$nextTick(() => this.renderDistChart());
            } else {
                this.kpiError = true;
            }
            this.kpiLoading = false;
        },

        async loadHealth() {
            this.healthLoading = true;
            const { ok, data } = await ApiClient.get('/api/v1/dashboard/health');
            if (ok && data) {
                this.health = data;
            }
            this.healthLoading = false;
        },

        async loadActivity() {
            this.activityLoading = true;
            const { ok, data } = await ApiClient.get('/api/v1/dashboard/activity', { limit: 10 });
            if (ok && data?.data) this.activity = data.data;
            this.activityLoading = false;
        },

        async loadLeads() {
            this.chartLoading = true;
            const { ok, data } = await ApiClient.get('/api/v1/dashboard/leads-trend', { days: 7 });
            if (ok && data?.data) {
                this.leads = data.data;
                this.$nextTick(() => this.renderLeadsChart());
            }
            this.chartLoading = false;
        },

        renderLeadsChart() {
            const ctx = document.getElementById('leadsTrendChart');
            if (!ctx || !this.leads.length) return;
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.leads.map(l => l.tarih),
                    datasets: [{
                        label: 'Lead',
                        data: this.leads.map(l => l.lead_count ?? 0),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99,102,241,0.1)',
                        fill: true,
                        tension: 0.4,
                    }],
                },
                options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
            });
        },

        renderDistChart() {
            const ctx = document.getElementById('listingDistChart');
            if (!ctx) return;
            const aktif  = this.kpis?.aktif_ilan_sayisi?.value ?? 0;
            const toplam = this.kpis?.toplam_portfolio_degeri?.value > 0 ? 1 : 0;
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Aktif', 'Pasif'],
                    datasets: [{
                        data: [aktif, Math.max(0, toplam - aktif)],
                        backgroundColor: ['#4ade80', '#e5e7eb'],
                    }],
                },
                options: { plugins: { legend: { position: 'bottom' } }, cutout: '65%' },
            });
        },

        kpiLabel(key) {
            const labels = {
                toplam_portfolio_degeri: 'Portföy Değeri',
                aktif_ilan_sayisi:       'Aktif İlan',
                ortalama_yas:            'Ort. Yaş (gün)',
                lead_7g:                 '7 Günlük Lead',
                donusum_orani:           'Dönüşüm Oranı',
            };
            return labels[key] ?? key;
        },

        kpiFormat(key, value) {
            if (key === 'toplam_portfolio_degeri') {
                return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY', maximumFractionDigits: 0 }).format(value);
            }
            if (key === 'donusum_orani') return `%${value}`;
            return value.toLocaleString('tr-TR');
        },

        timeAgo(iso) {
            if (!iso) return '—';
            const diff = Math.floor((Date.now() - new Date(iso)) / 1000);
            if (diff < 60) return `${diff}sn`;
            if (diff < 3600) return `${Math.floor(diff/60)}dk`;
            return `${Math.floor(diff/3600)}sa`;
        },
    };
}

function toastManager() {
    return {
        toasts: [],
        add({ mesaj, tip = 'info' }) {
            const id = Date.now();
            this.toasts.push({ id, mesaj, tip, visible: true });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) {
            const t = this.toasts.find(t => t.id === id);
            if (t) t.visible = false;
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 300);
        },
    };
}

window.investorDashboard = investorDashboard;
window.toastManager      = toastManager;
</script>
@endpush
