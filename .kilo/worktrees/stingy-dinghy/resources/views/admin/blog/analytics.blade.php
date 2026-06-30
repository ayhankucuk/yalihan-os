@extends('admin.layouts.admin')

@section('title', 'Blog Analitikleri')
@section('page-title', 'Blog Analitikleri')

@push('styles')
    <style>
        /* Modern Dashboard Styles */
        .btn-modern {
            @apply inline-flex items-center px-6 py-3 rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-lg;
        }

        .btn-modern-primary {
            @apply bg-gradient-to-r from-blue-600 to-purple-600 text-white hover:from-blue-700 hover:to-purple-700 shadow-blue-500/25;
        }

        .btn-modern-secondary {
            @apply bg-gradient-to-r from-gray-600 to-gray-700 text-white hover:from-gray-700 hover:to-gray-800 shadow-gray-500/25;
        }

        .admin-input {
            @apply px-4 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500 focus:outline-none transition-all duration-200;
        }

        .admin-input:focus {
            @apply ring-2 ring-blue-200;
        }

        .stat-card {
            @apply bg-white rounded-xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-shadow duration-200;
        }

        /* Chart Container */
        canvas {
            @apply w-full h-64;
        }
    </style>
@endpush

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50">
        <!-- Header with Filters -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 mb-8 p-8 dark:bg-slate-900 dark:border-slate-800">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                <div>
                    <h1
                        class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                        📊 Blog Analitikleri
                    </h1>
                    <p class="mt-3 text-lg text-gray-600">Blog performansınızı detaylı olarak inceleyin</p>
                </div>
                <div class="flex items-center space-x-3">
                    <form method="GET" action="{{ route('admin.blog.analytics') }}" class="flex items-center space-x-3">
                        <select style="color-scheme: light dark;" name="days" onchange="this.form.submit()" class="admin-input transition-all duration-200">
                            <option value="7" {{ $days == 7 ? 'selected' : '' }}>Son 7 Gün</option>
                            <option value="30" {{ $days == 30 ? 'selected' : '' }}>Son 30 Gün</option>
                            <option value="90" {{ $days == 90 ? 'selected' : '' }}>Son 90 Gün</option>
                            <option value="365" {{ $days == 365 ? 'selected' : '' }}>Son 1 Yıl</option>
                        </select>
                    </form>
                    <a href="{{ route('admin.blog.clear-cache') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 touch-target-optimized dark:text-slate-300">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.001 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        Cache Temizle
                    </a>
                </div>
            </div>
        </div>

        <!-- Overview Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Toplam Görüntülenme</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                            {{ number_format($analytics['total_views']) }}</p>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Benzersiz Ziyaretçi</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                            {{ number_format($analytics['unique_visitors']) }}</p>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Ort. Okuma Süresi</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                            {{ round($analytics['avg_reading_time'] / 60, 1) }}dk</p>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Tamamlama Oranı</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                            {{ round($analytics['completion_rate']) }}%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Detailed Analytics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Views by Date Chart -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                <div class="p-6 border-b border-gray-100 dark:border-slate-800">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                        <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 12l3-3 3 3m0 0l-3 3-3-3m3 3V6"></path>
                        </svg>
                        Günlük Görüntülenme
                    </h3>
                </div>
                <div class="p-6">
                    <canvas id="viewsChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Top Posts -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                <div class="p-6 border-b border-gray-100 dark:border-slate-800">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                        <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        En Popüler Yazılar
                    </h3>
                </div>
                <div class="p-6">
                    @if ($analytics['top_posts']->isEmpty())
                        <p class="text-gray-500 text-center py-4">Veri bulunamadı</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($analytics['top_posts'] as $post)
                                <div
                                    class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors duration-200 dark:border-slate-700">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 truncate dark:text-slate-100 dark:text-white">
                                            <a href="{{ route('admin.blog.posts.show', $post) }}"
                                                class="hover:text-blue-600 transition-colors duration-200">
                                                {{ $post->title }}
                                            </a>
                                        </h4>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ $post->category->name ?? 'Kategori Yok' }} •
                                            {{ $post->published_at->format('d.m.Y') }}
                                        </p>
                                    </div>
                                    <div class="text-right ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                            {{ number_format($post->view_count) }}</div>
                                        <div class="text-xs text-gray-500">görüntülenme</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Additional Analytics -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Traffic Sources -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                <div class="p-6 border-b border-gray-100 dark:border-slate-800">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                        <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Trafik Kaynakları
                    </h3>
                </div>
                <div class="p-6">
                    @if ($analytics['traffic_sources']->isEmpty())
                        <p class="text-gray-500 text-center py-4">Veri bulunamadı</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($analytics['traffic_sources'] as $source)
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-slate-300">{{ $source->source }}</span>
                                    </div>
                                    <span class="text-sm text-gray-500">{{ $source->count }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Top Categories -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                <div class="p-6 border-b border-gray-100 dark:border-slate-800">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                        <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                            </path>
                        </svg>
                        Popüler Kategoriler
                    </h3>
                </div>
                <div class="p-6">
                    @if ($analytics['top_categories']->isEmpty())
                        <p class="text-gray-500 text-center py-4">Veri bulunamadı</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($analytics['top_categories'] as $category)
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-3 h-3 rounded-full"
                                            style="background-color: {{ $category->color ?? '#6366f1' }}"></div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-slate-300">{{ $category->name }}</span>
                                    </div>
                                    <span class="text-sm text-gray-500">{{ $category->posts_count }}
                                        yazı</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Engagement Stats -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                <div class="p-6 border-b border-gray-100 dark:border-slate-800">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                        <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                            </path>
                        </svg>
                        Etkileşim İstatistikleri
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Toplam Yorum</span>
                            <span
                                class="text-sm text-gray-500">{{ number_format($analytics['engagement_stats']['comments']) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Onaylı Yorum</span>
                            <span
                                class="text-sm text-gray-500">{{ number_format($analytics['engagement_stats']['approved_comments']) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Bekleyen Yorum</span>
                            <span
                                class="text-sm text-orange-500">{{ number_format($analytics['engagement_stats']['pending_comments']) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Yorum Oranı</span>
                            <span class="text-sm text-gray-500">
                                {{ $analytics['total_views'] > 0 ? round(($analytics['engagement_stats']['comments'] / $analytics['total_views']) * 100, 2) : 0 }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <x-csp-script src="https://cdn.jsdelivr.net/npm/chart.js" />
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Views by Date Chart
            const viewsData = @json($analytics['views_by_date']);
            const ctx = document.getElementById('viewsChart').getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: viewsData.map(item => new Date(item.date).toLocaleDateString('tr-TR')),
                    datasets: [{
                        label: 'Görüntülenme',
                        data: viewsData.map(item => item.views),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(156, 163, 175, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(156, 163, 175, 0.1)'
                            }
                        }
                    }
                }
            });
        });
    </script>
@endsection
