{{-- Form Builder Field Component --}}
@props(['field'])

@php
    $fieldId = $field['id'] ?? ($field['key'] ?? 'field_' . uniqid());
    $fieldName = $field['key'] ?? ($field['id'] ?? '');
    $fieldType = $field['type'] ?? 'text';
    $fieldLabel = $field['label'] ?? '';
    $fieldRequired = $field['required'] ?? false;
    $fieldIcon = $field['icon'] ?? 'fas fa-circle';
    $fieldPlaceholder = $field['placeholder'] ?? '';
    $fieldHelpText = $field['help_text'] ?? '';
    $fieldOptions = $field['options'] ?? [];
    $fieldValue = $field['value'] ?? '';
    $fieldAttributes = $field['attributes'] ?? [];
@endphp

<div class="form-field-wrapper" x-data="{
    value: @js($fieldValue),
    error: null,
    isValid: true
}">

    {{-- Field Label --}}
    <label for="{{ $fieldId }}" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
        <i class="{{ $fieldIcon }} mr-2 text-blue-600"></i>
        {{ $fieldLabel }}
        @if ($fieldRequired)
            <span class="text-red-500 ml-1">*</span>
        @endif
    </label>

    {{-- Field Input --}}
    <div class="field-input-container">
        @switch($fieldType)
            @case('select')
                <select id="{{ $fieldId }}" name="{{ $fieldName }}" x-model="value"
                    @if ($fieldRequired) required @endif
                    @foreach ($fieldAttributes as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                    class="mt-1 block w-full py-3 px-4 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm dark:text-white transition-colors dark:shadow-none">

                    <option value="">Seçiniz...</option>
                    @foreach ($fieldOptions as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}" @if ($fieldValue == $optionValue) selected @endif>
                            {{ $optionLabel }}
                        </option>
                    @endforeach
                </select>
            @break

            @case('textarea')
                <textarea id="{{ $fieldId }}" name="{{ $fieldName }}" x-model="value"
                    @if ($fieldRequired) required @endif placeholder="{{ $fieldPlaceholder }}"
                    @foreach ($fieldAttributes as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                    class="mt-1 block w-full py-3 px-4 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm dark:text-white transition-colors dark:shadow-none"
                    rows="4"></textarea>
            @break

            @case('checkbox')
                <div class="flex items-center">
                    <input type="checkbox" id="{{ $fieldId }}" name="{{ $fieldName }}" x-model="value"
                        @if ($fieldRequired) required @endif
                        @foreach ($fieldAttributes as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 rounded">
                    <label for="{{ $fieldId }}" class="ml-2 block text-sm text-gray-900 dark:text-white dark:text-slate-100">
                        {{ $fieldLabel }}
                    </label>
                </div>
            @break

            @case('radio')
                <div class="space-y-2">
                    @foreach ($fieldOptions as $optionValue => $optionLabel)
                        <div class="flex items-center">
                            <input type="radio" id="{{ $fieldId }}_{{ $optionValue }}" name="{{ $fieldName }}"
                                value="{{ $optionValue }}" x-model="value" @if ($fieldRequired) required @endif
                                @foreach ($fieldAttributes as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600">
                            <label for="{{ $fieldId }}_{{ $optionValue }}"
                                class="ml-2 block text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                {{ $optionLabel }}
                            </label>
                        </div>
                    @endforeach
                </div>
            @break

            @case('file-upload')
                <div class="file-upload-container">
                    <input type="file" id="{{ $fieldId }}" name="{{ $fieldName }}[]"
                        @if ($fieldRequired) required @endif
                        @foreach ($fieldAttributes as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                        accept="image/*" multiple
                        class="mt-1 block w-full py-3 px-4 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm dark:text-white transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:shadow-none">

                    <div class="file-preview mt-2 grid grid-cols-2 md:grid-cols-4 gap-2" x-show="value && value.length > 0">
                        <template x-for="(file, index) in value" :key="index">
                            <div class="relative">
                                <img :src="URL.createObjectURL(file)" class="w-full h-20 object-cover rounded border">
                                <button @click="value.splice(index, 1)"
                                    class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600">
                                    ×
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            @break

            @case('date')
                <input type="date" id="{{ $fieldId }}" name="{{ $fieldName }}" x-model="value"
                    @if ($fieldRequired) required @endif
                    @foreach ($fieldAttributes as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                    class="mt-1 block w-full py-3 px-4 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm dark:text-white transition-colors dark:shadow-none">
            @break

            @case('number')
                <input type="number" id="{{ $fieldId }}" name="{{ $fieldName }}" x-model="value"
                    @if ($fieldRequired) required @endif placeholder="{{ $fieldPlaceholder }}"
                    @foreach ($fieldAttributes as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                    class="mt-1 block w-full py-3 px-4 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm dark:text-white transition-colors dark:shadow-none">
            @break

            @case('url')
                <input type="url" id="{{ $fieldId }}" name="{{ $fieldName }}" x-model="value"
                    @if ($fieldRequired) required @endif placeholder="{{ $fieldPlaceholder }}"
                    @foreach ($fieldAttributes as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                    class="mt-1 block w-full py-3 px-4 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm dark:text-white transition-colors dark:shadow-none">
            @break

            @case('email')
                <input type="email" id="{{ $fieldId }}" name="{{ $fieldName }}" x-model="value"
                    @if ($fieldRequired) required @endif placeholder="{{ $fieldPlaceholder }}"
                    @foreach ($fieldAttributes as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                    class="mt-1 block w-full py-3 px-4 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm dark:text-white transition-colors dark:shadow-none">
            @break

            @case('tel')
                <input type="tel" id="{{ $fieldId }}" name="{{ $fieldName }}" x-model="value"
                    @if ($fieldRequired) required @endif placeholder="{{ $fieldPlaceholder }}"
                    @foreach ($fieldAttributes as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                    class="mt-1 block w-full py-3 px-4 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm dark:text-white transition-colors dark:shadow-none">
            @break

            @case('coordinates')
                <div class="coordinates-container">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" name="{{ $fieldName }}_lat" x-model="value.lat" placeholder="Enlem"
                            step="0.000001"
                            class="mt-1 block w-full py-3 px-4 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm dark:text-white transition-colors dark:shadow-none">
                        <input type="number" name="{{ $fieldName }}_lng" x-model="value.lng" placeholder="Boylam"
                            step="0.000001"
                            class="mt-1 block w-full py-3 px-4 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm dark:text-white transition-colors dark:shadow-none">
                    </div>
                    <button type="button" @click="getCurrentLocation()" class="mt-2 w-full px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-crosshairs mr-2"></i>Mevcut Konumu Al
                    </button>
                </div>
            @break

            @case('checkbox-group')
                <div class="checkbox-group-container">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach ($fieldOptions as $optionValue => $optionLabel)
                            <div class="flex items-center">
                                <input type="checkbox" id="{{ $fieldId }}_{{ $optionValue }}"
                                    name="{{ $fieldName }}[]" value="{{ $optionValue }}" x-model="value"
                                    @if ($fieldRequired) required @endif
                                    @foreach ($fieldAttributes as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 rounded">
                                <label for="{{ $fieldId }}_{{ $optionValue }}"
                                    class="ml-2 block text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                    {{ $optionLabel }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @break

            @case('dynamic-fields')
                <div class="dynamic-fields-container" x-data="{ fields: [] }">
                    <div class="space-y-3">
                        <template x-for="(field, index) in fields" :key="index">
                            <div class="flex space-x-2">
                                <input type="text" :name="`{{ $fieldName }}[${index}][key]`" x-model="field.key"
                                    placeholder="Alan adı"
                                    class="flex-1 py-2 px-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 rounded-lg text-sm dark:text-white">
                                <input type="text" :name="`{{ $fieldName }}[${index}][value]`" x-model="field.value"
                                    placeholder="Alan değeri"
                                    class="flex-1 py-2 px-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 rounded-lg text-sm dark:text-white">
                                <button @click="fields.splice(index, 1)" type="button"
                                    class="px-4 py-2.5 bg-red-500 text-white rounded-lg hover:bg-red-600 text-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                    <button @click="fields.push({key: '', value: ''})" type="button" class="mt-3 px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <i class="fas fa-plus mr-2"></i>Alan Ekle
                    </button>
                </div>
            @break

            @default
                <input type="text" id="{{ $fieldId }}" name="{{ $fieldName }}" x-model="value"
                    @if ($fieldRequired) required @endif placeholder="{{ $fieldPlaceholder }}"
                    @foreach ($fieldAttributes as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                    class="mt-1 block w-full py-3 px-4 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm dark:text-white transition-colors dark:shadow-none">
        @endswitch
    </div>

    {{-- Help Text --}}
    @if ($fieldHelpText)
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $fieldHelpText }}</p>
    @endif

    {{-- Error Message --}}
    <div x-show="error" x-text="error" class="mt-1 text-sm text-red-600 dark:text-red-400"></div>

    {{-- Validation Status --}}
    <div class="field-validation-status mt-1">
        <span x-show="isValid" class="text-green-600 dark:text-green-400 text-sm">
            <i class="fas fa-check-circle mr-1"></i>Geçerli
        </span>
        <span x-show="!isValid" class="text-red-600 dark:text-red-400 text-sm">
            <i class="fas fa-exclamation-circle mr-1"></i>Hatalı
        </span>
    </div>
</div>

@push('scripts')
    <script>
        // Coordinates field functionality
        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        this.value = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                    },
                    function(error) {
                        console.error('Konum alınamadı:', error);
                        alert('Konum alınamadı. Lütfen manuel olarak girin.');
                    }
                );
            } else {
                alert('Tarayıcınız konum özelliğini desteklemiyor.');
            }
        }

        // File upload preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            const fileInputs = document.querySelectorAll('input[type="file"]');

            fileInputs.forEach(input => {
                input.addEventListener('change', function(e) {
                    const files = Array.from(e.target.files);
                    const previewContainer = this.parentElement.querySelector('.file-preview');

                    if (previewContainer) {
                        previewContainer.innerHTML = '';

                        files.forEach(file => {
                            if (file.type.startsWith('image/')) {
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    const img = document.createElement('img');
                                    img.src = e.target.result;
                                    img.className =
                                        'w-full h-20 object-cover rounded border';
                                    previewContainer.appendChild(img);
                                };
                                reader.readAsDataURL(file);
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
