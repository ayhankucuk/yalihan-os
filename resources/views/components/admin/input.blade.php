@props([
    'type' => 'text',
    'name',
    'value' => null,
    'placeholder' => '',
    'disabled' => false,
    'readonly' => false,
    'error' => null,
])

@php
    $hasError = $error || ($errors->has($name));
@endphp

<input
    type="{{ $type }}"
    name="{{ $name }}"
    id="{{ $name }}"
    value="{{ old($name, $value) }}"
    placeholder="{{ $placeholder }}"
    {{ $disabled ? 'disabled' : '' }}
    {{ $readonly ? 'readonly' : '' }}
    {{ $attributes->merge([
        'class' => 'w-full h-11 px-4 rounded-xl border text-sm transition-all ' .
            ($hasError
                ? 'border-red-500 dark:border-red-400 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 bg-red-50/50 dark:bg-red-900/10 text-red-900 dark:text-red-100 placeholder-red-300 dark:placeholder-red-400'
                : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 placeholder-slate-400 dark:placeholder-slate-500') .
            ($disabled ? ' opacity-50 cursor-not-allowed' : '')
    ]) }}
>
