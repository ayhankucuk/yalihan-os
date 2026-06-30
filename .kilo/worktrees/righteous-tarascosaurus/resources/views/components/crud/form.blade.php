@props([
    'action',
    'method' => 'POST',
    'enctype' => null,
    'fields' => [],
    'data' => null,
    'submitText' => 'Kaydet',
    'cancelRoute' => null,
    'cancelText' => 'İptal',
])

<form action="{{ $action }}" method="{{ strtolower($method) === 'get' ? 'GET' : 'POST' }}" {!! $enctype ? 'enctype="'.$enctype.'"' : '' !!} class="space-y-6">
    @csrf

    @if(strtolower($method) !== 'post' && strtolower($method) !== 'get')
        @method($method)
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($fields as $field)
            @php
                $fieldName = $field['name'];
                $fieldType = $field['type'] ?? 'text';
                $fieldLabel = $field['label'] ?? ucfirst($fieldName);
                $fieldPlaceholder = $field['placeholder'] ?? '';
                $fieldRequired = isset($field['required']) && $field['required'] ? true : false;
                $fieldOptions = $field['options'] ?? [];
                $fieldValue = old($fieldName, $data ? ($data->{$fieldName} ?? '') : '');
                $fieldClass = $field['class'] ?? '';
                $fieldCols = $field['cols'] ?? 1;
                $fieldHelp = $field['help'] ?? '';
                $fieldDisabled = isset($field['disabled']) && $field['disabled'] ? true : false;
                $fieldAutofocus = isset($field['autofocus']) && $field['autofocus'] ? true : false;
            @endphp

            <div class="{{ $fieldCols > 1 ? 'md:col-span-'.$fieldCols : '' }}">
                @if($fieldType === 'hidden')
                    <input type="hidden" name="{{ $fieldName }}" value="{{ $fieldValue }}">
                @else
                    <label for="{{ $fieldName }}" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                        {{ $fieldLabel }}
                        @if($fieldRequired)
                            <span class="text-red-500">*</span>
                        @endif
                    </label>

                    @if($fieldType === 'text' || $fieldType === 'email' || $fieldType === 'password' || $fieldType === 'number' || $fieldType === 'date' || $fieldType === 'tel' || $fieldType === 'url')
                        <input
                            type="{{ $fieldType }}"
                            id="{{ $fieldName }}"
                            name="{{ $fieldName }}"
                            value="{{ $fieldValue }}"
                            placeholder="{{ $fieldPlaceholder }}"
                            class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-slate-900 dark:text-white {{ $errors->has($fieldName) ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-600' }} $fieldClass dark:shadow-none"
                            {{ $fieldRequired ? 'required' : '' }}
                            {{ $fieldDisabled ? 'disabled' : '' }}
                            {{ $fieldAutofocus ? 'autofocus' : '' }}
                        >
                    @elseif($fieldType === 'textarea')
                        <textarea
                            id="{{ $fieldName }}"
                            name="{{ $fieldName }}"
                            rows="{{ $field['rows'] ?? 3 }}"
                            placeholder="{{ $fieldPlaceholder }}"
                            class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-slate-900 dark:text-white {{ $errors->has($fieldName) ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-600' }} $fieldClass dark:shadow-none"
                            {{ $fieldRequired ? 'required' : '' }}
                            {{ $fieldDisabled ? 'disabled' : '' }}
                            {{ $fieldAutofocus ? 'autofocus' : '' }}
                        >{{ $fieldValue }}</textarea>
                    @elseif($fieldType === 'select')
                        <select
                            id="{{ $fieldName }}"
                            name="{{ $fieldName }}"
                            class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-slate-900 dark:text-white {{ $errors->has($fieldName) ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-600' }} $fieldClass dark:shadow-none"
                            {{ $fieldRequired ? 'required' : '' }}
                            {{ $fieldDisabled ? 'disabled' : '' }}
                            {{ $fieldAutofocus ? 'autofocus' : '' }}
                        >
                            @if(!$fieldRequired)
                                <option value="">Seçiniz</option>
                            @endif

                            @foreach($fieldOptions as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" {{ $fieldValue == $optionValue ? 'selected' : '' }}>
                                    {{ $optionLabel }}
                                </option>
                            @endforeach
                        </select>
                    @elseif($fieldType === 'checkbox')
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                id="{{ $fieldName }}"
                                name="{{ $fieldName }}"
                                value="1"
                                {{ $fieldValue ? 'checked' : '' }}
                                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 rounded dark:bg-slate-900 {{ $fieldClass }}"
                                {{ $fieldDisabled ? 'disabled' : '' }}
                            >
                            <label for="{{ $fieldName }}" class="ml-2 block text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                {{ $fieldPlaceholder }}
                            </label>
                        </div>
                    @elseif($fieldType === 'radio')
                        <div class="space-y-2">
                            @foreach($fieldOptions as $optionValue => $optionLabel)
                                <div class="flex items-center">
                                    <input
                                        type="radio"
                                        id="{{ $fieldName }}_{{ $optionValue }}"
                                        name="{{ $fieldName }}"
                                        value="{{ $optionValue }}"
                                        {{ $fieldValue == $optionValue ? 'checked' : '' }}
                                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 dark:bg-slate-900 {{ $fieldClass }}"
                                        {{ $fieldRequired ? 'required' : '' }}
                                        {{ $fieldDisabled ? 'disabled' : '' }}
                                    >
                                    <label for="{{ $fieldName }}_{{ $optionValue }}" class="ml-2 block text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                        {{ $optionLabel }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @elseif($fieldType === 'file')
                        <input
                            type="file"
                            id="{{ $fieldName }}"
                            name="{{ $fieldName }}"
                            class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-slate-900 dark:text-white {{ $errors->has($fieldName) ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-600' }} $fieldClass dark:shadow-none"
                            {{ $fieldRequired ? 'required' : '' }}
                            {{ $fieldDisabled ? 'disabled' : '' }}
                            {{ $fieldAutofocus ? 'autofocus' : '' }}
                            {{ isset($field['accept']) ? 'accept="'.$field['accept'].'"' : '' }}
                            {{ isset($field['multiple']) && $field['multiple'] ? 'multiple' : '' }}
                        >
                        @if($data && method_exists($data, 'getFirstMediaUrl') && $data->getFirstMediaUrl($fieldName))
                            <div class="mt-2">
                                <img src="{{ $data->getFirstMediaUrl($fieldName) }}" alt="{{ $fieldLabel }}" class="h-20 w-auto rounded">
                            </div>
                        @endif
                    @endif

                    @if($fieldHelp)
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $fieldHelp }}</p>
                    @endif

                    @error($fieldName)
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                @endif
            </div>
        @endforeach
    </div>

    <div class="flex justify-end space-x-3 pt-5">
        @if($cancelRoute)
            <a href="{{ route($cancelRoute) }}" class="px-4 py-2 bg-gray-200 dark:bg-slate-900 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:focus:ring-offset-gray-800 transition-colors duration-200 dark:text-slate-300">
                {{ $cancelText }}
            </a>
        @endif

        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 transition-colors duration-200">
            <i class="fas fa-save mr-2"></i> {{ $submitText }}
        </button>
    </div>
</form>
