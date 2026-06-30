@extends('admin.layouts.admin')

@section('title', 'Edit AI Redirect - Yalıhan Emlak Pro')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center text-gray-800 dark:text-slate-200">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-edit text-white text-xl"></i>
                    </div>
                    Edit AI Redirect
                </h1>
                <p class="text-lg text-gray-600 mt-2">Update redirect configuration</p>
            </div>
            <a href="{{ route('admin.ai-redirect.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:text-slate-300">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Redirects
            </a>
        </div>
    </div>

    <div class="px-6">
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 max-w-2xl dark:shadow-none dark:border-slate-700">
            <form action="{{ route('admin.ai-redirect.update', $config['id']) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Redirect Name -->
                <div class="mb-6">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                        Redirect Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', $config['name'] ?? 'AI Redirect Configuration') }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                           placeholder="Enter redirect name"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Target Route -->
                <div class="mb-6">
                    <label for="target_route" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                        Target Route <span class="text-red-500">*</span>
                    </label>
                    <select style="color-scheme: light dark;" id="target_route"
                            name="target_route"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('target_route') border-red-500 @enderror transition-all duration-200"
                            required>
                        <option value="">Select Target Route</option>
                        <option value="admin.ai-settings.index" {{ old('target_route', $config['target_route'] ?? '') === 'admin.ai-settings.index' ? 'selected' : '' }}>AI Settings</option>
                        <option value="admin.ai.advanced-dashboard" {{ old('target_route', $config['target_route'] ?? '') === 'admin.ai.advanced-dashboard' ? 'selected' : '' }}>AI Dashboard</option>
                        <option value="admin.danisman-ai.index" {{ old('target_route', $config['target_route'] ?? '') === 'admin.danisman-ai.index' ? 'selected' : '' }}>Danışman AI</option>
                        <option value="admin.page-analyzer.dashboard" {{ old('target_route', $config['target_route'] ?? '') === 'admin.page-analyzer.dashboard' ? 'selected' : '' }}>Page Analyzer</option>
                        <option value="admin.analytics.index" {{ old('target_route', $config['target_route'] ?? '') === 'admin.analytics.index' ? 'selected' : '' }}>Analytics</option>
                    </select>
                    @error('target_route')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                        Description
                    </label>
                    <textarea id="description"
                              name="description"
                              rows="3"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @enderror"
                              placeholder="Enter redirect description">{{ old('description', $config['description'] ?? '') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Advanced Options -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 dark:text-slate-100 dark:text-white">Advanced Options</h3>

                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="enable_analytics" value="1"
                                   {{ ($config['options']['enable_analytics'] ?? true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:shadow-none">
                            <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Enable Analytics Tracking</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="cache_status" value="1"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:shadow-none">
                            <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Enable Caching</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="redirect_immediately" value="1" checked
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:shadow-none">
                            <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Redirect Immediately</span>
                        </label>
                    </div>
                </div>

                <!-- Cache Duration -->
                <div class="mb-6">
                    <label for="cache_duration" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                        Cache Duration (seconds)
                    </label>
                    <input type="number"
                           id="cache_duration"
                           name="cache_duration"
                           value="{{ old('cache_duration', $config['options']['cache_duration'] ?? 3600) }}"
                           min="0"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('cache_duration') border-red-500 @enderror"
                           placeholder="3600">
                    <p class="mt-1 text-sm text-gray-500">How long to cache the redirect (0 = no cache)</p>
                    @error('cache_duration')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Fallback Route -->
                <div class="mb-6">
                    <label for="fallback_route" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                        Fallback Route
                    </label>
                    <select style="color-scheme: light dark;" id="fallback_route"
                            name="fallback_route"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('fallback_route') border-red-500 @enderror transition-all duration-200">
                        <option value="">Select Fallback Route</option>
                        <option value="admin.dashboard" {{ old('fallback_route', $config['options']['fallback_route'] ?? '') === 'admin.dashboard' ? 'selected' : '' }}>Admin Dashboard</option>
                        <option value="admin.ai-settings.index" {{ old('fallback_route', $config['options']['fallback_route'] ?? '') === 'admin.ai-settings.index' ? 'selected' : '' }}>AI Settings</option>
                        <option value="admin.page-analyzer.dashboard" {{ old('fallback_route', $config['options']['fallback_route'] ?? '') === 'admin.page-analyzer.dashboard' ? 'selected' : '' }}>Page Analyzer</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">Route to redirect to if the main target fails</p>
                    @error('fallback_route')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Current Status -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 dark:text-slate-100 dark:text-white">Current Status</h3>

                    <div class="bg-gray-50 rounded-lg p-4 dark:bg-slate-900">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="font-medium text-gray-700 dark:text-slate-300">Redirect ID:</span>
                                <span class="text-gray-600">#{{ $config['id'] ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700 dark:text-slate-300">Status:</span>
                                <span class="text-green-600 font-semibold">Active</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700 dark:text-slate-300">Created:</span>
                                <span class="text-gray-600">{{ $config['created_at'] ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700 dark:text-slate-300">Last Updated:</span>
                                <span class="text-gray-600">{{ $config['updated_at'] ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-slate-700">
                    <a href="{{ route('admin.ai-redirect.index') }}"
                       class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors dark:text-slate-300">
                        Cancel
                    </a>
                    <button type="submit"
                            id="ai-redirect-edit-submit-btn"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            onsubmit="const btn = document.getElementById('ai-redirect-edit-submit-btn'); const icon = document.getElementById('ai-redirect-edit-submit-icon'); const text = document.getElementById('ai-redirect-edit-submit-text'); const spinner = document.getElementById('ai-redirect-edit-submit-spinner'); if(btn && icon && text && spinner) { btn.disabled = true; icon.classList.add('hidden'); spinner.classList.remove('hidden'); text.textContent = 'Updating...'; }">
                        <svg id="ai-redirect-edit-submit-icon" class="fas fa-save mr-2"></svg>
                        <svg id="ai-redirect-edit-submit-spinner" class="hidden w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="ai-redirect-edit-submit-text">Update Redirect</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
// Context7: Improved loading state with proper error handling
document.getElementById('editForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('ai-redirect-edit-submit-btn');
    const icon = document.getElementById('ai-redirect-edit-submit-icon');
    const text = document.getElementById('ai-redirect-edit-submit-text');
    const spinner = document.getElementById('ai-redirect-edit-submit-spinner');

    if (submitBtn && icon && text && spinner) {
        submitBtn.disabled = true;
        icon.classList.add('hidden');
        spinner.classList.remove('hidden');
        text.textContent = 'Updating...';
    }

    // Re-enable after 10 seconds as fallback (in case of error)
    setTimeout(() => {
        if (submitBtn && icon && text && spinner) {
            submitBtn.disabled = false;
            icon.classList.remove('hidden');
            spinner.classList.add('hidden');
            text.textContent = 'Update Redirect';
        }
    }, 10000);
});
</script>
@endpush
