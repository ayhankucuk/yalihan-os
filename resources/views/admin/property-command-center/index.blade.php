{{--
    admin/property-command-center/index.blade.php
    Sprint 14 E01 — Property Command Center Index View

    @source: docs/ERA_V/Phase_Reports/SPRINT-14-CHARTER.md
    @design: Mediterranean Premium — Navy #0A1628 · Gold #C9A84C · Cream #F8F6F1
--}}
@extends('admin.layouts.admin')

@section('title', 'Property Command Center | Yalıhan Emlak')

@push('styles')
<style>
    .pcc-kpi-value { @apply text-3xl font-bold text-navy; }
    .pcc-section-card { @apply bg-white rounded-xl border border-cream-border overflow-hidden; }
    .pcc-section-header { @apply px-5 py-4 border-b border-cream-border flex items-center justify-between; }
    .pcc-btn-primary {
        @apply inline-flex items-center justify-center gap-2 px-4 py-2 bg-navy hover:bg-navy-mid text-white text-sm font-medium rounded-lg transition-colors;
    }
    .pcc-btn-secondary {
        @apply inline-flex items-center justify-center gap-2 px-4 py-2 border border-cream-border hover:border-gold text-slate-600 hover:text-navy text-sm font-medium rounded-lg transition-colors;
    }
    .pcc-badge {
        @apply inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold;
    }
    .pcc-badge-dot::before {
        content: '';
        @apply inline-block w-1.5 h-1.5 rounded-full mr-1;
    }
    .pcc-badge-success { @apply bg-emerald-50 text-emerald-700; }
    .pcc-badge-success.pcc-badge-dot::before { @apply bg-emerald-500; }
    .pcc-badge-neutral { @apply bg-slate-100 text-slate-600; }
    .pcc-badge-neutral.pcc-badge-dot::before { @apply bg-slate-400; }
    .pcc-badge-danger { @apply bg-red-50 text-red-600; }
    .pcc-badge-danger.pcc-badge-dot::before { @apply bg-red-500; }
</style>
@endpush

@section('content')
<div
    class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"
    x-data="pccIndex()"
    x-init="init()"
>
    {{-- Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-navy">
                Property Command Center
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Tüm mülklerin durumunu tek ekrandan izle ve yönet
            </p>
        </div>
        <div class="flex items-center gap-3">
            <select
                id="tenantSelector"
                class="h-10 rounded-lg border border-cream-border bg-white px-3 text-sm text-navy focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold"
            >
                <option value="1">Tenant 1 — Yalıhan</option>
            </select>

            <button
                onclick="window.pccIndex && window.pccIndex.refresh()"
                class="pcc-btn-secondary"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Yenile
            </button>
        </div>
    </div>

    {{-- BAI Summary Banner --}}
    <div class="mb-6 rounded-xl bg-navy p-5">
        <div class="mb-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-gold">
                    Business Automation Index
                </span>
                <span class="rounded bg-gold-dim px-2 py-0.5 text-xs font-medium text-gold">BAI</span>
            </div>
            <span id="baiUpdatedAt" class="text-xs text-slate-400">—</span>
        </div>
        <div class="grid grid-cols-2 gap-6 md:grid-cols-4">
            <div>
                <p class="text-3xl font-bold text-white" id="baiTotalProps">—</p>
                <p class="mt-1 text-xs text-slate-400">Toplam Mülk</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-emerald-400" id="baiSuccessRate">—</p>
                <p class="mt-1 text-xs text-slate-400">Tenant Başarı Oranı</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-amber-400" id="baiRecoveryQueue">—</p>
                <p class="mt-1 text-xs text-slate-400">Recovery Kuyruğu</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-red-400" id="baiFailedExec">—</p>
                <p class="mt-1 text-xs text-slate-400">Başarısız Execution</p>
            </div>
        </div>
    </div>

    {{-- Property List Card --}}
    <div class="pcc-section-card">
        <div class="pcc-section-header">
            <h2 class="text-lg font-semibold text-navy">Mülkler</h2>
            <div class="flex items-center gap-2">
                <input
                    type="text"
                    id="searchProperty"
                    placeholder="Mülk ara..."
                    class="h-8 w-48 rounded-lg border border-cream-border bg-white px-3 text-sm text-navy focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold"
                    @input="filterProperties($event.target.value)"
                />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-cream">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Mülk</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Durum</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">İlanlar</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Son Execution</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">BAI</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">İşlem</th>
                    </tr>
                </thead>
                <tbody id="propertyTableBody" class="divide-y divide-cream-border bg-white">
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Yükleniyor...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.pccIndex = null;

function pccIndex() {
    return {
        tenantId: 1,
        properties: [],
        allProperties: [],
        baiMetrics: {},

        init() {
            window.pccIndex = this;
            this.loadOverview();
            this.loadProperties();
        },

        async refresh() {
            await this.loadOverview();
            await this.loadProperties();
        },

        async loadOverview() {
            try {
                const res = await fetch(`/admin/operations/api/overview?tenant_id=${this.tenantId}`);
                if (!res.ok) return;
                const data = await res.json();

                document.getElementById('baiTotalProps').textContent = data.summary?.total_executions ?? '—';
                document.getElementById('baiSuccessRate').textContent = data.summary?.success_rate
                    ? (data.summary.success_rate * 100).toFixed(1) + '%'
                    : '—';
                document.getElementById('baiRecoveryQueue').textContent = data.summary?.recovery_queue ?? '—';
                document.getElementById('baiFailedExec').textContent = data.summary?.failed_count ?? '—';
                document.getElementById('baiUpdatedAt').textContent = new Date().toLocaleTimeString('tr-TR');

                this.baiMetrics = data.metrics || {};
            } catch (e) { /* silent */ }
        },

        async loadProperties() {
            try {
                const res = await fetch(`/admin/property-command-center/api/properties-list?tenant_id=${this.tenantId}`);
                if (!res.ok) { this.renderEmpty(); return; }
                const data = await res.json();
                this.allProperties = data.properties || [];
                this.properties = this.allProperties;
                this.renderTable();
            } catch (e) { this.renderEmpty(); }
        },

        renderEmpty() {
            document.getElementById('propertyTableBody').innerHTML = `
                <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Mülk bulunamadı.</td></tr>`;
        },

        filterProperties(query) {
            const q = query.toLowerCase();
            this.properties = this.allProperties.filter(p =>
                (p.id + '').includes(q) ||
                (p.nitelik || '').toLowerCase().includes(q) ||
                (p.il_id + '').includes(q)
            );
            this.renderTable();
        },

        renderTable() {
            const tbody = document.getElementById('propertyTableBody');
            if (!this.properties.length) { this.renderEmpty(); return; }

            tbody.innerHTML = this.properties.map(p => `
                <tr class="border-b border-cream-border last:border-0 hover:bg-cream transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-navy">
                                <svg class="w-5 h-5 text-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                </svg>
                            </div>
                            <div>
                                <a href="/admin/property-command-center/${p.id}" class="font-medium text-navy hover:text-gold transition-colors">
                                    #${p.id} — ${p.nitelik ?? 'İsimsiz Mülk'}
                                </a>
                                <p class="text-xs text-slate-500">
                                    ${p.il_id ? 'İl: ' + p.il_id : ''} ${p.alan_m2 ? '· ' + p.alan_m2 + ' m²' : ''}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        ${this.statusBadge(p.aktiflik_durumu)}
                    </td>
                    <td class="px-4 py-3">
                        ${this.listingsBadge(p.listings)}
                    </td>
                    <td class="px-4 py-3">
                        ${p.last_execution
                            ? `<span class="text-xs font-medium ${this.execColor(p.last_execution.execution_status)}">${p.last_execution.status_label ?? p.last_execution.execution_status}</span>`
                            : '<span class="text-xs text-slate-400">—</span>'
                        }
                    </td>
                    <td class="px-4 py-3">
                        ${p.bai_rate !== null
                            ? `<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${p.bai_rate >= 0.8 ? 'bg-emerald-100 text-emerald-700' : p.bai_rate >= 0.5 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'}">${(p.bai_rate * 100).toFixed(0)}%</span>`
                            : '<span class="text-xs text-slate-400">—</span>'
                        }
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="/admin/property-command-center/${p.id}" class="pcc-btn-primary" style="padding: 0.4rem 0.75rem; font-size: 0.8125rem;">
                            Detay
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    </td>
                </tr>
            `).join('');
        },

        statusBadge(status) {
            const variants = {
                'DRAFT':  { cls: 'pcc-badge-neutral', label: 'Taslak' },
                'ACTIVE':  { cls: 'pcc-badge-success pcc-badge-dot', label: 'Aktif' },
                'AKTIF':  { cls: 'pcc-badge-success pcc-badge-dot', label: 'Aktif' },
                'PASIF':  { cls: 'pcc-badge-danger', label: 'Pasif' },
            };
            const cfg = variants[status] || { cls: 'pcc-badge-neutral', label: status || '—' };
            return `<span class="pcc-badge ${cfg.cls}">${cfg.label}</span>`;
        },

        listingsBadge(listings) {
            if (!listings || listings.length === 0) {
                return '<span class="text-xs text-slate-400">—</span>';
            }
            const yayinda = listings.filter(l => ['yayinda', 'aktif'].includes(l.yayin_durumu)).length;
            const taslak = listings.filter(l => l.yayin_durumu === 'taslak').length;

            return `
                <div class="flex items-center gap-2 text-xs">
                    <span class="pcc-badge pcc-badge-success pcc-badge-dot">${yayinda} yayında</span>
                    ${taslak > 0 ? `<span class="pcc-badge pcc-badge-neutral">${taslak} taslak</span>` : ''}
                </div>`;
        },

        execColor(status) {
            const colors = {
                'COMPLETED': 'text-emerald-600',
                'FAILED': 'text-red-600',
                'RUNNING': 'text-blue-600',
                'CANCELLED': 'text-amber-600',
                'REQUESTED': 'text-slate-500',
            };
            return colors[status] ?? 'text-slate-500';
        },
    };
}
</script>
@endpush
