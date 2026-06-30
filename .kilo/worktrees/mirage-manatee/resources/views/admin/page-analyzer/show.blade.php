@extends('admin.layouts.admin')

@section('title', 'Analysis Session Details - Yalıhan Emlak Pro')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center text-gray-800 dark:text-slate-200">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-chart-bar text-white text-xl"></i>
                    </div>
                    Analysis Session Details
                </h1>
                <p class="text-lg text-gray-600 mt-2">Detailed analysis results and findings</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.page-analyzer.edit', $specificResult['id'] ?? 1) }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg dark:shadow-none">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Session
                </a>
                <a href="{{ route('admin.page-analyzer.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:text-slate-300">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Sessions
                </a>
            </div>
        </div>
    </div>

    <div class="px-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Session Overview -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Session Overview</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Session Name</label>
                            <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ $specificResult['name'] ?? 'Unnamed Session' }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Analysis Type</label>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                {{ ucfirst($specificResult['type'] ?? 'unknown') }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Pages Analyzed</label>
                            <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ $specificResult['pages_count'] ?? 0 }} pages</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Average Score</label>
                            <div class="text-lg font-semibold
                                {{ ($specificResult['average_score'] ?? 0) >= 8 ? 'text-green-600' :
                                   (($specificResult['average_score'] ?? 0) >= 6 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ number_format($specificResult['average_score'] ?? 0, 1) }}/10
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Critical Issues</label>
                            <div class="text-lg font-semibold text-red-600">{{ $specificResult['critical_count'] ?? 0 }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Warnings</label>
                            <div class="text-lg font-semibold text-yellow-600">{{ $specificResult['warning_count'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                @if($specificResult['description'] ?? null)
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Description</h2>
                        <p class="text-gray-700 leading-relaxed dark:text-slate-300">{{ $specificResult['description'] }}</p>
                    </div>
                @endif

                <!-- Analysis Results -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Analysis Results</h2>

                    @if(isset($specificResult['pages']) && count($specificResult['pages']) > 0)
                        <div class="space-y-4">
                            @foreach($specificResult['pages'] as $page)
                                <div class="border border-gray-200 rounded-lg p-4 dark:border-slate-700">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ $page['name'] ?? 'Unknown Page' }}</h3>
                                        <span class="px-3 py-1 rounded-full text-sm font-medium
                                            {{ ($page['score'] ?? 0) >= 8 ? 'bg-green-100 text-green-800' :
                                               (($page['score'] ?? 0) >= 6 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ number_format($page['score'] ?? 0, 1) }}/10
                                        </span>
                                    </div>

                                    <div class="text-sm text-gray-600 mb-2">
                                        <span class="font-medium">Category:</span> {{ $page['category'] ?? 'Unknown' }}
                                    </div>

                                    @if(isset($page['issues']) && count($page['issues']) > 0)
                                        <div class="mt-3">
                                            <h4 class="text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">Issues Found:</h4>
                                            <ul class="list-disc list-inside space-y-1">
                                                @foreach($page['issues'] as $issue)
                                                    <li class="text-sm
                                                        {{ $issue['severity'] === 'critical' ? 'text-red-600' :
                                                           ($issue['severity'] === 'warning' ? 'text-yellow-600' : 'text-blue-600') }}">
                                                        {{ $issue['message'] ?? 'Unknown issue' }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @else
                                        <div class="text-sm text-green-600 mt-2">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            No issues found
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-search text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">No analysis results available</p>
                        </div>
                    @endif
                </div>

                <!-- Recommendations -->
                @if(isset($specificResult['recommendations']) && count($specificResult['recommendations']) > 0)
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Recommendations</h2>
                        <ul class="space-y-3">
                            @foreach($specificResult['recommendations'] as $recommendation)
                                <li class="flex items-start">
                                    <i class="fas fa-lightbulb text-yellow-500 mt-1 mr-3"></i>
                                    <span class="text-gray-700 dark:text-slate-300">{{ $recommendation }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Quick Actions</h3>
                    <div class="space-y-3">
                        <button onclick="exportSession({{ $specificResult['id'] ?? 1 }}, 'pdf')"
                                class="w-full flex items-center justify-center px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                            <i class="fas fa-file-pdf mr-2"></i>
                            Export PDF
                        </button>

                        <button onclick="exportSession({{ $specificResult['id'] ?? 1 }}, 'excel')"
                                class="w-full flex items-center justify-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                            <i class="fas fa-file-excel mr-2"></i>
                            Export Excel
                        </button>

                        <button onclick="rerunAnalysis({{ $specificResult['id'] ?? 1 }})"
                                class="w-full flex items-center justify-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                            <i class="fas fa-redo mr-2"></i>
                            Re-run Analysis
                        </button>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Statistics</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Total Pages</span>
                            <span class="text-lg font-semibold text-blue-600">{{ $specificResult['pages_count'] ?? 0 }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Critical Issues</span>
                            <span class="text-lg font-semibold text-red-600">{{ $specificResult['critical_count'] ?? 0 }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Warnings</span>
                            <span class="text-lg font-semibold text-yellow-600">{{ $specificResult['warning_count'] ?? 0 }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Success Count</span>
                            <span class="text-lg font-semibold text-green-600">{{ $specificResult['success_count'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <!-- Timestamps -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Timestamps</h3>
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm text-gray-600">Created</span>
                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                {{ $specificResult['created_at'] ?? 'N/A' }}
                            </div>
                        </div>

                        <div>
                            <span class="text-sm text-gray-600">Last Updated</span>
                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                {{ $specificResult['updated_at'] ?? 'N/A' }}
                            </div>
                        </div>

                        <div>
                            <span class="text-sm text-gray-600">Analysis Duration</span>
                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                {{ $specificResult['duration'] ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function exportSession(id, format) {
    window.open(`/admin/page-analyzer/export/${id}?format=${format}`, '_blank');
}

function rerunAnalysis(id) {
    if (confirm('Are you sure you want to re-run this analysis?')) {
        fetch(`/admin/page-analyzer/rerun/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error re-running analysis: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error re-running analysis');
        });
    }
}
</script>
@endpush
