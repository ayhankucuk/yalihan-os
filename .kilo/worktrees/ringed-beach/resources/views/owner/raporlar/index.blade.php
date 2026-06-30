@extends('layouts.owner')

@section('title', 'Raporlar ve Analizler')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="{
         exportFormat: 'pdf',
         isExporting: false,
         exportMessage: null,
         exportError: null,
         doExport() {
             if (this.isExporting) return;
             this.isExporting = true;
             this.exportMessage = null;
             this.exportError = null;
             const params = new URLSearchParams(window.location.search);
             fetch('{{ route('owner.reports.export') }}', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                     'Accept': 'application/json',
                 },
                 body: JSON.stringify({
                     format: this.exportFormat,
                     baslangic_tarihi: params.get('baslangic_tarihi') || '{{ now()->subMonth()->toDateString() }}',
                     bitis_tarihi:     params.get('bitis_tarihi')     || '{{ now()->toDateString() }}',
                     ilan_id:          params.get('ilan_id')          || null,
                 })
             })
             .then(r => r.json())
             .then(data => {
                 this.isExporting = false;
                 if (data.success) {
                     this.exportMessage = 'Rapor hazırlanıyor. Hazır olduğunda buradan indirebilirsiniz.';
                 } else {
                     this.exportError = data.message ?? 'Bir hata oluştu.';
                 }
             })
             .catch(() => {
                 this.isExporting = false;
                 this.exportError = 'Bağlantı hatası. Lütfen tekrar deneyin.';
             });
         }
     }">

    <!-- Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300">
                Performans Raporları
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Mülklerinizin piyasa performansını ve danışman aktivitelerini detaylı analiz edin.
            </p>
        </div>

        <!-- Export Araçları -->
        <div class="flex items-center gap-3 bg-white/40 dark:bg-gray-800/40 backdrop-blur-xl p-2 rounded-2xl border border-white/50 dark:border-gray-700/50 shadow-sm">
            <select x-model="exportFormat"
                    class="bg-transparent border-none text-sm font-medium text-gray-700 dark:text-gray-300 focus:ring-0 cursor-pointer">
                <option value="pdf">PDF Formatında</option>
                <option value="csv">CSV (Excel)</option>
            </select>
            <button @click="doExport()"
                    :class="{ 'opacity-75 cursor-wait': isExporting }"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-medium rounded-xl shadow-lg shadow-blue-500/30 transition-all duration-300 transform hover:-translate-y-0.5">
                <svg x-show="!isExporting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <svg x-show="isExporting" x-cloak class="animate-spin w-4 h-4"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                <span x-text="isExporting ? 'Hazırlanıyor...' : 'Raporu İndir'"></span>
            </button>
        </div>
    </div>

    <!-- Export Bildirim -->
    <template x-if="exportMessage">
        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-xl text-green-700 dark:text-green-300 text-sm"
             x-text="exportMessage"></div>
    </template>
    <template x-if="exportError">
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl text-red-700 dark:text-red-300 text-sm"
             x-text="exportError"></div>
    </template>

    <!-- Filtre Formu -->
    <form method="GET" action="{{ route('owner.reports.index') }}"
          class="mb-6 p-4 bg-white/60 dark:bg-gray-800/60 backdrop-blur-xl border border-white/50 dark:border-gray-700/50 rounded-2xl shadow-sm">
        <div class="flex flex-col sm:flex-row gap-3 items-end">
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Başlangıç Tarihi</label>
                <input type="date" name="baslangic_tarihi"
                       value="{{ $baslangicTar ?? now()->subMonth()->toDateString() }}"
                       class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Bitiş Tarihi</label>
                <input type="date" name="bitis_tarihi"
                       value="{{ $bitisTar ?? now()->toDateString() }}"
                       class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="submit"
                    class="px-5 py-2.5 bg-gray-900 dark:bg-white dark:text-gray-900 text-white text-sm font-medium rounded-xl hover:bg-gray-700 dark:hover:bg-gray-100 transition-colors">
                Filtrele
            </button>
            @if($baslangicTar || $bitisTar || $ilanId)
            <a href="{{ route('owner.reports.index') }}"
               class="px-5 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 text-sm font-medium rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                Temizle
            </a>
            @endif
        </div>
    </form>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        @forelse($metrics as $metric)
        <div class="relative group">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-500/10 to-indigo-500/10 rounded-3xl blur-xl transition-all duration-500 group-hover:scale-110 opacity-0 group-hover:opacity-100"></div>
            <div class="relative p-6 bg-white/60 dark:bg-gray-800/60 backdrop-blur-xl border border-white/50 dark:border-gray-700/50 rounded-3xl shadow-sm hover:shadow-lg transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            {{ $metric->metric_name ?? 'Metrik' }}
                        </p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $metric->metric_value ?? '0' }}
                        </h3>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full p-8 bg-white/60 dark:bg-gray-800/60 backdrop-blur-xl border border-white/50 dark:border-gray-700/50 rounded-3xl text-center">
            <svg class="w-10 h-10 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Bu dönem için özet metrik bulunmuyor.</p>
        </div>
        @endforelse
    </div>

    <!-- Detaylı Tablo -->
    <div class="bg-white/60 dark:bg-gray-800/60 backdrop-blur-xl border border-white/50 dark:border-gray-700/50 rounded-3xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700/50 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Detaylı Performans Geçmişi</h2>
            <span class="text-sm text-gray-400 dark:text-gray-500">{{ $rows->total() }} kayıt</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-900/50">
                        <th class="py-4 px-6 text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700/50">Tarih</th>
                        <th class="py-4 px-6 text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700/50">Aksiyon Tipi</th>
                        <th class="py-4 px-6 text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700/50">Açıklama</th>
                        <th class="py-4 px-6 text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700/50">Durum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @forelse($rows as $row)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="py-4 px-6 text-sm text-gray-900 dark:text-gray-300">
                            {{ \Carbon\Carbon::parse($row->kayit_tarihi)->format('d.m.Y H:i') }}
                        </td>
                        <td class="py-4 px-6">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                {{ $row->action_type ?? 'Genel' }}
                            </span>
                        </td>
                        <td class="py-4 px-6 text-sm text-gray-600 dark:text-gray-400">
                            {{ $row->description ?? '—' }}
                        </td>
                        <td class="py-4 px-6">
                            @php
                                $durum = $row->durum_kodu ?? $row->status ?? 'basarili';
                                $durumMap = [
                                    'basarili'  => ['label' => 'Başarılı',  'color' => 'emerald'],
                                    'beklemede' => ['label' => 'Beklemede', 'color' => 'amber'],
                                    'iptal'     => ['label' => 'İptal',     'color' => 'red'],
                                    'islemde'   => ['label' => 'İşlemde',   'color' => 'blue'],
                                ];
                                $d = $durumMap[$durum] ?? ['label' => ucfirst($durum), 'color' => 'gray'];
                            @endphp
                            <span class="inline-flex items-center gap-1.5 text-sm font-medium text-{{ $d['color'] }}-600 dark:text-{{ $d['color'] }}-400">
                                <span class="w-2 h-2 rounded-full bg-{{ $d['color'] }}-500"></span>
                                {{ $d['label'] }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-12 px-6 text-center">
                            <svg class="w-10 h-10 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">Bu tarih aralığında aktivite bulunamadı.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rows->hasPages())
        <div class="p-4 border-t border-gray-100 dark:border-gray-700/50">
            {{ $rows->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
