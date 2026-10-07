@props([
    'label' => null,
    'name',
    'checked' => false,
    'value' => 1,
    'required' => false,
    'disabled' => false,
    'error' => null,
    'wrapperClass' => 'mb-4',
])

@php
    $hasError = $error || ($errors->has($name));
@endphp

<div class="{{ $wrapperClass }}">
    <label class="inline-flex items-center gap-2.5 cursor-pointer group">
        <input
            type="checkbox"
            name="{{ $name }}"
            value="{{ $value }}"
            {{ $checked || old($name) ? 'checked' : '' }}
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            class="w-5 h-5 rounded border-2 transition-all cursor-pointer
                   {{ $hasError
                       ? 'border-red-500 dark:border-red-400 text-red-500 dark:text-red-400'
                       : 'border-gray-300 dark:border-gray-600 text-blue-500 dark:text-blue-400' }}
                   focus:ring-2 focus:ring-blue-500/30 dark:focus:ring-blue-400/30
                   bg-white dark:bg-slate-800
                   disabled:opacity-50 disabled:cursor-not-allowed
                   group-hover:border-gray-400 dark:group-hover:border-gray-500">
        @if($label)
            <span class="text-sm text-slate-700 dark:text-slate-200 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">
                {{ $label }}
                @if($required)
                    <span class="text-red-500">*</span>
                @endif
            </span>
        @endif
    </label>

    @error($name)
        <x-admin.validation-error :message="$message" />
    @enderror
</div>
