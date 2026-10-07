@props([
    'label' => null,
    'name',
    'required' => false,
    'help' => null,
    'value' => null,
    'rows' => 3,
    'placeholder' => '',
    'disabled' => false,
    'readonly' => false,
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
    <textarea
        {{ $attributes->merge([
            'class' => 'w-full p-4 rounded-xl border text-sm transition-all resize-y placeholder-slate-400 dark:placeholder-slate-500 ' .
                ($hasError
                    ? 'border-red-500 dark:border-red-400 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 bg-red-50/50 dark:bg-red-900/10'
                    : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100') .
                ($disabled ? ' opacity-50 cursor-not-allowed' : '')
        ]) }}
        id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $readonly ? 'readonly' : '' }}
>@if(old($name)){{ old($name) }}@else{{ $value ?? '' }}@endif</textarea>
    @if ($help)
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{!! $help !!}</p>
    @endif
    @error($name)
        <x-admin.validation-error :message="$message" />
    @enderror
</div>
