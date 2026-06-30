@props([
    'label' => null,
    'name',
    'type' => 'text',
    'required' => false,
    'help' => null,
    'value' => null,
    'error' => null,
    'wrapperClass' => 'mb-4',
])
<div class="{{ $wrapperClass }}">
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
            {{ $label }} @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    <input
        {{ $attributes->merge(['class' => 'w-full h-11 px-4 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm text-gray-900 dark:text-gray-100 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-sm placeholder-gray-400 dark:placeholder-gray-500 shadow-sm dark:shadow-none']) }}
        type="{{ $type }}" id="{{ $name }}" name="{{ $name }}"
        @if (!is_null($value)) value="{{ old($name, $value) }}" @else value="{{ old($name) }}" @endif
        @if ($required) required @endif>
    @if ($help)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{!! $help !!}</p>
    @endif
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
