{{--
    Alert Component

    @component x-admin.alert
    @description Modern alert/notification boxes with icons

    @props
        - type: string (optional) - Alert type (success, info, warning, error) - default: info
        - dismissible: bool (optional) - Can be dismissed - default: false
        - icon: bool (optional) - Show icon - default: true
        - title: string (optional) - Alert title

    @example
        <x-admin.alert type="success" :dismissible="true">
            Property successfully saved!
        </x-admin.alert>

        <x-admin.alert type="error" title="Error">
            An error occurred while saving.
        </x-admin.alert>

    @accessibility
        - ARIA role="alert"
        - Color contrast compliance
        - Screen reader friendly
--}}

@props([
    'type' => 'info',
    'dismissible' => false,
    'icon' => true,
    'title' => null,
])

@php
$types = [
    'success' => [
        'bg' => 'bg-green-50 dark:bg-green-900/20',
        'border' => 'border-green-200 dark:border-green-800',
        'text' => 'text-green-800 dark:text-green-300',
        'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>',
    ],
    'info' => [
        'bg' => 'bg-blue-50 dark:bg-blue-900/20',
        'border' => 'border-blue-200 dark:border-blue-800',
        'text' => 'text-blue-800 dark:text-blue-300',
        'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>',
    ],
    'warning' => [
        'bg' => 'bg-yellow-50 dark:bg-yellow-900/20',
        'border' => 'border-yellow-200 dark:border-yellow-800',
        'text' => 'text-yellow-800 dark:text-yellow-300',
        'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>',
    ],
    'error' => [
        'bg' => 'bg-red-50 dark:bg-red-900/20',
        'border' => 'border-red-200 dark:border-red-800',
        'text' => 'text-red-800 dark:text-red-300',
        'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>',
    ],
];

$style = $types[$type] ?? $types['info'];
@endphp

<div
    @if($dismissible)
    x-data="{ show: true }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0 transform scale-95"
    @endif
    class="rounded-lg border {{ $style['bg'] }} $style['border'] p-4"
    role="alert"
>
    <div class="flex items-start">
        {{-- Icon --}}
        @if($icon)
        <div class="{{ $style['text'] }} flex-shrink-0">
            {!! $style['icon'] !!}
        </div>
        @endif

        {{-- Content --}}
        <div class="flex-1 {{ $icon ? 'ml-3' : '' }}">
            @if($title)
            <h3 class="text-sm font-semibold {{ $style['text'] }} mb-1">
                {{ $title }}
            </h3>
            @endif

            <div class="text-sm {{ $style['text'] }}">
                {{ $slot }}
            </div>
        </div>

        {{-- Dismiss Button --}}
        @if($dismissible)
        <button
            type="button"
            @click="show = false"
            class="{{ $style['text'] }} ml-3 flex-shrink-0 inline-flex rounded-lg p-1.5
                   hover:bg-black/10 dark:hover:bg-white/10
                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-{{ explode('-', $style['text'])[1] }}-500
                   transition-colors duration-200"
        >
            <span class="sr-only">Dismiss</span>
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        @endif
    </div>
</div>
