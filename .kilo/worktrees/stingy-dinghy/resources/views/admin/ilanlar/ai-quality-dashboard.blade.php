@extends('admin.layouts.admin')

@section('title', 'AI Kalite & Şablon Analizi')

@section('content')
    <div class="container mx-auto px-4 py-6">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                    AI Kalite & Şablon Analizi
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    <span
                        class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        📊 Advisory Only - Read-Only Dashboard
                    </span>
                </p>
            </div>

            {{-- Time Range Selector --}}
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">Dönem:</label>
                <select id="daysFilter"
                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                    <option value="7" {{ $days == 7 ? 'selected' : '' }}>Son 7 Gün</option>
                    <option value="30" {{ $days == 30 ? 'selected' : '' }}>Son 30 Gün</option>
                    <option value="90" {{ $days == 90 ? 'selected' : '' }}>Son 90 Gün</option>
                </select>
            </div>
        </div>

        @if (isset($dashboard['error']))
            {{-- Error State --}}
            <div
                class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 px-4 py-3 rounded-lg">
                <p class="font-bold">Hata</p>
                <p>{{ $dashboard['error'] }}</p>
            </div>
        @else
            {{-- Overview Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                {{-- Avg Quality Score --}}
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Ortalama Kalite Skoru
                    </div>
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $dashboard['overview']['avg_quality_score'] }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                        / 100
                    </div>
                </div>

                {{-- Total Checks --}}
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Toplam Kontrol
                    </div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                        {{ number_format($dashboard['overview']['total_checks']) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                        kalite kontrolü
                    </div>
                </div>

                {{-- Success Rate --}}
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Başarı Oranı
                    </div>
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ number_format($dashboard['overview']['success_rate'] * 100, 1) }}%
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                        yayınlanan
                    </div>
                </div>

                {{-- Block Rate --}}
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Bloke Oranı
                    </div>
                    <div class="text-3xl font-bold text-red-600 dark:text-red-400">
                        {{ number_format($dashboard['overview']['block_rate'] * 100, 1) }}%
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                        engellenen
                    </div>
                </div>
            </div>

            {{-- Main Content Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Common Issues --}}
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                        🔍 Sık Karşılaşılan Hatalar
                    </h2>

                    @if (count($dashboard['common_issues']) > 0)
                        <div class="space-y-3">
                            @foreach ($dashboard['common_issues'] as $issue)
                                <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg dark:bg-slate-900">
                                    <div
                                        class="flex-shrink-0 w-12 h-12 flex items-center justify-center bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-300 rounded-full font-bold">
                                        {{ $issue['percentage'] }}%
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                            {{ $issue['code'] }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $issue['advice'] }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                            {{ $issue['count'] }} ilan
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            Henüz veri yok
                        </p>
                    @endif
                </div>

                {{-- Template Insights --}}
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                        💡 Şablon Önerileri
                    </h2>

                    @if (count($dashboard['template_insights']['advice']) > 0)
                        <div class="space-y-3">
                            @foreach ($dashboard['template_insights']['advice'] as $advice)
                                <div
                                    class="flex items-start gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                    <div class="flex-shrink-0 text-2xl">
                                        @if ($advice['priority'] == 'high')
                                            🔥
                                        @elseif($advice['priority'] == 'medium')
                                            ⚠️
                                        @else
                                            ℹ️
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                            {{ ucfirst($advice['category']) }}
                                        </div>
                                        <div class="text-sm text-gray-700 dark:text-slate-200 mt-1 dark:text-slate-300">
                                            {{ $advice['message'] }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            Henüz öneri yok
                        </p>
                    @endif
                </div>
            </div>

            {{-- Title Patterns --}}
            @if (isset($dashboard['template_insights']['title_patterns']['recommendations']))
                <div
                    class="mt-6 bg-white dark:bg-slate-900 rounded-lg shadow p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                        📝 Başlık Önerileri
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($dashboard['template_insights']['title_patterns']['recommendations'] as $tip)
                            <div class="flex items-start gap-2 text-sm">
                                <span class="text-green-600 dark:text-green-400">✓</span>
                                <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $tip }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Description Patterns --}}
            @if (isset($dashboard['template_insights']['description_structure']['structure_tips']))
                <div
                    class="mt-6 bg-white dark:bg-slate-900 rounded-lg shadow p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                        📄 Açıklama Yapısı Önerileri
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($dashboard['template_insights']['description_structure']['structure_tips'] as $tip)
                            <div class="flex items-start gap-2 text-sm">
                                <span class="text-blue-600 dark:text-blue-400">✓</span>
                                <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $tip }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Recommendations from Learning --}}
            @if (count($dashboard['recommendations']) > 0)
                <div
                    class="mt-6 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg shadow p-6 border border-purple-200 dark:border-purple-800 dark:shadow-none">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                        🎯 AI Öğrenme Önerileri
                    </h2>
                    <div class="space-y-2">
                        @foreach ($dashboard['recommendations'] as $rec)
                            <div class="flex items-start gap-2 text-sm">
                                <span class="text-purple-600 dark:text-purple-400 font-bold">→</span>
                                <span
                                    class="text-gray-800 dark:text-slate-200">{{ $rec['message'] ?? json_encode($rec) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>

    @push('scripts')
        <script>
            // Time range filter (passive reload)
            document.getElementById('daysFilter')?.addEventListener('change', function() {
                const days = this.value;
                const url = new URL(window.location.href);
                url.searchParams.set('days', days);
                window.location.href = url.toString();
            });
        </script>
    @endpush
@endsection
