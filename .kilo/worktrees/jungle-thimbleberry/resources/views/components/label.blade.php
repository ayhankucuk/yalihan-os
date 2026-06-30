@props(['for' => null, 'value' => null])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-gray-700 dark:text-slate-300']) }}
    @if ($for) for="{{ $for }}" @endif>
    {{ $value ?? $slot }}
</label>
