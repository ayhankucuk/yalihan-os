@props([
    'label' => null,
    'name',
    'required' => false,
    'help' => null,
    'value' => null,
    'options' => [],
    'placeholder' => null,
    'disabled' => false,
    'error' => null,
    'wrapperClass' => 'mb-4',
])

@php
    $hasError = $error || ($errors->has($name));
@endphp

<div class="{{ $wrapperClass }}">
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1.5">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    <div class="relative">
        <select
            {{ $attributes->merge([
                'class' => 'w-full h-11 px-4 pr-10 rounded-xl border text-sm transition-all cursor-pointer ' .
                    ($hasError
                        ? 'border-red-500 dark:border-red-400 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 bg-red-50/50 dark:bg-red-900/10 text-red-900 dark:text-red-100'
                        : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100') .
                    ($disabled ? ' opacity-50 cursor-not-allowed' : '')
            ]) }}
            id="{{ $name }}" name="{{ $name }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}>
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $key => $option)
                @if (is_array($option))
                    <option value="{{ $key }}" {{ old($name, $value) == $key ? 'selected' : '' }}>
                        {{ $option['label'] ?? $option['name'] ?? $option }}
                    </option>
                @else
                    <option value="{{ $key }}" {{ old($name, $value) == $key ? 'selected' : '' }}>
                        {{ $option }}
                    </option>
                @endif
            @endforeach
        </select>
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="w-5 h-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
    </div>
    @if ($help)
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{!! $help !!}</p>
    @endif
    @error($name)
        <x-admin.validation-error :message="$message" />
    @enderror
</div>
