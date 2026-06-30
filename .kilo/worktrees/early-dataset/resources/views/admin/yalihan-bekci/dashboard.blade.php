@extends('admin.layouts.admin')

@section('title', 'Yalıhan Bekçi - Dashboard')

@section('content')
<div class="min-h-screen bg-gray-50 py-8 dark:bg-slate-900">
    <div class="container mx-auto px-4 max-w-7xl">

        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white flex items-center gap-3 dark:text-slate-100">
                        <span class="text-5xl">🤖</span>
                        Yalıhan Bekçi
                    </h1>
                    <p class="text-gray-600 mt-2">Otomatik Proje İzleme ve Standardizasyon Sistemi</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="refreshData()" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                        <i class="fas fa-sync-alt"></i>
                        Yenile
                    </button>
                    <button onclick="runCheck()" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                        <i class="fas fa-play"></i>
                        Manuel Kontrol
                    </button>
                    <button onclick="autoFix()" class="px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition flex items-center gap-2">
                        <i class="fas fa-wrench"></i>
                        Otomatik Düzelt
                    </button>
                </div>
            </div>
        </div>

        {{-- Skor Card --}}
        <div class="mb-8">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl shadow-2xl p-8 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl opacity-90 mb-2">Genel Sağlık Skoru</h2>
                        <div class="text-6xl font-bold" id="main-score">{{ $report['score'] }}</div>
                        <div class="text-2xl opacity-90">/100</div>
                    </div>
                    <div class="text-center">
                        <div class="text-8xl mb-2">{{ $report['status']['icon'] }}</div>
                        <div class="text-2xl font-bold">{{ $report['status']['text'] }}</div>
                    </div>
                    <div class="w-64">
                        <div class="relative h-64 w-64">
                            <svg class="transform -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="45" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="8"/>
                                <circle cx="50" cy="50" r="45" fill="none" stroke="white" stroke-width="8"
                                        stroke-dasharray="{{ $report['score'] * 2.83 }} 283"
                                        stroke-linecap="round"
                                        class="transition-all duration-1000"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-5xl font-bold">{{ $report['score'] }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kategori Kartları --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

            {{-- Context7 --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 hover:shadow-xl transition border dark:border-slate-800">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                            <i class="fas fa-code text-purple-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold">Context7</h3>
                    </div>
                    <span class="text-2xl">
                        @if($report['context7']['status'] === 'perfect') ✅
                        @elseif($report['context7']['status'] === 'good') ⚠️
                        @else ❌
                        @endif
                    </span>
                </div>
                <div class="space-y-2">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $report['context7']['violations'] }}</div>
                    <div class="text-sm text-gray-600">İhlal tespit edildi</div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-purple-600 h-2 rounded-full transition-all"
                             style="width: {{ max(0, 100 - $report['context7']['violations']/5) }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Components --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 hover:shadow-xl transition border dark:border-slate-800">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-puzzle-piece text-blue-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold">Components</h3>
                    </div>
                    <span class="text-2xl">
                        @if($report['components']['status'] === 'perfect') ✅
                        @elseif($report['components']['status'] === 'good') ⚠️
                        @else ❌
                        @endif
                    </span>
                </div>
                <div class="space-y-2">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">%{{ $report['components']['rate'] }}</div>
                    <div class="text-sm text-gray-600">Component kullanımı</div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all"
                             style="width: {{ $report['components']['rate'] }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Code Quality --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 hover:shadow-xl transition border dark:border-slate-800">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold">Kod Kalitesi</h3>
                    </div>
                    <span class="text-2xl">
                        @if($report['code_quality']['status'] === 'perfect') ✅
                        @elseif($report['code_quality']['status'] === 'good') ⚠️
                        @else ❌
                        @endif
                    </span>
                </div>
                <div class="space-y-2">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $report['code_quality']['todo_count'] }}</div>
                    <div class="text-sm text-gray-600">TODO/FIXME</div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full transition-all"
                             style="width: {{ max(0, 100 - $report['code_quality']['todo_count']) }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Database --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 hover:shadow-xl transition border dark:border-slate-800">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center">
                            <i class="fas fa-database text-yellow-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold">Database</h3>
                    </div>
                    <span class="text-2xl">
                        @if($report['database']['status'] === 'healthy') ✅
                        @else ❌
                        @endif
                    </span>
                </div>
                <div class="space-y-2">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $report['database']['table_count'] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Aktif tablo</div>
                    <div class="text-xs text-gray-500 mt-2">
                        Bağlantı:
                        <span class="font-semibold {{ $report['database']['connection'] === 'status' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $report['database']['connection'] }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Performance --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 hover:shadow-xl transition border dark:border-slate-800">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="fas fa-tachometer-alt text-red-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold">Performans</h3>
                    </div>
                    <span class="text-2xl">
                        @if($report['performance']['status'] === 'perfect') ✅
                        @else ⚠️
                        @endif
                    </span>
                </div>
                <div class="space-y-2">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                        {{ $report['performance']['cache'] === 'status' ? 'Aktif' : 'Pasif' }}
                    </div>
                    <div class="text-sm text-gray-600">Cache statusu</div>
                    <div class="flex items-center gap-2 mt-2">
                        <div class="w-3 h-3 rounded-full {{ $report['performance']['cache'] === 'status' ? 'bg-green-500 animate-pulse' : 'bg-gray-400' }}"></div>
                        <span class="text-xs text-gray-600">{{ $report['performance']['cache'] === 'status' ? 'Çalışıyor' : 'Durdu' }}</span>
                    </div>
                </div>
            </div>

            {{-- Son Güncelleme --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 hover:shadow-xl transition border dark:border-slate-800">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                            <i class="fas fa-clock text-indigo-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold">Son Kontrol</h3>
                    </div>
                    <span class="text-2xl">⏰</span>
                </div>
                <div class="space-y-2">
                    <div class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $report['timestamp'] }}</div>
                    <div class="text-sm text-gray-600">Son güncelleme zamanı</div>
                    <button onclick="refreshData()" class="mt-2 text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                        <i class="fas fa-redo"></i>
                        Şimdi yenile
                    </button>
                </div>
            </div>
        </div>

        {{-- Grafik --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 mb-8 border dark:border-slate-800">
            <h3 class="text-xl font-bold mb-4">Son 7 Günlük Skor Geçmişi</h3>
            <canvas id="scoreChart" height="80"></canvas>
        </div>

        {{-- Son Loglar --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 border dark:border-slate-800">
            <h3 class="text-xl font-bold mb-4">Son Aktiviteler</h3>
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @forelse($logs as $log)
                    @if(trim($log))
                        <div class="p-3 bg-gray-50 rounded-lg text-sm font-mono dark:bg-slate-900">
                            {{ $log }}
                        </div>
                    @endif
                @empty
                    <p class="text-gray-500 text-center py-8">Henüz log kaydı yok</p>
                @endforelse
            </div>
        </div>

    </div>
</div>

{{-- Toast Notification --}}
<div id="toast" class="fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg text-white transform translate-x-full transition-transform duration-300 z-50">
    <span id="toast-message"></span>
</div>

@endsection

@push('scripts')
<x-csp-script src="https://cdn.jsdelivr.net/npm/chart.js" />
<script>
// Grafik
const ctx = document.getElementById('scoreChart').getContext('2d');
const scoreChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json(array_column($history, 'date')),
        datasets: [{
            label: 'Skor',
            data: @json(array_column($history, 'score')),
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        }
    }
});

// Toast göster
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');

    toast.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg text-white transform transition-transform duration-300 z-50`;

    if (type === 'success') {
        toast.classList.add('bg-green-600');
    } else if (type === 'error') {
        toast.classList.add('bg-red-600');
    } else {
        toast.classList.add('bg-blue-600');
    }

    toastMessage.textContent = message;
    toast.classList.remove('translate-x-full');

    setTimeout(() => {
        toast.classList.add('translate-x-full');
    }, 3000);
}

// Veriyi yenile
function refreshData() {
    showToast('Veriler yenileniyor...', 'info');
    location.reload();
}

// Manuel kontrol
async function runCheck() {
    showToast('Kontrol başlatıldı...', 'info');

    try {
        const response = await fetch('/admin/yalihan-bekci/run-check', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();
        showToast(data.message, 'success');

        setTimeout(() => location.reload(), 2000);
    } catch (error) {
        showToast('Kontrol başlatılamadı!', 'error');
    }
}

// Otomatik düzelt
async function autoFix() {
    if (!confirm('Otomatik düzeltme başlatılsın mı? Bu işlem dosyaları değiştirebilir.')) {
        return;
    }

    showToast('Otomatik düzeltme başlatıldı...', 'info');

    try {
        const response = await fetch('/admin/yalihan-bekci/auto-fix', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();
        showToast(data.message, 'success');

        setTimeout(() => location.reload(), 3000);
    } catch (error) {
        showToast('Otomatik düzeltme başlatılamadı!', 'error');
    }
}

// Otomatik yenileme (opsiyonel - 60 saniyede bir)
// setInterval(refreshData, 60000);
</script>
@endpush
