@extends('admin.layouts.admin')

@section('title', 'UPS Analytics')

@section('content')
    <div class="min-h-screen bg-gray-50 dark:bg-slate-900 py-6" x-data="analyticsPage()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <nav class="flex mb-4" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-2">
                        <li>
                            <a href="{{ route('admin.property-hub.index') }}"
                                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-all duration-200">
                                Property Hub
                            </a>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-2 text-gray-900 dark:text-slate-100 font-medium">Analytics</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100">📊 UPS Analytics</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Feature kullanım istatistikleri ve sistem performansı
                </p>
            </div>

            <!-- Usage Heatmap -->
            <div
                class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 mb-8 border border-gray-200 dark:border-slate-800 dark:shadow-none">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">🔥 Feature Usage Heatmap</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Her hücre, ilgili kategorideki atama sayısını
                    gösterir</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="text-left text-sm font-medium text-gray-500 dark:text-gray-400 pb-3">Feature</th>
                                @foreach ($categories as $category)
                                    <th class="text-center text-sm font-medium text-gray-500 dark:text-gray-400 pb-3 px-2">
                                        {{ Str::limit($category->name, 10) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topFeatures as $feature)
                                <tr class="border-t border-gray-200 dark:border-slate-800">
                                    <td class="py-2 text-sm text-gray-900 dark:text-slate-100">
                                        {{ Str::limit($feature->name, 25) }}
                                    </td>
                                    @foreach ($categories as $category)
                                        @php
                                            $count = $heatmapData[$feature->id][$category->id] ?? 0;
                                            $intensity = min(100, $count * 20);
                                        @endphp
                                        <td class="text-center px-2">
                                            <div class="w-8 h-8 mx-auto rounded flex items-center justify-center text-xs font-medium transition-all duration-200"
                                                style="background-color: rgba(59, 130, 246, {{ $intensity / 100 }}); color: {{ $intensity > 50 ? 'white' : 'inherit' }}">
                                                {{ $count }}
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Assignment Coverage & Orphaned Features -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">📈 Assignment Coverage</h3>
                    @foreach ($coverageStats as $stat)
                        <div class="mb-4">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">{{ $stat['name'] }}</span>
                                <span class="font-medium text-gray-900 dark:text-slate-100">{{ $stat['percentage'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-slate-800 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all duration-500
                            @if ($stat['percentage'] >= 80) bg-green-500
                            @elseif($stat['percentage'] >= 50) bg-yellow-500
                            @else bg-red-500 @endif"
                                    style="width: {{ $stat['percentage'] }}%">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Orphaned Features -->
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">⚠️ Kullanılmayan Özellikler</h3>
                    @if ($orphanedFeatures->isEmpty())
                        <div class="text-center py-8">
                            <div class="text-4xl mb-2">✅</div>
                            <p class="text-gray-500 dark:text-gray-400">Tüm özellikler kullanımda!</p>
                        </div>
                    @else
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @foreach ($orphanedFeatures as $feature)
                                <div
                                    class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg transition-all duration-200 hover:bg-red-100 dark:hover:bg-red-900/30">
                                    <div>
                                        <span class="font-medium text-gray-900 dark:text-slate-100">{{ $feature->name }}</span>
                                        <span
                                            class="text-sm text-gray-500 dark:text-gray-400 ml-2">{{ $feature->slug }}</span>
                                    </div>
                                    <div class="flex gap-2">
                                        <button @click="assignFeature({{ $feature->id }})"
                                            class="px-3 py-1 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded transition-all duration-200">
                                            Ata
                                        </button>
                                        <button @click="archiveFeature({{ $feature->id }})"
                                            class="px-3 py-1 text-sm bg-gray-600 hover:bg-gray-700 text-white rounded transition-all duration-200">
                                            Arşivle
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">⚡ Performance Metrics</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div
                        class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-slate-900">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                            {{ number_format($metrics['avg_query_time'], 2) }}ms
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ortalama Query Süresi</div>
                    </div>
                    <div
                        class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-slate-900">
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                            {{ $metrics['cache_hit_rate'] }}%
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Cache Hit Rate</div>
                    </div>
                    <div
                        class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-slate-900">
                        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                            {{ $metrics['templates_created_today'] }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Bugün Oluşturulan</div>
                    </div>
                    <div
                        class="text-center p-4 bg-gray-50 dark:bg-slate-950/50 rounded-lg transition-all duration-200 hover:bg-gray-100 dark:hover:bg-slate-800">
                        <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                            {{ $metrics['ai_suggestions_accepted'] }}%
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">AI Öneri Kabul Oranı</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function analyticsPage() {
                return {
                    async assignFeature(featureId) {
                        // Redirect to template manager with feature preselected
                        window.location.href = '{{ route('admin.property-hub.templates.index') }}?assign_feature=' +
                            featureId;
                    },

                    async archiveFeature(featureId) {
                        if (!confirm('Bu özelliği arşivlemek istediğinize emin misiniz?')) return;

                        try {
                            const response = await fetch(`/admin/property-hub/features/${featureId}/archive`, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                }
                            });

                            const data = await response.json();

                            if (data.success) {
                                if (window.toast) {
                                    window.toast.success('Özellik arşivlendi');
                                }
                                location.reload();
                            } else {
                                if (window.toast) {
                                    window.toast.error(data.message || 'İşlem başarısız');
                                }
                            }
                        } catch (error) {
                            if (window.toast) {
                                window.toast.error('İşlem başarısız: ' + error.message);
                            }
                        }
                    }
                }
            }
        </script>
    @endpush
@endsection
