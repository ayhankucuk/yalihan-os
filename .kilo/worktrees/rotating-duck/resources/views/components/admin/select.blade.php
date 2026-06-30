@props([
    'label' => null,
    'name',
    'required' => false,
    'help' => null,
    'value' => null,
    'error' => null,
    'wrapperClass' => 'mb-4',
    'options' => [],
    'placeholder' => null,
])

<div class="{{ $wrapperClass }}">
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
            {{ $label }} @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    <select
        {{ $attributes->merge(['class' => 'w-full h-11 px-4 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm text-gray-900 dark:text-gray-100 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-sm cursor-pointer dark:shadow-none']) }}
        id="{{ $name }}" name="{{ $name }}"
        @if ($required) required @endif>
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
    @if ($help)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{!! $help !!}</p>
    @endif
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
