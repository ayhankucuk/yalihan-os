{{-- Schema-driven dynamic field renderer --}}
{{-- Variables: $field (schema field definition array) --}}
@php
    $fieldName = 'features[' . $field['slug'] . ']';
    $fieldId = 'feature-' . $field['slug'];
    $isRequired = $field['required'] ?? false;
    $fieldType = $field['type'] ?? 'text';
    $unit = $field['unit'] ?? null;
    $description = $field['description'] ?? null;
@endphp

<div class="dynamic-field" data-field-slug="{{ $field['slug'] }}" data-field-type="{{ $fieldType }}">
    <label for="{{ $fieldId }}" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">
        {{ $field['label'] }}
        @if ($isRequired)
            <span class="text-red-500">*</span>
        @endif
        @if ($unit)
            <span class="text-xs text-gray-400 dark:text-slate-500">({{ $unit }})</span>
        @endif
    </label>

    @switch($fieldType)
        @case('number')
            <input type="number" id="{{ $fieldId }}" name="{{ $fieldName }}" step="any"
                class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                @if ($isRequired) required @endif x-model="featureValues['{{ $field['slug'] }}']" />
        @break

        @case('boolean')
            <div class="flex items-center gap-2">
                <input type="checkbox" id="{{ $fieldId }}" name="{{ $fieldName }}" value="1"
                    class="rounded border-gray-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500 dark:bg-slate-800"
                    x-model="featureValues['{{ $field['slug'] }}']" />
                <span class="text-sm text-gray-600 dark:text-slate-400">{{ $field['label'] }}</span>
            </div>
        @break

        @case('select')
            <select id="{{ $fieldId }}" name="{{ $fieldName }}"
                class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                @if ($isRequired) required @endif x-model="featureValues['{{ $field['slug'] }}']">
                <option value="">Seçiniz</option>
                @foreach ($field['options'] ?? [] as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
        @break

        @case('multiselect')
            <div
                class="space-y-1 max-h-48 overflow-y-auto border border-gray-300 dark:border-slate-600 rounded-lg p-3 bg-white dark:bg-slate-800">
                @foreach ($field['options'] ?? [] as $option)
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300 cursor-pointer">
                        <input type="checkbox" name="{{ $fieldName }}[]" value="{{ $option['value'] }}"
                            class="rounded border-gray-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500 dark:bg-slate-800" />
                        {{ $option['label'] }}
                    </label>
                @endforeach
            </div>
        @break

        @case('textarea')
            <textarea id="{{ $fieldId }}" name="{{ $fieldName }}" rows="3"
                class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                @if ($isRequired) required @endif x-model="featureValues['{{ $field['slug'] }}']"></textarea>
        @break

        @default
            <input type="text" id="{{ $fieldId }}" name="{{ $fieldName }}"
                class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                maxlength="500" @if ($isRequired) required @endif
                x-model="featureValues['{{ $field['slug'] }}']" />
    @endswitch

    @if ($description)
        <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">{{ $description }}</p>
    @endif
</div>
