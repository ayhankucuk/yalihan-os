@props([
    'name',
    'label' => null,
    'value' => '',
    'placeholder' => '',
    'error' => null,
    'help' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'rows' => 4,
    'maxlength' => null,
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
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $readonly ? 'readonly' : '' }}
            {{ $maxlength ? "maxlength={$maxlength}" : '' }}
            @class([
                'w-full px-4 py-2.5 rounded-lg transition-all duration-200 resize-vertical',
                'bg-white dark:bg-gray-800',
                'text-black dark:text-white',
                'placeholder-gray-400 dark:placeholder-gray-500',
                'focus:outline-none focus:ring-2 focus:border-transparent',
                'disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:cursor-not-allowed disabled:text-gray-500',
                'border-2 border-red-500 dark:border-red-400 focus:ring-red-500' => $error,
                'border border-gray-300 dark:border-gray-600 focus:ring-blue-500 dark:focus:ring-blue-400' => !$error,
            ])
            {{ $attributes }}
        >{{ old($name, $value) }}</textarea>
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

    @if($maxlength)
        <div class="flex justify-between items-center text-sm text-gray-500 dark:text-gray-400">
            <span>{{ $help }}</span>
            <span x-data="{ count: {{ strlen(old($name, $value)) }} }"
                  @input="count = $event.target.value.length">
                <span x-text="count"></span> / {{ $maxlength }}
            </span>
        </div>
    @endif
</div>
