@extends('admin.layouts.admin')

@section('title', 'Create Etiket - Yalıhan Emlak Pro')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center text-gray-800 dark:text-slate-200">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-plus text-white text-xl"></i>
                    </div>
                    Create Etiket
                </h1>
                <p class="text-lg text-gray-600 mt-2">Create a new system label or tag</p>
            </div>
            <a href="{{ route('admin.etiket.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg border border-gray-700 hover:border-gray-600 transition-all duration-200 font-medium shadow-sm hover:shadow-md dark:shadow-none">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Etiketler
            </a>
        </div>
    </div>

    <div class="px-6">
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 max-w-2xl dark:shadow-none dark:border-slate-700">
            <form action="{{ route('admin.etiket.store') }}" method="POST"
                  onsubmit="const btn = document.getElementById('etiket-submit-btn'); const icon = document.getElementById('etiket-submit-icon'); const text = document.getElementById('etiket-submit-text'); const spinner = document.getElementById('etiket-submit-spinner'); if(btn && icon && text && spinner) { btn.disabled = true; icon.classList.add('hidden'); spinner.classList.remove('hidden'); text.textContent = 'Kaydediliyor...'; }">
                @csrf

                <!-- Name -->
                <div class="mb-6">
                    <label for="name" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Etiket Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 @error('name') border-red-500 @enderror dark:text-slate-100"
                           placeholder="Enter etiket name"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Description
                    </label>
                    <textarea id="description"
                              name="description"
                              rows="3"
                              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 @error('description') border-red-500 @enderror dark:text-slate-100"
                              placeholder="Enter etiket description">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Color -->
                <div class="mb-6">
                    <label for="color" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                        Color
                    </label>
                    <div class="flex items-center gap-4">
                        <input type="color"
                               id="color"
                               name="color"
                               value="{{ old('color', '#3B82F6') }}"
                               class="w-16 h-10 border border-gray-300 rounded cursor-pointer @error('color') border-red-500 @enderror">
                        <input type="text"
                               id="color_text"
                               value="{{ old('color', '#3B82F6') }}"
                               class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="#3B82F6"
                               pattern="^#[0-9A-Fa-f]{6}$">
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Choose a color for this etiket</p>
                    @error('color')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Icon -->
                <div class="mb-6">
                    <label for="icon" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Icon Class
                    </label>
                    <input type="text"
                           id="icon"
                           name="icon"
                           value="{{ old('icon') }}"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 @error('icon') border-red-500 @enderror dark:text-slate-100"
                           placeholder="fas fa-tag">
                    <p class="mt-1 text-sm text-gray-500">FontAwesome icon class (e.g., fas fa-tag, fas fa-star)</p>
                    @error('icon')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div class="mb-6">
                    <label for="status" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Status <span class="text-red-500">*</span>
                    </label>
                        <select style="color-scheme: light dark;" id="aktiflik_durumu"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 @error('aktiflik_durumu') border-red-500 @enderror dark:text-slate-100"
                                name="aktiflik_durumu"
                                required>
                            <option value="1" {{ old('aktiflik_durumu', '1') == '1' ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ old('aktiflik_durumu') == '0' ? 'selected' : '' }}>Pasif</option>
                        </select>
                    @error('aktiflik_durumu')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Preview -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Preview
                    </label>
                    <div class="p-4 bg-gray-50 dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <div class="flex items-center">
                            <div id="previewIcon" class="text-lg mr-2">
                                <i class="fas fa-tag"></i>
                            </div>
                            <span id="previewName" class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Etiket Name</span>
                            <div id="previewColor" class="w-4 h-4 rounded-full ml-2" style="background-color: #3B82F6;"></div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <a href="{{ route('admin.etiket.index') }}"
                       class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md dark:shadow-none">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </a>
                    <button type="submit"
                            id="etiket-submit-btn"
                            class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 font-semibold shadow-md hover:shadow-lg hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 dark:shadow-none">
                        <svg id="etiket-submit-icon" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span id="etiket-submit-text">Create Etiket</span>
                        <svg id="etiket-submit-spinner" class="hidden w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
// Color picker synchronization
document.getElementById('color').addEventListener('input', function() {
    document.getElementById('color_text').value = this.value;
    document.getElementById('previewColor').style.backgroundColor = this.value;
});

document.getElementById('color_text').addEventListener('input', function() {
    if (this.value.match(/^#[0-9A-Fa-f]{6}$/)) {
        document.getElementById('color').value = this.value;
        document.getElementById('previewColor').style.backgroundColor = this.value;
    }
});

// Icon preview
document.getElementById('icon').addEventListener('input', function() {
    const previewIcon = document.getElementById('previewIcon');
    const iconClass = this.value || 'fas fa-tag';
    previewIcon.innerHTML = `<i class="${iconClass}"></i>`;
});

// Name preview
document.getElementById('name').addEventListener('input', function() {
    document.getElementById('previewName').textContent = this.value || 'Etiket Name';
});
</script>
@endpush
