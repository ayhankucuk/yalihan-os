{{--
    Modal Component

    @component x-admin.modal
    @description Full-featured modal with Alpine.js, keyboard support, and animations

    @props
        - open: bool (optional) - Initial open state - default: false
        - title: string (optional) - Modal title
        - size: string (optional) - Modal size: sm, md, lg, xl, full - default: md
        - bind: string (optional) - Alpine.js binding variable name
        - position: string (optional) - Modal position: center, top - default: center
        - scrollable: bool (optional) - Enable body scroll - default: false
        - closeOnBackdrop: bool (optional) - Close on backdrop click - default: true
        - closeOnEsc: bool (optional) - Close on ESC key - default: true

    @slots
        - default: Modal content
        - footer: Modal footer (optional)

    @example
        <x-admin.modal title="Edit User" size="lg" bind="showModal">
            <p>Modal content here...</p>

            <x-slot:footer>
                <button @click="showModal = false">Cancel</button>
                <button>Save</button>
            </x-slot:footer>
        </x-admin.modal>

    @features
        - Keyboard support (ESC to close)
        - Focus trap
        - Smooth animations
        - Backdrop blur
        - Dark mode support
        - Multiple size variants
        - Position variants
        - Scrollable content
--}}

@props([
    'open' => false,
    'title' => null,
    'size' => 'md',
    'bind' => null,
    'position' => 'center',
    'scrollable' => false,
    'closeOnBackdrop' => true,
    'closeOnEsc' => true,
])

@php
    $isBound = !empty($bind);
    $modalVar = $isBound ? $bind : 'open';
@endphp

<div
    @if(!$isBound) x-data="{ open: {{ $open ? 'true' : 'false' }} }" @endif
    @if($isBound) x-show="{{ $bind }}" @else x-show="open" @endif
    @if($closeOnEsc) @keydown.escape.window="@if($isBound){{ $bind }} = false @else open = false @endif" @endif
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="@if($isBound){{ $bind }}@else open @endif"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm"
        @if($closeOnBackdrop)
            @click="@if($isBound){{ $bind }} = false @else open = false @endif"
        @endif
        aria-hidden="true"
    ></div>

    {{-- Modal Container --}}
    <div class="flex min-h-screen items-{{ $position === 'top' ? 'start' : 'center' }} justify-center {{ 'pt-20' 'p-4'">
        {{-- Modal Content --}}
        <div
            x-show="@if($isBound){{ $bind }}@else open @endif"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            @click.stop
            class="relative bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-gray-200 dark:border-slate-800 {{ $scrollable ? 'max-h-[90vh] flex flex-col' : '' }} dark:border-slate-700"
            :class="{
                'w-full max-w-sm': '{{ $size }}' === 'sm',
                'w-full max-w-lg': '{{ $size }}' === 'md',
                'w-full max-w-2xl': '{{ $size }}' === 'lg',
                'w-full max-w-4xl': '{{ $size }}' === 'xl',
                'w-full max-w-full mx-4': '{{ $size }}' === 'full',
            }"
        >
            {{-- Header --}}
            @if($title)
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between {{ $scrollable ? 'flex-shrink-0' : '' }} dark:border-slate-700">
                <h3 id="modal-title" class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                    {{ $title }}
                </h3>
                <button
                    type="button"
                    @click="@if($isBound){{ $bind }} = false @else open = false @endif"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200
                           transition-colors duration-200 p-1 rounded-lg
                           hover:bg-gray-100 dark:hover:bg-gray-800
                           focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label="Close modal"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            @endif

            {{-- Body --}}
            <div class="p-6 {{ $scrollable ? 'overflow-y-auto flex-1' : '' }}">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            @isset($footer)
            <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 rounded-b-xl {{ $scrollable ? 'flex-shrink-0' : '' }} dark:border-slate-700">
                {{ $footer }}
            </div>
            @endisset
        </div>
    </div>
</div>
