@props([
    'label' => null,
    'name' => null,
    'required' => false,
    'hint' => null,
    'error' => null,
    'wrapperClass' => '',
])

<div class="relative {{ $wrapperClass }}">
    {{-- Label --}}
    @if($label)
        <label for="{{ $name }}"
               class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    {{-- Input Slot --}}
    {{ $slot }}

    {{-- Hint --}}
    @if($hint)
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif

    {{-- Error Message --}}
    @if($error || $name)
        @error($name)
            <x-admin.validation-error :message="$message" />
        @enderror
    @endif
</div>
