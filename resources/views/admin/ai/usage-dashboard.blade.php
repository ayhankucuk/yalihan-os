@extends('layouts.admin')

@section('title', 'AI Kullanım Dashboard')

@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">AI Kullanım Dashboard</h2>
                    <p class="text-muted mb-0">Tenant: {{ $tenant->name }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.ai.usage.export') }}" class="btn btn-outline-primary">
                        <x-icon name="download" class="w-4 h-4 inline" />
                        CSV Export
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Credit Balance Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="text-muted mb-0">Mevcut Kredi</h6>
                        <x-icon name="wallet" class="w-5 h-5 text-success" />
                    </div>
                    <h3 class="mb-0 fw-bold">{{ number_format($creditBalance->available_credits) }}</h3>
                    <small class="text-muted">/ {{ number_format($creditBalance->monthly_limit) }} aylık limit</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="text-muted mb-0">Kullanılan Kredi</h6>
                        <x-icon name="chart-bar" class="w-5 h-5 text-primary" />
                    </div>
                    <h3 class="mb-0 fw-bold">{{ number_format($creditBalance->used_credits) }}</h3>
                    <small class="text-muted">Bu ay</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="text-muted mb-0">Aylık Kullanım</h6>
                        <x-icon name="calendar" class="w-5 h-5 text-info" />
                    </div>
                    <h3 class="mb-0 fw-bold">{{ number_format($monthlyUsage) }}</h3>
                    <small class="text-muted">İşlem</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="text-muted mb-0">Tahmini Aylık</h6>
                        <x-icon name="trending-up" class="w-5 h-5 text-warning" />
                    </div>
                    <h3 class="mb-0 fw-bold">{{ number_format($projectedMonthlyUsage) }}</h3>
                    <small class="text-muted">Projeksiyon</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row mb-4">
        {{-- Daily Trend Chart --}}
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">30 Günlük Kullanım Trendi</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailyTrendChart" height="80"></canvas>
                </div>
            </div>
        </div>

        {{-- Feature Breakdown Chart --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">Özellik Dağılımı</h5>
                </div>
                <div class="card-body">
                    <canvas id="featureBreakdownChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Consumers Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">En Çok Kullanan Kullanıcılar (Son 30 Gün)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Kullanıcı</th>
                                    <th>Email</th>
                                    <th class="text-end">Kullanım</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topConsumers as $index => $consumer)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $consumer->user->name ?? 'N/A' }}</td>
                                    <td>{{ $consumer->user->email ?? 'N/A' }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-primary">{{ number_format($consumer->usage_count) }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Henüz kullanım verisi yok
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Daily Trend Chart
const dailyTrendCtx = document.getElementById('dailyTrendChart').getContext('2d');
const dailyTrendChart = new Chart(dailyTrendCtx, {
    type: 'line',
    data: {
        labels: @json($dailyTrend->pluck('date')),
        datasets: [{
            label: 'Günlük Kullanım',
            data: @json($dailyTrend->pluck('count')),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});

// Feature Breakdown Chart
const featureBreakdownCtx = document.getElementById('featureBreakdownChart').getContext('2d');
const featureBreakdownChart = new Chart(featureBreakdownCtx, {
    type: 'doughnut',
    data: {
        labels: @json($featureBreakdown->pluck('feature_key')),
        datasets: [{
            data: @json($featureBreakdown->pluck('usage_count')),
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Auto-refresh stats every 30 seconds
setInterval(async () => {
    try {
        const response = await fetch('{{ route("admin.ai.usage.stats") }}');
        const data = await response.json();
        console.log('Stats updated:', data);
        // Update UI if needed
    } catch (error) {
        console.error('Failed to refresh stats:', error);
    }
}, 30000);
</script>
@endpush
@endsection
