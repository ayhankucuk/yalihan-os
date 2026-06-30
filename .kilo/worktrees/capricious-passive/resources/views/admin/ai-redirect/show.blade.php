@extends('admin.layouts.admin')

@section('title', 'AI Redirect Details - Yalıhan Emlak Pro')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center text-gray-800 dark:text-slate-200">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-route text-white text-xl"></i>
                    </div>
                    AI Redirect Details
                </h1>
                <p class="text-lg text-gray-600 mt-2">Redirect configuration and analytics</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.ai-redirect.edit', $redirectData['id']) }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg dark:shadow-none">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Redirect
                </a>
                <a href="{{ route('admin.ai-redirect.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:text-slate-300">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Redirects
                </a>
            </div>
        </div>
    </div>

    <div class="px-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Redirect Information -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Redirect Information</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Redirect Name</label>
                            <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ $redirectData['name'] ?? 'N/A' }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Target Route</label>
                            <div class="text-sm text-gray-600 bg-gray-50 px-4 py-2.5 rounded dark:bg-slate-900">{{ $redirectData['target_route'] ?? 'N/A' }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Description</label>
                            <div class="text-gray-700 dark:text-slate-300">{{ $redirectData['description'] ?? 'No description' }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Status</label>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>
                                Active
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Analytics -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Analytics</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Total Uses</label>
                            <div class="text-2xl font-bold text-blue-600">{{ $redirectData['analytics']['total_uses'] ?? 0 }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Success Rate</label>
                            <div class="text-2xl font-bold text-green-600">{{ $redirectData['analytics']['success_rate'] ?? 0 }}%</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Last Used</label>
                            <div class="text-sm text-gray-600">{{ $redirectData['analytics']['last_used'] ?? 'Never' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Usage Chart -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Usage Over Time</h2>

                    <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg dark:bg-slate-900">
                        <div class="text-center">
                            <i class="fas fa-chart-line text-4xl text-gray-400 mb-4"></i>
                            <p class="text-gray-500">Usage chart would be displayed here</p>
                            <p class="text-sm text-gray-400">Chart integration pending</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="{{ route('admin.ai-redirect.edit', $redirectData['id']) }}"
                           class="w-full flex items-center justify-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                            <i class="fas fa-edit mr-2"></i>
                            Edit Redirect
                        </a>

                        <button onclick="testRedirect()"
                                class="w-full flex items-center justify-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                            <i class="fas fa-play mr-2"></i>
                            Test Redirect
                        </button>

                        <button onclick="deleteRedirect({{ $redirectData['id'] }})"
                                class="w-full flex items-center justify-center px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                            <i class="fas fa-trash mr-2"></i>
                            Delete Redirect
                        </button>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Statistics</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Total Uses</span>
                            <span class="text-lg font-semibold text-blue-600">{{ $redirectData['analytics']['total_uses'] ?? 0 }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Success Rate</span>
                            <span class="text-lg font-semibold text-green-600">{{ $redirectData['analytics']['success_rate'] ?? 0 }}%</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Avg Response Time</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">45ms</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Last Updated</span>
                            <span class="text-sm text-gray-600">{{ $redirectData['updated_at'] ?? 'N/A' }}</span>
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
                                {{ $redirectData['created_at'] ?? 'N/A' }}
                            </div>
                        </div>

                        <div>
                            <span class="text-sm text-gray-600">Last Updated</span>
                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                {{ $redirectData['updated_at'] ?? 'N/A' }}
                            </div>
                        </div>

                        <div>
                            <span class="text-sm text-gray-600">Last Used</span>
                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                {{ $redirectData['analytics']['last_used'] ?? 'Never' }}
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
function testRedirect() {
    // Test the redirect functionality
    window.open('{{ route($redirectData["target_route"] ?? "admin.dashboard") }}', '_blank');
}

function deleteRedirect(id) {
    if (confirm('Are you sure you want to delete this redirect configuration?')) {
        fetch(`/admin/ai-redirect/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '{{ route("admin.ai-redirect.index") }}';
            } else {
                alert('Error deleting redirect: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting redirect');
        });
    }
}
</script>
@endpush
