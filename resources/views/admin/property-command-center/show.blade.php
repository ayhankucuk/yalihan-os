{{--
    admin/property-command-center/show.blade.php
    Sprint 14 E01 — Property Command Center Detail View

    @source: docs/ERA_V/Phase_Reports/SPRINT-14-CHARTER.md
    @source: docs/ysos/PROPERTY_COMMAND_CENTER_REFERENCE.md
    @design: Mediterranean Premium — Navy #0A1628 · Gold #C9A84C · Cream #F8F6F1
--}}
@extends('admin.layouts.admin')

@section('title', 'Property Command Center — #' . $property->id . ' | Yalıhan Emlak')

@push('styles')
<style>
    /* Sprint 14 E01 — Command Center Tabs */
    .cc-tab {
        @apply relative px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800 transition-colors duration-150;
    }
    .cc-tab.active {
        @apply text-slate-900;
    }
    .cc-tab.active::after {
        content: '';
        @apply absolute bottom-0 left-0 right-0 h-0.5 bg-gold rounded-full;
    }
    .cc-tab:hover:not(.active) {
        @apply text-slate-700;
    }

    /* KPI Card */
    .kpi-card {
        @apply bg-white rounded-xl border border-cream-border p-4 hover:shadow-md transition-shadow;
    }
    .kpi-card .kpi-label {
        @apply text-xs font-medium text-slate-500 uppercase tracking-wider mb-1;
    }
    .kpi-card .kpi-value {
        @apply text-2xl font-bold text-navy;
    }
    .kpi-card .kpi-sub {
        @apply text-xs text-slate-400 mt-0.5;
    }

    /* Section Card */
    .section-card {
        @apply bg-white rounded-xl border border-cream-border overflow-hidden;
    }
    .section-card .section-header {
        @apply px-5 py-4 border-b border-cream-border flex items-center justify-between;
    }
    .section-card .section-body {
        @apply p-5;
    }

    /* Status Badge */
    .status-dot {
        @apply inline-block w-2 h-2 rounded-full;
    }
    .status-dot.confirmed { @apply bg-emerald-500; }
    .status-dot.pending   { @apply bg-amber-500; }
    .status-dot.cancelled { @apply bg-red-400; }
    .status-dot.running   { @apply bg-blue-500 animate-pulse; }

    /* Timeline */
    .timeline-item {
        @apply relative pl-8 pb-6 border-l-2 border-cream-border last:border-transparent last:pb-0;
    }
    .timeline-item::before {
        content: '';
        @apply absolute left-[-5px] top-1 w-2 h-2 rounded-full bg-gold border-2 border-white;
    }

    /* State badge */
    .state-badge {
        @apply inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold;
    }
    .state-badge.yayinda  { @apply bg-emerald-50 text-emerald-700 border border-emerald-200; }
    .state-badge.taslak   { @apply bg-slate-100 text-slate-600 border border-slate-200; }
    .state-badge.beklemede{ @apply bg-amber-50 text-amber-700 border border-amber-200; }
    .state-badge.pasif    { @apply bg-red-50 text-red-600 border border-red-100; }

    /* Channel pill */
    .channel-pill {
        @apply inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium border;
    }
    .channel-pill.airbnb        { @apply bg-pink-50 text-pink-700 border-pink-200; }
    .channel-pill.sahibinden    { @apply bg-blue-50 text-blue-700 border-blue-200; }
    .channel-pill.booking      { @apply bg-indigo-50 text-indigo-700 border-indigo-200; }
    .channel-pill.manual       { @apply bg-slate-50 text-slate-600 border-slate-200; }
</style>
@endpush

@section('content')
<div
    class="min-h-screen bg-cream"
    x-data="pccShow()"
    x-init="init()"
>
    {{-- Sticky Property Header --}}
    <div class="sticky top-0 z-30 bg-white border-b border-cream-border shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Header Row --}}
            <div class="flex items-center justify-between h-16">
                {{-- Left: Back + Title --}}
                <div class="flex items-center gap-3 min-w-0">
                    <a
                        href="{{ route('admin.property-command-center.index') }}"
                        class="flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-lg hover:bg-cream transition-colors text-slate-500 hover:text-navy"
                        title="Geri"
                    >
                        <x-icon name="geri" class="w-5 h-5" />
                    </a>
                    <div class="min-w-0">
                        <h1 class="text-base font-semibold text-navy truncate">
                            {{ $property->nitelik ?? 'İsimsiz Mülk' }}
                            <span class="font-mono text-slate-400 text-sm">#{{ $property->id }}</span>
                        </h1>
                        <p class="text-xs text-slate-500 truncate">
                            {{ $property->ilce?->ilce_adi ?? '' }}
                            @if($property->ilce?->il)
                                · {{ $property->ilce->il->il_adi }}
                            @endif
                            @if($property->alan_m2)
                                · {{ number_format((float)$property->alan_m2, 0) }} m²
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Right: Status badges + Tenant --}}
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($property->listings && $property->listings->count() > 0)
                        @foreach($property->listings->take(3) as $ilan)
                            <span class="state-badge {{ $ilan->yayin_durumu ?? 'taslak' }}">
                                {{ $ilan->yayin_durumu ? ucfirst($ilan->yayin_durumu) : 'Taslak' }}
                            </span>
                        @endforeach
                    @endif
                    <span class="text-xs text-slate-400 font-mono bg-cream px-2 py-0.5 rounded">
                        tenant #{{ $tenant_id }}
                    </span>
                </div>
            </div>

            {{-- Tab Bar --}}
            <div class="flex items-end gap-1 overflow-x-auto scrollbar-none -mb-px">
                @foreach([
                    ['id' => 'overview',    'label' => 'Genel',       'icon' => 'gonullu'],
                    ['id' => 'listings',    'label' => 'İlanlar',     'icon' => 'liste'],
                    ['id' => 'executions',  'label' => 'Executions',  'icon' => 'islem'],
                    ['id' => 'timeline',    'label' => 'Timeline',    'icon' => 'zaman'],
                    ['id' => 'commercial',  'label' => 'Ticari',     'icon' => 'dosya'],
                    ['id' => 'reservations','label' => 'Rezerv.',     'icon' => 'takvim'],
                    ['id' => 'finance',     'label' => 'Finans',      'icon' => 'para'],
                    ['id' => 'availability','label' => 'Uygunluk',    'icon' => 'masa'],
                ] as $tab)
                    <button
                        :class="{ 'cc-tab active': activeTab === '{{ $tab['id'] }}', 'cc-tab': activeTab !== '{{ $tab['id'] }}' }"
                        @click="switchTab('{{ $tab['id'] }}')"
                    >
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Loading overlay --}}
        <div x-show="loading" class="flex items-center justify-center py-16">
            <div class="flex flex-col items-center gap-3">
                <div class="w-8 h-8 border-2 border-gold border-t-transparent rounded-full animate-spin"></div>
                <p class="text-sm text-slate-500">Yükleniyor...</p>
            </div>
        </div>

        {{-- Tab Content --}}
        <div x-show="!loading" x-cloak>

            {{-- ── TAB: OVERVIEW ── --}}
            <div x-show="activeTab === 'overview'" x-transition:enter="fade-enter">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    {{-- Total Listings --}}
                    <div class="kpi-card">
                        <p class="kpi-label">Toplam İlan</p>
                        <p class="kpi-value" id="ov-listings-total">—</p>
                        <p class="kpi-sub" id="ov-listings-detail">yayında</p>
                    </div>
                    {{-- Executions --}}
                    <div class="kpi-card">
                        <p class="kpi-label">Toplam Execution</p>
                        <p class="kpi-value" id="ov-exec-total">—</p>
                        <p class="kpi-sub" id="ov-exec-rate">başarı oranı</p>
                    </div>
                    {{-- Reservations --}}
                    <div class="kpi-card">
                        <p class="kpi-label">Rezervasyonlar</p>
                        <p class="kpi-value" id="ov-resv-total">—</p>
                        <p class="kpi-sub" id="ov-resv-upcoming">yaklaşan</p>
                    </div>
                    {{-- Success Rate --}}
                    <div class="kpi-card">
                        <p class="kpi-label">BAI Başarı</p>
                        <p class="kpi-value text-gold" id="ov-bai-rate">—</p>
                        <p class="kpi-sub">tenant ortalaması</p>
                    </div>
                </div>

                {{-- Property Info + Quick Actions --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    {{-- Property Details --}}
                    <div class="section-card md:col-span-2">
                        <div class="section-header">
                            <h2 class="text-sm font-semibold text-navy">Mülk Bilgileri</h2>
                        </div>
                        <div class="section-body">
                            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                                <div>
                                    <dt class="text-xs text-slate-500">Alan</dt>
                                    <dd class="font-medium text-navy">
                                        {{ $property->alan_m2 ? number_format((float)$property->alan_m2, 0) . ' m²' : '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Oda / Banyo</dt>
                                    <dd class="font-medium text-navy">
                                        {{ ($property->oda_sayisi ?? '—') . ' / ' . ($property->banyo_sayisi ?? '—') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Bina Yaşı</dt>
                                    <dd class="font-medium text-navy">
                                        {{ $property->bina_yasi ? $property->bina_yasi . ' yıl' : '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Kat</dt>
                                    <dd class="font-medium text-navy">
                                        {{ $property->kat_sayisi ?? '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Aktiflik</dt>
                                    <dd class="font-medium text-navy">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="status-dot {{ $property->aktiflik_durumu === 'AKTIF' ? 'confirmed' : 'cancelled' }}"></span>
                                            {{ $property->aktiflik_durumu ?? '—' }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Oluşturulma</dt>
                                    <dd class="font-medium text-navy">
                                        {{ $property->created_at?->format('d M Y') ?? '—' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="section-card">
                        <div class="section-header">
                            <h2 class="text-sm font-semibold text-navy">Hızlı İşlemler</h2>
                        </div>
                        <div class="section-body flex flex-col gap-2">
                            <button
                                @click="syncAvailability()"
                                class="flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-gold-dim hover:bg-gold text-navy font-medium text-sm rounded-lg transition-colors"
                            >
                                <x-icon name="yenile" class="w-4 h-4" />
                                Senkronizasyon Başlat
                            </button>
                            <button
                                @click="switchTab('listings')"
                                class="flex items-center justify-center gap-2 w-full px-4 py-2.5 border border-cream-border hover:border-gold text-slate-600 hover:text-navy font-medium text-sm rounded-lg transition-colors"
                            >
                                <x-icon name="duzenle" class="w-4 h-4" />
                                İlanları Yönet
                            </button>
                            <button
                                @click="switchTab('timeline')"
                                class="flex items-center justify-center gap-2 w-full px-4 py-2.5 border border-cream-border hover:border-gold text-slate-600 hover:text-navy font-medium text-sm rounded-lg transition-colors"
                            >
                                <x-icon name="zaman" class="w-4 h-4" />
                                Geçmişi Görüntüle
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Upcoming Reservations Preview --}}
                <div class="section-card mb-6">
                    <div class="section-header">
                        <h2 class="text-sm font-semibold text-navy">Yaklaşan Rezervasyonlar</h2>
                        <button
                            @click="switchTab('reservations')"
                            class="text-xs text-gold hover:underline font-medium"
                        >Tümünü Gör</button>
                    </div>
                    <div class="section-body">
                        <div id="ov-reservations-list" class="space-y-3">
                            <p class="text-sm text-slate-400 text-center py-4">Yükleniyor...</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── TAB: LISTINGS ── --}}
            <div x-show="activeTab === 'listings'" x-transition:enter="fade-enter">
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="text-sm font-semibold text-navy">İlanlar</h2>
                        <span class="text-xs text-slate-400" id="listings-count">— adet</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-cream">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">İlan</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Durum</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Fiyat</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tarih</th>
                                </tr>
                            </thead>
                            <tbody id="listings-table-body" class="divide-y divide-cream-border">
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-slate-400">Yükleniyor...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ── TAB: EXECUTIONS ── --}}
            <div x-show="activeTab === 'executions'" x-transition:enter="fade-enter">
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="text-sm font-semibold text-navy">Execution Geçmişi</h2>
                        <div class="flex items-center gap-2">
                            <select
                                x-model="execFilter"
                                @change="loadExecutions()"
                                class="h-8 text-xs border border-cream-border rounded-lg px-2 text-slate-600 focus:outline-none focus:border-gold"
                            >
                                <option value="">Tümü</option>
                                <option value="COMPLETED">Tamamlanan</option>
                                <option value="FAILED">Başarısız</option>
                                <option value="RUNNING">Çalışan</option>
                            </select>
                        </div>
                    </div>
                    <div id="executions-list" class="divide-y divide-cream-border">
                        <p class="px-5 py-8 text-center text-slate-400 text-sm">Yükleniyor...</p>
                    </div>
                </div>
            </div>

            {{-- ── TAB: TIMELINE ── --}}
            <div x-show="activeTab === 'timeline'" x-transition:enter="fade-enter">
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="text-sm font-semibold text-navy">Timeline</h2>
                        <span class="text-xs text-slate-400" id="timeline-count">— kayıt</span>
                    </div>
                    <div id="timeline-list" class="section-body space-y-0">
                        <p class="text-slate-400 text-sm text-center py-4">Yükleniyor...</p>
                    </div>
                </div>
            </div>

            {{-- ── TAB: COMMERCIAL ── --}}
            <div x-show="activeTab === 'commercial'" x-transition:enter="fade-enter">
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="text-sm font-semibold text-navy">Ticari Teklifler</h2>
                    </div>
                    <div id="commercial-list" class="section-body">
                        <p class="text-slate-400 text-sm text-center py-4">Yükleniyor...</p>
                    </div>
                </div>
            </div>

            {{-- ── TAB: RESERVATIONS ── --}}
            <div x-show="activeTab === 'reservations'" x-transition:enter="fade-enter">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="kpi-card">
                        <p class="kpi-label">Toplam</p>
                        <p class="kpi-value" id="resv-total">—</p>
                    </div>
                    <div class="kpi-card">
                        <p class="kpi-label">Aktif</p>
                        <p class="kpi-value text-emerald-600" id="resv-active">—</p>
                    </div>
                    <div class="kpi-card">
                        <p class="kpi-label">Bekleyen</p>
                        <p class="kpi-value text-amber-600" id="resv-pending">—</p>
                    </div>
                    <div class="kpi-card">
                        <p class="kpi-label">İptal</p>
                        <p class="kpi-value text-red-400" id="resv-cancelled">—</p>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="text-sm font-semibold text-navy">Rezervasyonlar</h2>
                    </div>
                    <div id="reservations-table" class="overflow-x-auto">
                        <p class="px-5 py-8 text-center text-slate-400 text-sm">Yükleniyor...</p>
                    </div>
                </div>
            </div>

            {{-- ── TAB: FINANCE ── --}}
            <div x-show="activeTab === 'finance'" x-transition:enter="fade-enter">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                    <div class="kpi-card">
                        <p class="kpi-label">Toplam Gelir</p>
                        <p class="kpi-value" id="fin-total">—</p>
                    </div>
                    <div class="kpi-card">
                        <p class="kpi-label">Bekleyen Tahsilat</p>
                        <p class="kpi-value text-amber-600" id="fin-pending">—</p>
                    </div>
                    <div class="kpi-card">
                        <p class="kpi-label">İşlem Sayısı</p>
                        <p class="kpi-value" id="fin-count">—</p>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="text-sm font-semibold text-navy">Son İşlemler</h2>
                    </div>
                    <div id="finance-table" class="overflow-x-auto">
                        <p class="px-5 py-8 text-center text-slate-400 text-sm">Yükleniyor...</p>
                    </div>
                </div>
            </div>

            {{-- ── TAB: AVAILABILITY ── --}}
            <div x-show="activeTab === 'availability'" x-transition:enter="fade-enter">
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="text-sm font-semibold text-navy">Uygunluk Durumu</h2>
                        <button
                            @click="syncAvailability()"
                            class="flex items-center gap-1.5 text-xs text-gold hover:underline font-medium"
                        >
                            <x-icon name="yenile" class="w-3.5 h-3.5" />
                            Senkronize Et
                        </button>
                    </div>
                    <div class="section-body">
                        <div id="availability-content">
                            <p class="text-slate-400 text-sm text-center py-4">Yükleniyor...</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
function pccShow() {
    return {
        activeTab: 'overview',
        loading: false,
        execFilter: '',
        propertyId: {{ $property->id }},
        tenantId: {{ $tenant_id }},

        init() {
            this.loadOverview();
        },

        switchTab(tab) {
            this.activeTab = tab;

            if (tab === 'overview')   this.loadOverview();
            if (tab === 'listings')  this.loadListings();
            if (tab === 'executions') this.loadExecutions();
            if (tab === 'timeline')   this.loadTimeline();
            if (tab === 'commercial') this.loadCommercial();
            if (tab === 'reservations') this.loadReservations();
            if (tab === 'finance')    this.loadFinance();
            if (tab === 'availability') this.loadAvailability();
        },

        // ── Overview ────────────────────────────────────────────────
        async loadOverview() {
            try {
                const res = await fetch(`/admin/property-command-center/api/${this.propertyId}/summary?tenant_id=${this.tenantId}`);
                if (!res.ok) return;
                const data = await res.json();

                const ls = data.listing_summary;
                document.getElementById('ov-listings-total').textContent = ls.total;
                document.getElementById('ov-listings-detail').textContent = `${ls.yayinda} yayında · ${ls.taslak} taslak`;

                const es = data.execution_summary;
                document.getElementById('ov-exec-total').textContent = es.total_executions ?? 0;
                document.getElementById('ov-exec-rate').textContent = es.success_rate != null ? es.success_rate + '% başarı' : '—';

                document.getElementById('ov-bai-rate').textContent = data.tenant_bai
                    ? (data.tenant_bai.success_rate * 100).toFixed(0) + '%'
                    : '—';

            } catch (e) { /* silent */ }

            this.loadReservationsPreview();
        },

        async loadReservationsPreview() {
            try {
                const res = await fetch(`/admin/property-command-center/api/${this.propertyId}/reservations?tenant_id=${this.tenantId}`);
                if (!res.ok) return;
                const data = await res.json();

                document.getElementById('ov-resv-total').textContent = data.summary?.total ?? 0;

                const upcoming = data.upcoming_checkin;
                document.getElementById('ov-resv-upcoming').textContent = upcoming
                    ? `${upcoming.guest_name ?? 'Misafir'} — ${upcoming.start_date}`
                    : 'yaklaşan yok';

                const container = document.getElementById('ov-reservations-list');
                if (!data.reservations || data.reservations.length === 0) {
                    container.innerHTML = '<p class="text-sm text-slate-400 text-center py-4">Henüz rezervasyon yok.</p>';
                    return;
                }
                container.innerHTML = data.reservations.slice(0, 3).map(r => `
                    <div class="flex items-center justify-between p-3 rounded-lg border border-cream-border hover:border-gold transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="status-dot ${r.reservation_state}"></span>
                            <div>
                                <p class="text-sm font-medium text-navy">${r.guest_name ?? 'İsimsiz Misafir'}</p>
                                <p class="text-xs text-slate-500">${r.start_date} → ${r.end_date} · ${r.nights} gece</p>
                            </div>
                        </div>
                        <span class="text-xs font-medium ${r.reservation_state === 'confirmed' ? 'text-emerald-600' : 'text-slate-400'}">${r.state_label ?? r.reservation_state}</span>
                    </div>
                `).join('');
            } catch (e) { /* silent */ }
        },

        // ── Listings ─────────────────────────────────────────────────
        async loadListings() {
            try {
                const res = await fetch(`/admin/property-command-center/api/${this.propertyId}/summary?tenant_id=${this.tenantId}`);
                if (!res.ok) return;
                const data = await res.json();

                const prop = data.property;
                document.getElementById('listings-count').textContent = (data.listing_summary?.total ?? 0) + ' adet';

                // Get listings from property model passed to view
                const listings = @json($property->listings ?? collect());
                const tbody = document.getElementById('listings-table-body');

                if (!listings || listings.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">İlan bulunamadı.</td></tr>';
                    return;
                }

                tbody.innerHTML = listings.map(l => `
                    <tr class="hover:bg-cream transition-colors">
                        <td class="px-4 py-3">
                            <a href="/admin/ilanlar/${l.id}" class="text-sm font-medium text-navy hover:text-gold transition-colors">
                                ${l.baslik ?? 'İsimsiz İlan'}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="state-badge ${l.yayin_durumu ?? 'taslak'}">
                                ${l.yayin_durumu ? l.yayin_durumu.charAt(0).toUpperCase() + l.yayin_durumu.slice(1) : 'Taslak'}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-navy font-medium">
                            ${l.fiyat ? '₺' + Number(l.fiyat).toLocaleString('tr-TR') : '—'}
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            ${l.created_at ? new Date(l.created_at).toLocaleDateString('tr-TR') : '—'}
                        </td>
                    </tr>
                `).join('');
            } catch (e) {
                console.error('Listings load failed:', e);
            }
        },

        // ── Executions ───────────────────────────────────────────────
        async loadExecutions() {
            try {
                const statusParam = this.execFilter ? `&execution_status=${this.execFilter}` : '';
                const res = await fetch(`/admin/property-command-center/api/${this.propertyId}/executions?tenant_id=${this.tenantId}&limit=30${statusParam}`);
                if (!res.ok) return;
                const data = await res.json();

                const container = document.getElementById('executions-list');
                if (!data.executions || data.executions.length === 0) {
                    container.innerHTML = '<p class="px-5 py-8 text-center text-slate-400 text-sm">Execution bulunamadı.</p>';
                    return;
                }

                container.innerHTML = data.executions.map(ex => `
                    <div class="px-5 py-4 hover:bg-cream transition-colors flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3 min-w-0">
                            <span class="status-dot flex-shrink-0 mt-1 ${ex.execution_status?.toLowerCase() ?? 'pending'}"></span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-navy truncate">${ex.capability ?? 'Bilinmeyen'}</p>
                                <p class="text-xs text-slate-500">${ex.trigger_type ?? '—'} · ${ex.created_at ?? '—'}</p>
                                ${ex.error_message ? `<p class="text-xs text-red-500 mt-1">${ex.error_message}</p>` : ''}
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1 flex-shrink-0">
                            <span class="text-xs font-medium ${this.execStatusColor(ex.execution_status)}">${ex.status_label ?? ex.execution_status ?? '—'}</span>
                            ${ex.is_replay ? '<span class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded">REPLAY</span>' : ''}
                            ${ex.duration_ms ? `<span class="text-xs text-slate-400">${ex.duration_ms}ms</span>` : ''}
                        </div>
                    </div>
                `).join('');
            } catch (e) {
                console.error('Executions load failed:', e);
            }
        },

        execStatusColor(status) {
            const map = {
                'COMPLETED': 'text-emerald-600',
                'FAILED': 'text-red-600',
                'RUNNING': 'text-blue-600',
                'CANCELLED': 'text-amber-600',
                'REQUESTED': 'text-slate-500',
            };
            return map[status] ?? 'text-slate-500';
        },

        // ── Timeline ─────────────────────────────────────────────────
        async loadTimeline() {
            try {
                const res = await fetch(`/admin/property-command-center/api/${this.propertyId}/timeline?tenant_id=${this.tenantId}&limit=40`);
                if (!res.ok) return;
                const data = await res.json();

                document.getElementById('timeline-count').textContent = (data.count ?? 0) + ' kayıt';

                const container = document.getElementById('timeline-list');
                if (!data.timeline || data.timeline.length === 0) {
                    container.innerHTML = '<p class="text-slate-400 text-sm text-center py-4">Timeline boş.</p>';
                    return;
                }

                container.innerHTML = data.timeline.map(item => {
                    const icon = item.olay_tipi === 'state_transition' ? 'yenile' : 'islem';
                    const label = item.olay_tipi === 'state_transition'
                        ? `<span class="text-navy">${item.from_state ?? '—'}</span> → <span class="text-emerald-600 font-medium">${item.to_state ?? '—'}</span>`
                        : `<span class="text-navy">${item.capability ?? item.execution_status ?? '—'}</span>`;
                    return `
                        <div class="timeline-item">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-2.5">
                                    <x-icon name="${icon}" class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" />
                                    <div>
                                        <p class="text-sm text-navy">${label}</p>
                                        <p class="text-xs text-slate-500 mt-0.5">${item.created_at ?? '—'}</p>
                                    </div>
                                </div>
                                ${item.olay_tipi === 'execution' && item.error_message
                                    ? `<span class="text-xs text-red-500 flex-shrink-0">${item.error_message}</span>`
                                    : ''}
                            </div>
                        </div>
                    `;
                }).join('');
            } catch (e) {
                console.error('Timeline load failed:', e);
            }
        },

        // ── Commercial ───────────────────────────────────────────────
        async loadCommercial() {
            try {
                const res = await fetch(`/admin/property-command-center/api/${this.propertyId}/commercial-offerings?tenant_id=${this.tenantId}`);
                if (!res.ok) return;
                const data = await res.json();

                const container = document.getElementById('commercial-list');
                if (!data.offerings || data.offerings.length === 0) {
                    container.innerHTML = '<p class="text-sm text-slate-400 text-center py-4">Ticari teklif yok.</p>';
                    return;
                }

                const summary = data.summary;
                container.innerHTML = `
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div class="kpi-card">
                            <p class="kpi-label">Toplam</p><p class="kpi-value">${summary.total}</p>
                        </div>
                        <div class="kpi-card">
                            <p class="kpi-label">Aktif</p><p class="kpi-value text-emerald-600">${summary.active}</p>
                        </div>
                        <div class="kpi-card">
                            <p class="kpi-label">Taslak</p><p class="kpi-value text-slate-500">${summary.draft}</p>
                        </div>
                        <div class="kpi-card">
                            <p class="kpi-label">Fiyat Aralığı</p>
                            <p class="kpi-value text-sm">${data.price_range?.min ? '₺' + Number(data.price_range.min).toLocaleString('tr-TR') : '—'} ${data.price_range?.max ? '— ₺' + Number(data.price_range.max).toLocaleString('tr-TR') : ''}</p>
                        </div>
                    </div>
                `;
            } catch (e) {
                console.error('Commercial load failed:', e);
            }
        },

        // ── Reservations ─────────────────────────────────────────────
        async loadReservations() {
            try {
                const res = await fetch(`/admin/property-command-center/api/${this.propertyId}/reservations?tenant_id=${this.tenantId}`);
                if (!res.ok) return;
                const data = await res.json();

                const s = data.summary ?? {};
                document.getElementById('resv-total').textContent = s.total ?? 0;
                document.getElementById('resv-active').textContent = s.active ?? 0;
                document.getElementById('resv-pending').textContent = s.pending ?? 0;
                document.getElementById('resv-cancelled').textContent = s.cancelled ?? 0;

                const container = document.getElementById('reservations-table');
                if (!data.reservations || data.reservations.length === 0) {
                    container.innerHTML = '<p class="px-5 py-8 text-center text-slate-400 text-sm">Rezervasyon yok.</p>';
                    return;
                }

                container.innerHTML = `
                    <table class="w-full text-sm">
                        <thead class="bg-cream">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Misafir</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tarih</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Gece</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Durum</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tutar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cream-border">
                            ${data.reservations.map(r => `
                                <tr class="hover:bg-cream transition-colors">
                                    <td class="px-4 py-3 text-navy font-medium">${r.guest_name ?? '—'}</td>
                                    <td class="px-4 py-3 text-slate-600">${r.start_date ?? '—'} → ${r.end_date ?? '—'}</td>
                                    <td class="px-4 py-3 text-slate-600">${r.nights ?? 0}</td>
                                    <td class="px-4 py-3">
                                        <span class="state-badge ${r.reservation_state ?? 'taslak'}">${r.state_label ?? r.reservation_state ?? '—'}</span>
                                    </td>
                                    <td class="px-4 py-3 text-navy font-medium">${r.islem_tutari ? '₺' + Number(r.islem_tutari).toLocaleString('tr-TR') : '—'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            } catch (e) {
                console.error('Reservations load failed:', e);
            }
        },

        // ── Finance ──────────────────────────────────────────────────
        async loadFinance() {
            try {
                const res = await fetch(`/admin/property-command-center/api/${this.propertyId}/finance?tenant_id=${this.tenantId}`);
                if (!res.ok) return;
                const data = await res.json();

                const s = data.summary ?? {};
                document.getElementById('fin-total').textContent = s.total_revenue ? '₺' + Number(s.total_revenue).toLocaleString('tr-TR') : '—';
                document.getElementById('fin-pending').textContent = s.pending_collection ? '₺' + Number(s.pending_collection).toLocaleString('tr-TR') : '—';
                document.getElementById('fin-count').textContent = s.transaction_count ?? 0;

                const container = document.getElementById('finance-table');
                if (!data.transactions || data.transactions.length === 0) {
                    container.innerHTML = '<p class="px-5 py-8 text-center text-slate-400 text-sm">İşlem yok.</p>';
                    return;
                }

                container.innerHTML = `
                    <table class="w-full text-sm">
                        <thead class="bg-cream">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tür</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tutar</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tarih</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Durum</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cream-border">
                            ${data.transactions.map(t => `
                                <tr class="hover:bg-cream transition-colors">
                                    <td class="px-4 py-3 text-slate-700">${t.islem_turu ?? '—'}</td>
                                    <td class="px-4 py-3 font-semibold text-navy">${t.islem_tutari ? '₺' + Number(t.islem_tutari).toLocaleString('tr-TR') : '—'}</td>
                                    <td class="px-4 py-3 text-slate-500">${t.payment_date ?? '—'}</td>
                                    <td class="px-4 py-3">
                                        ${t.is_verified
                                            ? '<span class="state-badge yayinda">Doğrulandı</span>'
                                            : '<span class="state-badge beklemede">Bekliyor</span>'}
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            } catch (e) {
                console.error('Finance load failed:', e);
            }
        },

        // ── Availability ─────────────────────────────────────────────
        async loadAvailability() {
            const container = document.getElementById('availability-content');
            container.innerHTML = `
                <div class="text-center py-8">
                    <p class="text-sm text-slate-500 mb-2">Sprint 14 E02 — Uygunluk Paneli yakında eklenecek.</p>
                    <button
                        @click="syncAvailability()"
                        class="mt-2 inline-flex items-center gap-2 px-4 py-2 bg-gold-dim hover:bg-gold text-navy font-medium text-sm rounded-lg transition-colors"
                    >
                        <x-icon name="yenile" class="w-4 h-4" />
                        Şimdi Senkronize Et
                    </button>
                </div>
            `;
        },

        // ── Sync ─────────────────────────────────────────────────────
        async syncAvailability() {
            try {
                const res = await fetch(`/api/v1/property/${this.propertyId}/command/sync?tenant_id=${this.tenantId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '' },
                });
                const data = await res.json();
                alert(data.message ?? 'Senkronizasyon başlatıldı.');
            } catch (e) {
                alert('Senkronizasyon başlatılamadı.');
            }
        },
    };
}
</script>
@endpush
