{{--
    Toggle/Switch Component

    @component x-admin.toggle
    @description Modern toggle switch with label and error handling

    @props
        - name: string (required) - Input name
        - label: string (required) - Toggle label
        - checked: bool (optional) - Checked state - default: false
        - disabled: bool (optional) - Disabled state - default: false
        - error: string (optional) - Error message
        - help: string (optional) - Help text
        - id: string (optional) - Custom ID - default: name
        - size: string (optional) - Size variant (sm, md, lg) - default: md

    @example
        <x-admin.toggle
            name="notifications"
            label="Enable Notifications"
            :checked="old('notifications', $user->notifications ?? false)"
            help="Receive email notifications for new listings"
        />

    @accessibility
        - ARIA labels
        - Keyboard navigation (Space/Enter)
        - Focus states
        - Screen reader announcements
--}}

@props([
    'name' => '',
    'label' => '',
    'checked' => false,
    'disabled' => false,
    'error' => null,
    'help' => null,
    'id' => null,
    'size' => 'md',
])

@php
$toggleId = $id ?? 'toggle-' . $name;
$hasError = !empty($error);

// Size variants
$sizes = [
    'sm' => [
        'switch' => 'w-9 h-5',
        'toggle' => 'w-4 h-4',
        'translate' => 'translate-x-4',
    ],
    'md' => [
        'switch' => 'w-11 h-6',
        'toggle' => 'w-5 h-5',
        'translate' => 'translate-x-5',
    ],
    'lg' => [
        'switch' => 'w-14 h-7',
        'toggle' => 'w-6 h-6',
        'translate' => 'translate-x-7',
    ],
];

$sizeClasses = $sizes[$size] ?? $sizes['md'];
@endphp

<div
    x-data="{ status: {{ $checked ? 'true' : 'false' }} }"
    class="flex items-start"
>
    {{-- Toggle Switch --}}
    <button
        type="button"
        @click="if (!{{ $disabled ? 'true' : 'false' }}) status = !status"
        @keydown.space.prevent="if (!{{ $disabled ? 'true' : 'false' }}) status = !status"
        @keydown.enter.prevent="if (!{{ $disabled ? 'true' : 'false' }}) status = !status"
        :aria-checked="status"
        :aria-labelledby="'{{ $toggleId }}-label'"
        :aria-describedby="'{{ $help ? $toggleId . '-help' : '' }} {{ $hasError ? $toggleId . '-error' : '' }}'"
        role="switch"
        class="relative inline-flex {{ $sizeClasses['switch'] }} flex-shrink-0
               rounded-full transition-colors duration-200 ease-in-out
               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900
               {{ $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}
               {{ $hasError ? 'ring-2 ring-red-500' : '' }}"
        :class="{
            'bg-blue-600': status && !{{ $hasError ? 'true' : 'false' }},
            'bg-gray-200 dark:bg-gray-700': !status && !{{ $hasError ? 'true' : 'false' }},
            'bg-red-600': status && {{ $hasError ? 'true' : 'false' }},
            'bg-red-200': !status && {{ $hasError ? 'true' : 'false' }}
        }"
    >
        {{-- Toggle Circle --}}
        <span
            aria-hidden="true"
            class="{{ $sizeClasses['toggle'] }} inline-block rounded-full bg-white shadow-lg
                   transform ring-0 transition-transform duration-200 ease-in-out
                   translate-x-0.5"
            :class="{ '{{ $sizeClasses['translate'] }}': status }"
        ></span>
    </button>

    {{-- Hidden Input (for form submission) --}}
    <input
        type="hidden"
        id="{{ $toggleId }}"
        name="{{ $name }}"
        :value="status ? '1' : '0'"
        {{ $disabled ? 'disabled' : '' }}
    />

    {{-- Label & Help --}}
    <div class="ml-3">
        @if($label)
        <span
            id="{{ $toggleId }}-label"
            class="text-sm font-medium text-gray-900 dark:text-white block
                   {{ $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}"
            @click="if (!{{ $disabled ? 'true' : 'false' }}) status = !status"
        >
            {{ $label }}
        </span>
        @endif

        @if($help)
        <p
            id="{{ $toggleId }}-help"
            class="mt-1 text-xs text-gray-600 dark:text-gray-400"
        >
            {{ $help }}
        </p>
        @endif

        @if($hasError)
        <p
            id="{{ $toggleId }}-error"
            class="mt-1 text-xs text-red-600 dark:text-red-400"
            role="alert"
        >
            {{ $error }}
        </p>
        @endif
    </div>
</div>
