@extends('admin.layouts.admin')

@section('title', 'Create Analysis Session - Yalıhan Emlak Pro')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center text-gray-800 dark:text-slate-200">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-plus text-white text-xl"></i>
                    </div>
                    Create Analysis Session
                </h1>
                <p class="text-lg text-gray-600 mt-2">Configure and run a new page analysis</p>
            </div>
            <a href="{{ route('admin.page-analyzer.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:text-slate-300">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Sessions
            </a>
        </div>
    </div>

    <div class="px-6">
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200 dark:border-slate-800 dark:bg-slate-900 p-6 max-w-2xl dark:shadow-none dark:border-slate-700">
            <form action="{{ route('admin.page-analyzer.store') }}" method="POST" id="analysisForm">
                @csrf

                <!-- Session Name -->
                <div class="mb-6">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                        Session Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', 'Analysis Session - ' . now()->format('Y-m-d H:i')) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                           placeholder="Enter session name"
                           required>
                    @error('name')
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
                              placeholder="Enter analysis description">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Analysis Type -->
                <div class="mb-6">
                    <label for="analysis_type" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                        Analysis Type <span class="text-red-500">*</span>
                    </label>
                    <select style="color-scheme: light dark;" id="analysis_type"
                            name="analysis_type"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('analysis_type') border-red-500 @enderror transition-all duration-200"
                            required>
                        <option value="">Select Analysis Type</option>
                        <option value="complete" {{ old('analysis_type') === 'complete' ? 'selected' : '' }}>Complete Analysis</option>
                        <option value="performance" {{ old('analysis_type') === 'performance' ? 'selected' : '' }}>Performance Analysis</option>
                        <option value="security" {{ old('analysis_type') === 'security' ? 'selected' : '' }}>Security Analysis</option>
                        <option value="partial" {{ old('analysis_type') === 'partial' ? 'selected' : '' }}>Partial Analysis</option>
                        <option value="single" {{ old('analysis_type') === 'single' ? 'selected' : '' }}>Single Page Analysis</option>
                    </select>
                    @error('analysis_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Target Pages -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                        Target Pages
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="target_pages[]" value="all" checked
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:shadow-none">
                            <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">All Admin Pages</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="target_pages[]" value="ilan"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:shadow-none">
                            <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">İlan Management Pages</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="target_pages[]" value="crm"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:shadow-none">
                            <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">CRM Pages</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="target_pages[]" value="settings"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:shadow-none">
                            <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Settings Pages</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="target_pages[]" value="analytics"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:shadow-none">
                            <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Analytics Pages</span>
                        </label>
                    </div>
                </div>

                <!-- Analysis Options -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 dark:text-slate-100 dark:text-white">Analysis Options</h3>

                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="options[]" value="check_methods" checked
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:shadow-none">
                            <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Check CRUD Methods</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="options[]" value="check_views" checked
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:shadow-none">
                            <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Check View Files</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="options[]" value="check_routes" checked
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:shadow-none">
                            <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Check Routes</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="options[]" value="check_performance"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:shadow-none">
                            <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Performance Analysis</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="options[]" value="check_security"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:shadow-none">
                            <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Security Check</span>
                        </label>
                    </div>
                </div>

                <!-- Auto-run Option -->
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="auto_run" value="1" checked
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:shadow-none">
                        <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">Run analysis immediately after creation</span>
                    </label>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-slate-700">
                    <a href="{{ route('admin.page-analyzer.index') }}"
                       class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors dark:text-slate-300">
                        Cancel
                    </a>
                    <button type="submit"
                            id="page-analyzer-create-submit-btn"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            onsubmit="const btn = document.getElementById('page-analyzer-create-submit-btn'); const icon = document.getElementById('page-analyzer-create-submit-icon'); const text = document.getElementById('page-analyzer-create-submit-text'); const spinner = document.getElementById('page-analyzer-create-submit-spinner'); if(btn && icon && text && spinner) { btn.disabled = true; icon.classList.add('hidden'); spinner.classList.remove('hidden'); text.textContent = 'Creating Analysis...'; }">
                        <svg id="page-analyzer-create-submit-icon" class="fas fa-play mr-2"></svg>
                        <svg id="page-analyzer-create-submit-spinner" class="hidden w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="page-analyzer-create-submit-text">Create & Run Analysis</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
// Context7: Improved loading state with proper error handling
document.getElementById('analysisForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('page-analyzer-create-submit-btn');
    const icon = document.getElementById('page-analyzer-create-submit-icon');
    const text = document.getElementById('page-analyzer-create-submit-text');
    const spinner = document.getElementById('page-analyzer-create-submit-spinner');

    if (submitBtn && icon && text && spinner) {
        submitBtn.disabled = true;
        icon.classList.add('hidden');
        spinner.classList.remove('hidden');
        text.textContent = 'Creating Analysis...';
    }

    // Re-enable after 10 seconds as fallback (in case of error)
    setTimeout(() => {
        if (submitBtn && icon && text && spinner) {
            submitBtn.disabled = false;
            icon.classList.remove('hidden');
            spinner.classList.add('hidden');
            text.textContent = 'Create & Run Analysis';
        }
    }, 10000);
});

// Auto-update session name based on type
document.getElementById('analysis_type').addEventListener('change', function() {
    const type = this.value;
    const nameField = document.getElementById('name');

    if (type && nameField.value.includes('Analysis Session')) {
        const timestamp = new Date().toLocaleString('tr-TR');
        nameField.value = `${type.charAt(0).toUpperCase() + type.slice(1)} Analysis - ${timestamp}`;
    }
});
</script>
@endpush
