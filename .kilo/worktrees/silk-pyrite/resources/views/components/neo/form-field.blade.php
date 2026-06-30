{{--
Neo Form Field Component
Usage: <x-neo.form-field name="email" label="E-posta" type="email" required />
--}}

@props([
    'name' => '',
    'label' => '',
    'type' => 'text',
    'placeholder' => '',
    'required' => false,
    'description' => '',
    'options' => [], // For select fields
    'value' => '',
    'error' => '',
])

@php
    $id = $name ?: 'field_' . uniqid();
    $inputClasses = 'w-full px-3 py-2 rounded-md border border-gray-200 bg-white text-sm placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-800 dark:text-gray-100 transition-colors dark:placeholder:text-slate-500';

    if ($error || $errors->has($name)) {
        $inputClasses .= ' border-red-500 focus:ring-red-500';
    }

    $oldValue = old($name, $value);
@endphp

<div class="form-field">
    {{-- Label --}}
    @if($label)
        <label for="{{ $id }}" class="admin-label">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    {{-- Input Field --}}
    @if($type === 'select')
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            class="{{ $inputClasses }}"
            @if($required) required @endif
            {{ $attributes }}
        >
            <option value="">{{ $placeholder ?: 'Seçin...' }}</option>
            @foreach($options as $key => $option)
                @php
                    $optionValue = is_array($option) ? $key : $option;
                    $optionLabel = is_array($option) ? $option['label'] ?? $option['name'] ?? $key : $option;
                @endphp
                <option
                    value="{{ $optionValue }}"
                    @if($oldValue == $optionValue) selected @endif
                >
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>

    @elseif($type === 'textarea')
        <textarea
            id="{{ $id }}"
            name="{{ $name }}"
            class="{{ $inputClasses }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            rows="4"
            {{ $attributes }}
        >{{ $oldValue }}</textarea>

    @elseif($type === 'checkbox')
        <div class="flex items-center gap-3">
            <input
                type="checkbox"
                id="{{ $id }}"
                name="{{ $name }}"
                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                @if($oldValue) checked @endif
                {{ $attributes }}
            >
            @if($description)
                <label for="{{ $id }}" class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                    {{ $description }}
                </label>
            @endif
        </div>

    @else
        <input
            type="{{ $type }}"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $oldValue }}"
            class="{{ $inputClasses }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            {{ $attributes }}
        >
    @endif

    {{-- Description --}}
    @if($description && $type !== 'checkbox')
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            {{ $description }}
        </p>
    @endif

    {{-- Error Message --}}
    @if($error)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @elseif($errors->has($name))
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $errors->first($name) }}</p>
    @endif
</div>
