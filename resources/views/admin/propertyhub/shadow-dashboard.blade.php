@extends('admin.layouts.admin')

@section('title', 'Shadow Mode | Drift Analytics')

@push('styles')
<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 1rem;
    }
    .dark .glass-card {
        background: rgba(15, 23, 42, 0.6);
        border-color: rgba(51, 65, 85, 0.5);
    }
    .stat-value {
        font-family: 'Inter', sans-serif;
        font-weight: 700;
        letter-spacing: -0.025em;
    }
    .state-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .match-glow {
        box-shadow: 0 0 15px rgba(34, 197, 94, 0.2);
    }
</style>
<x-csp-script src="https://cdn.jsdelivr.net/npm/chart.js" />
@endpush

@section('content')
<div class="px-4 pb-12">
    <!-- Header -->
    <div class="flex justify-between items-end mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white mb-2">Shadow Mode Drift Monitor</h1>
            <p class="text-slate-500 dark:text-slate-400">V2 vs V3 Real-time Parity & Performance Intelligence</p>
        </div>
        <div class="flex gap-4 items-center">
            <div id="circuitBadge" class="state-badge bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                CIRCUIT: HEALTHY
            </div>
            <select id="timeRange" class="bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                <option value="1">Last Hour</option>
                <option value="6">Last 6 Hours</option>
                <option value="24" selected>Last 24 Hours</option>
                <option value="168">Last 7 Days</option>
            </select>
            <button onclick="refreshDashboard()" class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-6 py-2 text-sm font-semibold transition-all">
                Refresh Now
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <div class="glass-card p-6 match-glow">
            <div class="text-slate-500 dark:text-slate-400 text-sm font-medium mb-2">Overall Parity</div>
            <div id="parityVal" class="stat-value text-3xl text-green-600 dark:text-green-400">--%</div>
            <div class="text-xs text-slate-400 mt-2">V2/V3 Result Sync Rate</div>
        </div>
        <div class="glass-card p-6 mismatch-glow">
            <div class="text-slate-500 dark:text-slate-400 text-sm font-medium mb-2">Mismatches</div>
            <div id="mismatchCount" class="stat-value text-3xl text-red-600 dark:text-red-400">0</div>
            <div class="text-xs text-slate-400 mt-2">Divergences detected</div>
        </div>
        <div class="glass-card p-6">
            <div class="text-slate-500 dark:text-slate-400 text-sm font-medium mb-2">Drift Slope</div>
            <div id="slopeVal" class="stat-value text-3xl text-purple-600 dark:text-purple-400">0.0%</div>
            <div class="text-xs text-slate-400 mt-2">Rate of Change (Bucket)</div>
        </div>
        <div class="glass-card p-6">
            <div class="text-slate-500 dark:text-slate-400 text-sm font-medium mb-2">Performance Shift</div>
            <div id="latencyDelta" class="stat-value text-3xl text-blue-600 dark:text-blue-400">0ms</div>
            <div class="text-xs text-slate-400 mt-2">Avg V3 Latency Delta</div>
        </div>
        <div class="glass-card p-6">
            <div class="text-slate-500 dark:text-slate-400 text-sm font-medium mb-2">V3 Crashes</div>
            <div id="errorCount" class="stat-value text-3xl text-orange-600 dark:text-orange-400">0</div>
            <div class="text-xs text-slate-400 mt-2">Surface Errors (Rescue)</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Trend Chart -->
        <div class="glass-card p-6 lg:col-span-2">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Drift Trend (Hourly)</h3>
                <span class="state-badge bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">Live Telemetry</span>
            </div>
            <div class="h-64">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
        <!-- Template Drift Heatmap -->
        <div class="glass-card p-6">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-6">Problematic Templates</h3>
            <div id="templateList" class="space-y-4">
                <!-- Dynamic -->
                <div class="animate-pulse bg-slate-200 dark:bg-slate-700 h-8 rounded"></div>
                <div class="animate-pulse bg-slate-200 dark:bg-slate-700 h-8 rounded"></div>
                <div class="animate-pulse bg-slate-200 dark:bg-slate-700 h-8 rounded"></div>
            </div>
        </div>
    </div>

    <!-- Recent Mismatches Table -->
    <div class="glass-card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Recent Divergence Events</h3>
            <a href="#" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View All Logs</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50">
                        <th class="px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Timestamp</th>
                        <th class="px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">V2 Template</th>
                        <th class="px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">V3 Template</th>
                        <th class="px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Latency V3</th>
                        <th class="px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Outcome</th>
                    </tr>
                </thead>
                <tbody id="mismatchTableBody" class="divide-y divide-slate-200 dark:divide-slate-700">
                    <!-- Rows injected via JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let trendChart = null;

    async function refreshDashboard() {
        const hours = document.getElementById('timeRange').value;
        const response = await fetch(`{{ route('admin.property-hub.shadow-dashboard.stats') }}?hours=${hours}`);
        const data = await response.json();

        updateStats(data.summary);
        updateTrends(data.trends);
        updateTemplateDrift(data.template_drift);
        fetchRecentMismatches();
    }

    function updateStats(summary) {
        document.getElementById('parityVal').innerText = summary.match_rate + '%';
        document.getElementById('mismatchCount').innerText = summary.mismatches;
        document.getElementById('slopeVal').innerText = summary.mismatch_slope;
        document.getElementById('latencyDelta').innerText = (summary.avg_latency_v3 - summary.avg_latency_v2).toFixed(1) + 'ms';
        document.getElementById('errorCount').innerText = summary.errors;

        // Circuit Badge
        const badge = document.getElementById('circuitBadge');
        if (summary.circuit_state === 'TRIPPED') {
            badge.innerText = 'CIRCUIT: TRIPPED';
            badge.className = 'state-badge bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        } else {
            badge.innerText = 'CIRCUIT: HEALTHY';
            badge.className = 'state-badge bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        }
    }

    function updateTrends(trends) {
        const ctx = document.getElementById('trendChart').getContext('2d');
        const labels = trends.map(t => t.hour.split(' ')[1]); // Focus on hours
        const totalData = trends.map(t => t.count);
        const driftData = trends.map(t => t.mismatches);

        if (trendChart) trendChart.destroy();

        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Total Shadow Resolves',
                        data: totalData,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Mismatches',
                        data: driftData,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: { color: document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b' }
                    }
                },
                scales: {
                    y: { beginAtZero: true },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    function updateTemplateDrift(templates) {
        const list = document.getElementById('templateList');
        list.innerHTML = '';

        if (templates.length === 0) {
            list.innerHTML = '<div class="text-slate-400 text-sm">Perfect parity detected.</div>';
            return;
        }

        templates.forEach(t => {
            const div = document.createElement('div');
            div.className = 'flex justify-between items-center bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg border border-slate-100 dark:border-slate-700';
            div.innerHTML = `
                <span class="text-sm font-medium dark:text-slate-200">Template #${t.template_id_v2}</span>
                <span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold rounded">${t.drift_count} drifts</span>
            `;
            list.appendChild(div);
        });
    }

    async function fetchRecentMismatches() {
        const response = await fetch(`{{ route('admin.property-hub.shadow-dashboard.mismatches') }}`);
        const data = await response.json();
        const tbody = document.getElementById('mismatchTableBody');
        tbody.innerHTML = '';

        data.forEach(m => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="px-6 py-4 text-xs dark:text-slate-400">${new Date(m.created_at).toLocaleString('tr-TR')}</td>
                <td class="px-6 py-4 text-xs font-mono dark:text-slate-300">#${m.template_id_v2 || 'N/A'}</td>
                <td class="px-6 py-4 text-xs dark:text-slate-300">${m.template_id_v2 || '-'}</td>
                <td class="px-6 py-4 text-xs dark:text-slate-300">${m.template_id_v3 || '-'}</td>
                <td class="px-6 py-4 text-xs font-medium ${m.latency_ms_v3 > m.latency_ms_v2 + 50 ? 'text-red-500' : 'text-slate-500'}">${m.latency_ms_v3}ms</td>
                <td class="px-6 py-4">
                    <span class="state-badge ${m.error_v3 ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800'}">
                        ${m.error_v3 ? 'V3 CRASH' : 'SIGNATURE DRIFT'}
                    </span>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    document.addEventListener('DOMContentLoaded', refreshDashboard);
</script>
@endpush
