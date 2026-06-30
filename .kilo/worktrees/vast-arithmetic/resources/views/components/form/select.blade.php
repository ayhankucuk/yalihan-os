@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => '',
    'placeholder' => 'Seçin...',
    'error' => null,
    'help' => null,
    'required' => false,
    'disabled' => false,
    'searchable' => false,
])

<div class="space-y-2">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <select
            id="{{ $name }}"
            name="{{ $name }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            @class([
                'w-full px-4 py-2.5 rounded-lg transition-all duration-200 cursor-pointer',
                'bg-white dark:bg-gray-800',
                'text-black dark:text-white',
                'focus:outline-none focus:ring-2 focus:border-transparent',
                'disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:cursor-not-allowed disabled:text-gray-500',
                'appearance-none',
                'border-2 border-red-500 dark:border-red-400 focus:ring-red-500' => $error,
                'border border-gray-300 dark:border-gray-600 focus:ring-blue-500 dark:focus:ring-blue-400' => !$error,
            ])
            {{ $attributes }}
        >
            @if($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif

            @foreach($options as $optionValue => $optionLabel)
                <option
                    value="{{ $optionValue }}"
                    {{ old($name, $value) == $optionValue ? 'selected' : '' }}
                    class="bg-white dark:bg-slate-900 text-black dark:text-white"
                >
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>

        <!-- Dropdown Icon -->
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </div>

    @if($error)
        <p class="text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            {{ $error }}
        </p>
    @elseif($help)
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ $help }}
        </p>
    @endif
</div>

<style>
/* Dropdown option hover state (for better contrast) */
select option:hover,
select option:focus {
    background-color: #3B82F6 !important;
    color: #FFFFFF !important;
}

/* Dark mode dropdown options */
@media (prefers-color-scheme: dark) {
    select option {
        background-color: #1F2937;
        color: #FFFFFF;
    }

    select option:hover,
    select option:focus {
        background-color: #3B82F6 !important;
        color: #FFFFFF !important;
    }
}
</style>
