@props([
    'label' => null,
    'name',
    'required' => false,
    'help' => null,
    'value' => null,
    'rows' => 3,
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
    <textarea
        {{ $attributes->merge(['class' => 'w-full p-4 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm text-gray-900 dark:text-gray-100 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-sm placeholder-gray-400 dark:placeholder-gray-500 resize-y dark:shadow-none']) }}
        id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
        @if ($required) required @endif>
@if (old($name))
{{ old($name) }}@else{{ $value }}
@endif
</textarea>
    @if ($help)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{!! $help !!}</p>
    @endif
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
