@extends('admin.layouts.admin')

@section('title', 'CRM Dashboard')

@section('content')
    <div class="p-6">
        <!-- Page Header -->
        <div class=" mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">CRM Dashboard</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Müşteri ilişkileri yönetimi ve analitikler</p>
                </div>
            </div>
        </div>

        <!-- Cortex AI Fırsatları -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- En Yüksek ROI'li Fırsatlar -->
            <div
                class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div
                    class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between bg-gradient-to-r from-blue-50 to-transparent dark:from-blue-900/20 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h5 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">En Yüksek ROI'li Fırsatlar</h5>
                    </div>
                    <span
                        class="px-2 py-1 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-[10px] font-bold uppercase tracking-wider rounded">Cortex
                        AI</span>
                </div>
                <div class="p-6">
                    @if ($topROIOpportunities->isEmpty())
                        <div class="flex flex-col items-center justify-center py-8 text-gray-500 dark:text-gray-400">
                            <i class="fas fa-search-dollar text-4xl mb-3 opacity-20"></i>
                            <p class="text-sm">Henüz yüksek ROI'li fırsat yakalanmadı.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach ($topROIOpportunities as $opp)
                                <div
                                    class="group p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-slate-800 hover:border-blue-300 dark:hover:border-blue-700 transition-all duration-200 dark:bg-slate-900">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <h6
                                                class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-1 dark:text-slate-100">
                                                {{ $opp->ilan->baslik ?? 'İlan Başlığı Yok' }}
                                            </h6>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                <i class="fas fa-user mr-1"></i>
                                                {{ $opp->lead->ad_soyad ?? 'Bilinmeyen Müşteri' }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-black text-blue-600 dark:text-blue-400">
                                                %{{ round($opp->firsat_skoru, 1) }}
                                            </div>
                                            <div class="text-[10px] font-bold text-gray-400 uppercase">Eşleşme</div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 mb-3">
                                        <div
                                            class="bg-white dark:bg-slate-900 p-2 rounded-lg border border-gray-100 dark:border-slate-800">
                                            <div class="text-[10px] text-gray-400 uppercase font-bold">ROI Skoru</div>
                                            <div class="text-sm font-bold text-green-600 dark:text-green-400">
                                                %{{ $opp->skor_detayi['roi'] ?? '—' }}
                                            </div>
                                        </div>
                                        <div
                                            class="bg-white dark:bg-slate-900 p-2 rounded-lg border border-gray-100 dark:border-slate-800">
                                            <div class="text-[10px] text-gray-400 uppercase font-bold">Amortisman</div>
                                            <div class="text-sm font-bold text-orange-600 dark:text-orange-400">
                                                {{ $opp->ilan->amortisman_suresi_yil ?? '—' }} Yıl
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400 italic">
                                            "{{ Str::limit($opp->firsat_nedeni, 40) }}"
                                        </span>
                                        <a href="#"
                                            class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                            Detay <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Yüksek Riskli Müşteriler (Churn) -->
            <div
                class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div
                    class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between bg-gradient-to-r from-red-50 to-transparent dark:from-red-900/20 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-red-500 flex items-center justify-center text-white shadow-lg shadow-red-500/30">
                            <i class="fas fa-user-slash"></i>
                        </div>
                        <h5 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">Yüksek Riskli Müşteriler</h5>
                    </div>
                    <span
                        class="px-2 py-1 bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 text-[10px] font-bold uppercase tracking-wider rounded">Churn
                        Prediction</span>
                </div>
                <div class="px-6 py-4">
                    <div id="highRiskCustomersWidget" x-data="highRiskCustomersWidget()" x-init="load()" class="min-h-[80px]">
                        <template x-if="loading">
                            <div class="flex items-center justify-center py-8 text-gray-600 dark:text-slate-200">
                                <i class="fas fa-spinner animate-spin mr-2"></i>
                                <span>Yükleniyor...</span>
                            </div>
                        </template>
                        <template x-if="error">
                            <div class="text-red-600 dark:text-red-400 text-sm p-4 bg-red-50 dark:bg-red-900/20 rounded-lg"
                                x-text="error"></div>
                        </template>
                        <div x-show="!loading && customers.length === 0"
                            class="text-sm text-gray-500 dark:text-gray-400 py-8 text-center">Veri bulunamadı</div>
                        <div x-show="!loading && customers.length > 0" class="space-y-3">
                            <template x-for="c in customers" :key="c.id">
                                <div
                                    class="p-3 bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 hover:border-red-300 dark:hover:border-red-700 transition-all dark:border-slate-700">
                                    <div class="flex items-center justify-between mb-2">
                                        <a :href="'/admin/crm/customers/' + c.id"
                                            class="text-sm font-semibold text-gray-900 dark:text-white hover:underline dark:text-slate-100">
                                            <span x-text="c.ad"></span> <span x-text="c.soyad"></span>
                                        </a>
                                        <span class="text-sm font-bold text-red-600 dark:text-red-400"
                                            x-text="c.score + '%' "></span>
                                    </div>
                                    <div class="w-full h-1.5 bg-gray-100 dark:bg-slate-900 rounded-full overflow-hidden">
                                        <div class="h-full bg-red-500 rounded-full" :style="'width:' + c.score + '%'">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-6">
            <div
                class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div class="px-6 py-4 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-blue-500 text-white">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ml-4">
                            <h6 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Toplam Müşteri</h6>
                            <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-0 dark:text-slate-100">
                                {{ number_format($stats['total_customers']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div class="px-6 py-4 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500 text-white">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ml-4">
                            <h6 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Aktif Müşteri</h6>
                            <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-0 dark:text-slate-100">
                                {{ number_format($stats['active_customers']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div class="px-6 py-4 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-yellow-500 text-white">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ml-4">
                            <h6 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Bekleyen Takip</h6>
                            <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-0 dark:text-slate-100">
                                {{ number_format($stats['pending_followups']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div class="px-6 py-4 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-cyan-500 text-white">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ml-4">
                            <h6 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Bugünkü Aktivite</h6>
                            <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-0 dark:text-slate-100">
                                {{ number_format($stats['today_activities']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Forecast -->
            <div
                class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div class="px-6 py-4 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-purple-500 text-white">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ml-4">
                            <h6 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Pipeline (Tahmini)</h6>
                            <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-0 dark:text-slate-100">
                                {{ is_null($stats['revenue_forecast']) ? 'Veri Yok' : number_format($stats['revenue_forecast']) . ' ₺' }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-8">
            <!-- Customer Segments Chart -->
            <div
                class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <h5 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">Müşteri Segmentleri</h5>
                </div>
                <div class="px-6 py-4 p-6">
                    <canvas id="customerSegmentsChart" height="300"></canvas>
                </div>
            </div>

            <!-- High Priority Follow-ups -->
            <div
                class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <h5 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">Yüksek Öncelikli Takipler</h5>
                </div>
                <div class="px-6 py-4 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex-grow-1">
                            <h6 class="text-lg font-medium text-gray-900 dark:text-white mb-0 dark:text-slate-100">Acil Takip Gereken</h6>
                        </div>
                        <div class="flex-shrink-0">
                            <span
                                class="px-3 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 text-xs font-medium rounded-full">{{ number_format($stats['high_priority_followups']) }}</span>
                        </div>
                    </div>
    <div class="w-full bg-gray-200 dark:bg-slate-900 rounded-full overflow-hidden h-2">
        @php
            $percentage =
                $stats['total_customers'] > 0
                    ? ($stats['high_priority_followups'] / $stats['total_customers']) * 100
                    : 0;
        @endphp
        <div class="w-full h-full bg-red-500" style="width: {{ $percentage }}%"></div>
    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities & Upcoming Follow-ups -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-8">
            <!-- Recent Activities -->
            <div class="xl:col-span-2">
                <div
                    class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center dark:border-slate-700">
                        <h5 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">Son Aktiviteler</h5>
                        <a href="{{ route('admin.crm.customers.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:outline-none transition-all duration-200 text-sm touch-target-optimized dark:shadow-none">Tümünü
                            Gör</a>
                    </div>
                    <div class="px-6 py-4">
                        <div class="w-full overflow-x-auto">
                            <table
                                class="w-full text-left border-collapse hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-200">
                                <thead>
                                    <tr>
                                        <th class="text-gray-900 dark:text-white dark:text-slate-100">Müşteri</th>
                                        <th class="text-gray-900 dark:text-white dark:text-slate-100">Aktivite</th>
                                        <th class="text-gray-900 dark:text-white dark:text-slate-100">Tip</th>
                                        <th class="text-gray-900 dark:text-white dark:text-slate-100">Tarih</th>
                                        <th class="text-gray-900 dark:text-white dark:text-slate-100">Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentActivities as $activity)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="text-gray-900 dark:text-white dark:text-slate-100">
                                                <a href="{{ route('admin.crm.customers.show', $activity['kisi']['id']) }}"
                                                    class="text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300 hover:underline transition-colors duration-200">
                                                    {{ $activity['kisi']['ad'] }} {{ $activity['kisi']['soyad'] }}
                                                </a>
                                            </td>
                                            <td class="text-gray-600 dark:text-gray-400">
                                                {{ Str::limit($activity['aciklama'], 50) }}</td>
                                            <td>
                                                <span
                                                    class="px-3 py-1 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 text-xs font-medium rounded-full">{{ $activity['aktivite_tipi'] }}</span>
                                            </td>
                                            <td class="text-gray-600 dark:text-gray-400">
                                                {{ \Carbon\Carbon::parse($activity['aktivite_tarihi'])->format('d.m.Y H:i') }}
                                            </td>
                                            <td>
                                                @if (($activity['durum'] ?? '') === 'Tamamlandı')
                                                    <span
                                                        class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-medium rounded-full">Tamamlandı</span>
                                                @elseif(($activity['durum'] ?? '') === 'Bekliyor')
                                                    <span
                                                        class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 text-xs font-medium rounded-full">Bekliyor</span>
                                                @else
                                                    <span
                                                        class="px-3 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 text-xs font-medium rounded-full">İptal</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-gray-500 dark:text-gray-400 py-8">
                                                Henüz aktivite bulunmuyor</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Follow-ups -->
            <div class="xl:col-span-1">
                <div
                    class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <h5 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">Yaklaşan Takipler</h5>
                    </div>
                    <div class="px-6 py-4 p-6">
                        @forelse($upcomingFollowUps as $followUp)
                            <div
                                class="flex items-center mb-4 p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 dark:border-slate-700">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-10 h-10 rounded-full flex items-center justify-center bg-cyan-500 text-white">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ml-3">
                                    <h6 class="text-sm font-semibold text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                        <a href="{{ route('admin.crm.customers.show', $followUp['kisi']['id']) }}"
                                            class="text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300 hover:underline transition-colors duration-200">
                                            {{ $followUp['kisi']['ad'] }} {{ $followUp['kisi']['soyad'] }}
                                        </a>
                                    </h6>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">
                                        {{ \Carbon\Carbon::parse($followUp['sonraki_takip_tarihi'])->format('d.m.Y H:i') }}
                                    </p>
                                    <small class="text-xs text-gray-500 dark:text-gray-400">
                                        Danışman: {{ $followUp['danisman']['ad'] ?? 'Atanmamış' }}
                                    </small>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                                <i class="fas fa-calendar-times text-2xl mb-2"></i>
                                <p>Yaklaşan takip bulunmuyor</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Insights Panel -->
        <div
            class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 mt-8 dark:shadow-none dark:border-slate-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <h5 class="text-lg font-bold text-gray-900 dark:text-white flex items-center dark:text-slate-100">
                    <i class="fas fa-brain text-purple-500 mr-2"></i>
                    AI Akıllı Öngörüler
                </h5>
            </div>
            <div class="px-6 py-4 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Yüksek Potansiyelli Müşteriler -->
                    <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400 mb-2">
                            {{ $stats['active_customers'] ?? 0 }}</div>
                        <div class="text-sm text-green-700 dark:text-green-300 mb-1">Yüksek Potansiyel</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Satın alma olasılığı >70%</div>
                    </div>

                    <!-- Risk Altındaki Müşteriler -->
                    <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400 mb-2">
                            {{ $stats['high_priority_followups'] ?? 0 }}</div>
                        <div class="text-sm text-red-700 dark:text-red-300 mb-1">Risk Altında</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Churn riski >60%</div>
                    </div>

                    <!-- AI Önerileri -->
                    <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mb-2">
                            {{ $stats['pending_followups'] ?? 0 }}</div>
                        <div class="text-sm text-blue-700 dark:text-blue-300 mb-1">AI Önerileri</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Bekleyen aksiyon</div>
                    </div>

                    <!-- Otomatik Etiketleme -->
                    <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mb-2">
                            {{ $stats['today_activities'] ?? 0 }}</div>
                        <div class="text-sm text-purple-700 dark:text-purple-300 mb-1">Otomatik Etiket</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Bugün işlenen</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <x-csp-script src="https://cdn.jsdelivr.net/npm/chart.js"></x-csp-script>
        <script>
            function highRiskCustomersWidget() {
                return {
                    customers: [],
                    loading: false,
                    error: null,
                    async load() {
                        this.loading = true;
                        this.error = null;
                        try {
                            const res = await fetch('/api/ai/churn-risk/top', {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                                        'content') || ''
                                }
                            });
                            if (!res.ok) throw new Error('HTTP ' + res['s'+'tatus']);
                            const data = await res.json();
                            this.customers = (data.customers || []).slice(0, 10);
                        } catch (e) {
                            this.error = 'Veri yüklenemedi';
                        } finally {
                            this.loading = false;
                        }
                    },
                    scoreColorClass(score) {
                        if (score >= 70) return 'text-red-600 dark:text-red-400';
                        if (score >= 40) return 'text-yellow-600 dark:text-yellow-400';
                        return 'text-gray-600 dark:text-gray-300';
                    },
                    scoreBarClass(score) {
                        if (score >= 70) return 'bg-red-500';
                        if (score >= 40) return 'bg-yellow-500';
                        return 'bg-gray-400';
                    }
                }
            }
            document.addEventListener('DOMContentLoaded', function() {
                // Customer Segments Chart
                const ctx = document.getElementById('customerSegmentsChart').getContext('2d');
                const segments = @json($customerSegments);

                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(segments),
                        datasets: [{
                            data: Object.values(segments),
                            backgroundColor: [
                                '#FF6384',
                                '#36A2EB',
                                '#FFCE56',
                                '#4BC0C0',
                                '#9966FF'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            });
        </script>
    @endpush
@endsection
