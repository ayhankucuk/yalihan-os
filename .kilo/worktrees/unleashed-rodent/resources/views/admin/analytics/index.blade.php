@extends('admin.layouts.admin')

@section('title', 'Analytics Dashboard')

@section('styles')
    @parent
    {{-- Chart.js --}}
    <x-csp-script src="https://cdn.jsdelivr.net/npm/chart.js" />
    {{-- Chart.js plugins --}}
    <x-csp-script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns" />
@endsection

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gray-50 dark:bg-slate-900 rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">📊 Analytics Dashboard</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Real-time analytics and insights</p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Son güncelleme: <span id="last-updated">{{ $metrics['last_updated'] ?? 'Bilinmiyor' }}</span>
                </div>
                <button onclick="refreshDashboard()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>Yenile
                </button>
            </div>
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total İlanlar -->
        <div class="bg-gray-50 dark:bg-slate-900 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam İlanlar</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $metrics['total_ilanlar'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-home text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Kategoriler -->
        <div class="bg-gray-50 dark:bg-slate-900 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Kategoriler</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $metrics['total_kategoriler'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-tags text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Kullanıcılar -->
        <div class="bg-gray-50 dark:bg-slate-900 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Kullanıcılar</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $metrics['total_kullanicilar'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Bu Ay Yeni İlanlar -->
        <div class="bg-gray-50 dark:bg-slate-900 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Bu Ay Yeni İlanlar</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $metrics['new_ilanlar_this_month'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-plus-circle text-orange-600 dark:text-orange-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- İlan Trendi Chart -->
        <div class="bg-gray-50 dark:bg-slate-900 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">📈 İlan Trendi (Son 7 Gün)</h3>
            <canvas id="ilanTrendiChart" width="400" height="200"></canvas>
        </div>

        <!-- Kategori Dağılımı Chart -->
        <div class="bg-gray-50 dark:bg-slate-900 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">🥧 Kategori Dağılımı</h3>
            <canvas id="kategoriDagilimiChart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Top Kategori -->
    <div class="bg-gray-50 dark:bg-slate-900 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">🏆 En Popüler Kategori</h3>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $metrics['top_kategori'] ?? 'Veri yok' }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $metrics['top_kategori_count'] ?? 0 }} ilan</p>
            </div>
            <div class="w-16 h-16 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                <i class="fas fa-trophy text-yellow-600 dark:text-yellow-400 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<script>
// İlan Trendi Chart
const ilanTrendiCtx = document.getElementById('ilanTrendiChart').getContext('2d');
const ilanTrendiData = @json($metrics['ilan_trendi'] ?? []);

new Chart(ilanTrendiCtx, {
    type: 'line',
    data: {
        labels: ilanTrendiData.map(item => item.date),
        datasets: [{
            label: 'İlan Sayısı',
            data: ilanTrendiData.map(item => item.count),
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
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Kategori Dağılımı Chart
const kategoriDagilimiCtx = document.getElementById('kategoriDagilimiChart').getContext('2d');
const kategoriDagilimiData = @json($metrics['kategori_dagilimi'] ?? []);

new Chart(kategoriDagilimiCtx, {
    type: 'doughnut',
    data: {
        labels: kategoriDagilimiData.map(item => item.name),
        datasets: [{
            data: kategoriDagilimiData.map(item => item.count),
            backgroundColor: [
                'rgb(59, 130, 246)',
                'rgb(16, 185, 129)',
                'rgb(245, 158, 11)',
                'rgb(239, 68, 68)',
                'rgb(139, 92, 246)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Dashboard yenileme fonksiyonu
function refreshDashboard() {
    location.reload();
}

// Otomatik yenileme (5 dakikada bir)
setInterval(function() {
    fetch('/admin/analytics/data?type=dashboard')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Metrics güncelle
                document.getElementById('last-updated').textContent = new Date().toLocaleString('tr-TR');
            }
        })
        .catch(error => console.error('Dashboard yenileme hatası:', error));
}, 300000); // 5 dakika
</script>
@endsection
