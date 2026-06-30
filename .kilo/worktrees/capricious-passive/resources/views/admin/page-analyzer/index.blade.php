@extends('admin.layouts.admin')

@section('title', 'Page Analyzer Sessions - Yalıhan Emlak Pro')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center text-gray-800 dark:text-slate-200">
                    <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-search text-white text-xl"></i>
                    </div>
                    Page Analyzer Sessions
                </h1>
                <p class="text-lg text-gray-600 mt-2">Manage and view analysis sessions</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.page-analyzer.create') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none">
                    <i class="fas fa-plus mr-2"></i>
                    New Analysis
                </a>
                <a href="{{ route('admin.page-analyzer.dashboard') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    Live Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="px-6">
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2.5 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2.5 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <button onclick="runQuickAnalysis('complete')"
                        class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none">
                    <i class="fas fa-search mr-2"></i>
                    Complete Analysis
                </button>
                <button onclick="runQuickAnalysis('performance')"
                        class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    Performance Check
                </button>
                <button onclick="runQuickAnalysis('security')"
                        class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-yellow-600 to-orange-600 rounded-lg hover:from-yellow-700 hover:to-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none">
                    <i class="fas fa-shield-alt mr-2"></i>
                    Security Scan
                </button>
                <button onclick="exportResults()"
                        class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-cyan-600 to-blue-600 rounded-lg hover:from-cyan-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none">
                    <i class="fas fa-download mr-2"></i>
                    Export Results
                </button>
            </div>

            <!-- Analysis Sessions -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Session Name
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Analysis Type
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pages Analyzed
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Average Score
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-slate-900">
                        @forelse ($results['sessions'] ?? [] as $session)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                    {{ $session['id'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $session['name'] ?? 'Unnamed Session' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        {{ $session['type'] === 'complete' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($session['type'] ?? 'unknown') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $session['pages_count'] ?? 0 }} pages
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex items-center">
                                        <span class="text-lg font-semibold
                                            {{ ($session['average_score'] ?? 0) >= 8 ? 'text-green-600' :
                                               (($session['average_score'] ?? 0) >= 6 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ number_format($session['average_score'] ?? 0, 1) }}/10
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $session['created_at'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('admin.page-analyzer.show', $session['id'] ?? 1) }}"
                                       class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                                    <a href="{{ route('admin.page-analyzer.edit', $session['id'] ?? 1) }}"
                                       class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                    <button onclick="deleteSession({{ $session['id'] ?? 1 }})"
                                            class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    <div class="flex flex-col items-center py-8">
                                        <i class="fas fa-search text-gray-400 text-4xl mb-4"></i>
                                        <p class="text-lg font-medium text-gray-900 mb-2 dark:text-slate-100 dark:text-white">No analysis sessions found</p>
                                        <p class="text-gray-500 mb-4">Create your first analysis session to get started</p>
                                        <a href="{{ route('admin.page-analyzer.create') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none">
                                            <i class="fas fa-plus mr-2"></i>
                                            Create Analysis Session
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Statistics -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chart-line text-2xl text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Sessions</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ count($results['sessions'] ?? []) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-globe text-2xl text-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Pages Analyzed</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ $results['total_pages'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Critical Issues</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ $results['critical_count'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-star text-2xl text-yellow-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Average Score</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ number_format($results['average_score'] ?? 0, 1) }}/10</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function runQuickAnalysis(type) {
    // Redirect to dashboard with analysis type
    window.location.href = `{{ route('admin.page-analyzer.dashboard') }}?type=${type}`;
}

function exportResults() {
    // Export functionality
    window.location.href = `{{ route('admin.page-analyzer.api.export') }}?format=pdf`;
}

function deleteSession(id) {
    if (confirm('Are you sure you want to delete this analysis session?')) {
        fetch(`/admin/page-analyzer/${id}`, {
            method: 'DELETE',
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
                alert('Error deleting session: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting session');
        });
    }
}
</script>
@endpush
