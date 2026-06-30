@extends('admin.layouts.admin')

@section('title', 'Context7 Analytics Dashboard - Yalıhan Bekçi')

@section('content')
    <div class="min-h-screen bg-gray-50 transition-all duration-200 ease-in-out dark:bg-slate-900">
        <!-- Header Section -->
        <div
            class="border-b border-gray-200 bg-white shadow-sm transition-all duration-200 ease-in-out dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
            <div class="mx-auto max-w-7xl px-4 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1
                            class="text-3xl font-bold text-gray-900 transition-all duration-200 ease-in-out dark:text-slate-100 dark:text-white">
                            📊 Context7 Analytics Dashboard
                        </h1>
                        <p class="mt-2 text-gray-600 transition-all duration-200 ease-in-out dark:text-slate-200">
                            Yalıhan Bekçi - Real-time Project Analytics & AI Learning
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <button id="refresh-data"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-white shadow-lg transition-all duration-200 ease-in-out hover:scale-105 hover:bg-blue-700 active:scale-95">
                            🔄 Yenile
                        </button>
                        <button id="recalculate-health"
                            class="rounded-lg bg-green-600 px-4 py-2 text-white shadow-lg transition-all duration-200 ease-in-out hover:scale-105 hover:bg-green-700 active:scale-95">
                            🔬 Sağlık Hesapla
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Dashboard -->
        <div class="mx-auto max-w-7xl px-4 py-6">
            <!-- Real-time Status Cards -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <!-- Overall Health Card -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-lg transition-all duration-200 ease-in-out hover:scale-105 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="text-3xl">🎯</div>
                        <div id="health-trend"
                            class="rounded-full bg-green-100 px-2 py-1 text-sm font-semibold text-green-800 dark:bg-green-900 dark:text-green-300">
                            📈 Improving
                        </div>
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Genel Sağlık
                    </h3>
                    <div class="flex items-end space-x-2">
                        <span id="health-score" class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                            85.2
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">%</span>
                    </div>
                    <p id="sistem-durumu" class="mt-2 text-sm capitalize text-gray-600 dark:text-slate-200">
                        Good Health
                    </p>
                </div>

                <!-- Context7 Compliance Card -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-lg transition-all duration-200 ease-in-out hover:scale-105 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="text-3xl">⚖️</div>
                        <div class="text-sm font-semibold text-green-600 dark:text-green-400">Context7</div>
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Uyumluluk</h3>
                    <div class="flex items-end space-x-2">
                        <span id="context7-score" class="text-3xl font-bold text-green-600 dark:text-green-400">
                            92.8
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">%</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-slate-200">
                        <span id="active-violations" class="font-semibold text-red-600 dark:text-red-400">3</span> aktif
                        ihlal
                    </p>
                </div>

                <!-- Today's Activity Card -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-lg transition-all duration-200 ease-in-out hover:scale-105 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="text-3xl">⚡</div>
                        <div class="text-sm font-semibold text-purple-600 dark:text-purple-400">Bugün</div>
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Aktivite</h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-slate-200">Commit</span>
                            <span id="commits-today" class="font-semibold text-purple-600 dark:text-purple-400">12</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-slate-200">Build</span>
                            <span id="builds-today" class="font-semibold text-purple-600 dark:text-purple-400">8</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-slate-200">Test</span>
                            <span id="tests-today" class="font-semibold text-purple-600 dark:text-purple-400">45</span>
                        </div>
                    </div>
                </div>

                <!-- AI Learning Card -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-lg transition-all duration-200 ease-in-out hover:scale-105 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="text-3xl">🧠</div>
                        <div class="text-sm font-semibold text-orange-600 dark:text-orange-400">AI Bekçi</div>
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Öğrenme</h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-slate-200">Oturum</span>
                            <span id="ai-sessions" class="font-semibold text-orange-600 dark:text-orange-400">7</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-slate-200">Kalıp</span>
                            <span id="patterns-learned" class="font-semibold text-orange-600 dark:text-orange-400">15</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-slate-200">Fikir</span>
                            <span id="ideas-generated" class="font-semibold text-orange-600 dark:text-orange-400">23</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Context7 Compliance Chart -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-lg transition-all duration-200 ease-in-out dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">📈 Context7
                        Uyumluluk Trendi</h3>
                    <div class="h-64">
                        <canvas id="complianceChart"></canvas>
                    </div>
                </div>

                <!-- Development Velocity Chart -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-lg transition-all duration-200 ease-in-out dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">🚀 Geliştirme
                        Hızı</h3>
                    <div class="h-64">
                        <canvas id="velocityChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Detailed Analytics Row -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Violations Summary -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-lg transition-all duration-200 ease-in-out dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">⚠️ İhlaller (7
                        Gün)</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-slate-200">Toplam</span>
                            <span id="total-violations" class="font-bold text-red-600 dark:text-red-400">8</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-slate-200">Otomatik Düzeltilen</span>
                            <span id="auto-fixed" class="font-bold text-green-600 dark:text-green-400">6</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-slate-200">Kritik</span>
                            <span id="critical-violations" class="font-bold text-red-800 dark:text-red-300">1</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-slate-200">Düzeltme Oranı</span>
                            <span id="fix-rate" class="font-bold text-blue-600 dark:text-blue-400">75.0%</span>
                        </div>
                    </div>
                </div>

                <!-- Velocity Metrics -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-lg transition-all duration-200 ease-in-out dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">📊 Hız
                        Metrikleri</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-slate-200">Commit/Gün</span>
                            <span id="velocity-commits" class="font-bold text-purple-600 dark:text-purple-400">24</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-slate-200">Dosya Değişimi</span>
                            <span id="velocity-files" class="font-bold text-purple-600 dark:text-purple-400">156</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-slate-200">Satır (+/-)</span>
                            <span id="velocity-lines" class="font-bold text-purple-600 dark:text-purple-400">+2,847</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-slate-200">Üretkenlik Skoru</span>
                            <span id="productivity-score" class="font-bold text-green-600 dark:text-green-400">88.5</span>
                        </div>
                    </div>
                </div>

                <!-- Live Status -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-lg transition-all duration-200 ease-in-out dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">🔴 Canlı Durum
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div id="mcp-durumu" class="h-3 w-3 animate-pulse rounded-full bg-green-500"></div>
                            <span class="text-gray-600 dark:text-slate-200">MCP Server</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div id="git-durumu" class="h-3 w-3 animate-pulse rounded-full bg-green-500"></div>
                            <span class="text-gray-600 dark:text-slate-200">Git Hooks</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div id="context7-durumu" class="h-3 w-3 animate-pulse rounded-full bg-green-500"></div>
                            <span class="text-gray-600 dark:text-slate-200">Context7 Validator</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div id="bekci-durumu" class="h-3 w-3 animate-pulse rounded-full bg-green-500"></div>
                            <span class="text-gray-600 dark:text-slate-200">Yalıhan Bekçi</span>
                        </div>
                        <div class="mt-4 rounded-lg bg-gray-50 p-3 dark:bg-gray-700 dark:bg-slate-900">
                            <p class="text-xs text-gray-600 dark:text-slate-200">
                                Son güncelleme: <span id="last-update">{{ now()->format('H:i:s') }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="notification" class="fixed right-4 top-4 z-50 hidden">
        <div
            class="rounded-lg border border-gray-200 bg-white p-4 shadow-lg transition-all duration-200 ease-in-out dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center space-x-3">
                <div id="notification-icon" class="text-2xl"></div>
                <div>
                    <p id="notification-title" class="font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                    </p>
                    <p id="notification-message" class="text-sm text-gray-600 dark:text-slate-200"></p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <x-csp-script src="https://cdn.jsdelivr.net/npm/chart.js" />
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts
            const complianceCtx = document.getElementById('complianceChart').getContext('2d');
            const velocityCtx = document.getElementById('velocityChart').getContext('2d');

            let complianceChart, velocityChart;

            // Chart colors for dark mode support
            const isDarkMode = document.documentElement.classList.contains('dark') || document.body.classList
                .contains('dark');
            const textColor = isDarkMode ? '#f3f4f6' : '#374151';
            const gridColor = isDarkMode ? '#374151' : '#e5e7eb';

            function initializeCharts() {
                // Compliance Chart
                complianceChart = new Chart(complianceCtx, {
                    type: 'line',
                    data: {
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        datasets: [{
                            label: 'Context7 Uyumluluk %',
                            data: [85, 87, 92, 88, 94, 96, 93],
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: {
                                    color: textColor
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: textColor
                                },
                                grid: {
                                    color: gridColor
                                }
                            },
                            y: {
                                ticks: {
                                    color: textColor
                                },
                                grid: {
                                    color: gridColor
                                },
                                min: 0,
                                max: 100
                            }
                        }
                    }
                });

                // Velocity Chart
                velocityChart = new Chart(velocityCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Commits', 'Files', 'Lines', 'Fixes'],
                        datasets: [{
                            label: 'Bu Hafta',
                            data: [24, 156, 284, 19],
                            backgroundColor: ['#8b5cf6', '#06b6d4', '#f59e0b', '#10b981'],
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: {
                                    color: textColor
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: textColor
                                },
                                grid: {
                                    color: gridColor
                                }
                            },
                            y: {
                                ticks: {
                                    color: textColor
                                },
                                grid: {
                                    color: gridColor
                                }
                            }
                        }
                    }
                });
            }

            function showNotification(title, message, type = 'info') {
                const notification = document.getElementById('notification');
                const icon = document.getElementById('notification-icon');
                const titleEl = document.getElementById('notification-title');
                const messageEl = document.getElementById('notification-message');

                const icons = {
                    success: '✅',
                    error: '❌',
                    warning: '⚠️',
                    info: 'ℹ️'
                };

                icon.textContent = icons[type];
                titleEl.textContent = title;
                messageEl.textContent = message;

                notification.classList.remove('hidden');
                setTimeout(() => notification.classList.add('hidden'), 5000);
            }

            function updateDashboard() {
                // Mock data update - replace with actual API call
                const mockData = {
                    health: {
                        overall_score: 85.2 + (Math.random() * 10 - 5),
                        context7_score: 92.8 + (Math.random() * 5 - 2.5),
                        durum: 'good',
                        trend: 'improving'
                    },
                    violations: {
                        active: Math.floor(Math.random() * 5) + 1,
                        today: Math.floor(Math.random() * 10),
                        auto_fixed_today: Math.floor(Math.random() * 8)
                    },
                    activity: {
                        commits_today: Math.floor(Math.random() * 20) + 5,
                        builds_today: Math.floor(Math.random() * 15) + 3,
                        tests_run: Math.floor(Math.random() * 100) + 20
                    },
                    ai_learning: {
                        sessions_today: Math.floor(Math.random() * 10) + 2,
                        patterns_learned: Math.floor(Math.random() * 20) + 10,
                        ideas_generated: Math.floor(Math.random() * 30) + 15
                    }
                };

                // Update dashboard cards
                document.getElementById('health-score').textContent = mockData.health.overall_score.toFixed(1);
                document.getElementById('context7-score').textContent = mockData.health.context7_score.toFixed(1);
                document.getElementById('active-violations').textContent = mockData.violations.active;
                document.getElementById('commits-today').textContent = mockData.activity.commits_today;
                document.getElementById('builds-today').textContent = mockData.activity.builds_today;
                document.getElementById('tests-today').textContent = mockData.activity.tests_run;

                // Update AI learning metrics
                document.getElementById('ai-sessions').textContent = mockData.ai_learning.sessions_today;
                document.getElementById('patterns-learned').textContent = mockData.ai_learning.patterns_learned;
                document.getElementById('ideas-generated').textContent = mockData.ai_learning.ideas_generated;

                // Update last update time
                document.getElementById('last-update').textContent = new Date().toLocaleTimeString();

                console.log('Dashboard updated successfully');
            }

            // Event listeners
            document.getElementById('refresh-data').addEventListener('click', function() {
                this.classList.add('animate-spin');
                updateDashboard();
                setTimeout(() => this.classList.remove('animate-spin'), 1000);
                showNotification('Yenileme', 'Veriler güncellendi', 'success');
            });

            document.getElementById('recalculate-health').addEventListener('click', function() {
                showNotification('Sağlık Hesaplanıyor', 'Proje sağlığı yeniden hesaplanıyor...', 'info');
                setTimeout(() => {
                    updateDashboard();
                    showNotification('Sağlık Hesaplandı', 'Proje sağlığı başarıyla güncellendi',
                        'success');
                }, 2000);
            });

            // Initialize
            initializeCharts();
            updateDashboard();

            // Auto-refresh every 30 seconds
            setInterval(updateDashboard, 30000);
        });
    </script>
@endpush
