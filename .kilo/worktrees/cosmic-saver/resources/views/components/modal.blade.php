{{--
    Modal Component

    @component x-modal
    @description Reusable modal wrapper with Alpine.js and Tailwind CSS

    @props
        - id: string (required) - Unique modal identifier
        - title: string (required) - Modal title
        - size: string (optional) - Modal size (sm, md, lg, xl, full) - default: md
        - closeable: bool (optional) - Show close button - default: true
        - show: bool (optional) - Initial visibility - default: false

    @slots
        - default: Modal body content
        - footer: Modal footer content (optional)

    @example
        <x-modal id="deleteModal" title="Confirm Delete" size="md">
            <p>Are you sure you want to delete this item?</p>

            <x-slot name="footer">
                <button @click="show = false" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg dark:text-slate-300">
                    Cancel
                </button>
                <button class="px-4 py-2 bg-red-600 text-white hover:bg-red-700 rounded-lg">
                    Delete
                </button>
            </x-slot>
        </x-modal>

    @accessibility
        - ARIA labels
        - Keyboard navigation (ESC to close)
        - Focus management
        - Screen reader support
--}}

@props([
    'id' => 'modal-' . uniqid(),
    'title' => '',
    'size' => 'md',
    'closeable' => true,
    'show' => false,
])

@php
$sizeClasses = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
    'full' => 'max-w-full mx-4',
];

$modalSize = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<div
    x-data="{
        show: {{ $show ? 'true' : 'false' }},
        closeModal() {
            this.show = false;
        }
    }"
    x-show="show"
    x-on:keydown.escape.window="closeModal()"
    x-on:open-modal-{{ $id }}.window="show = true"
    x-on:close-modal-{{ $id }}.window="show = false"
    style="display: none;"
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title-{{ $id }}"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/50 dark:bg-gray-900/80 backdrop-blur-sm transition-opacity"
        @click="closeModal()"
        aria-hidden="true"
    ></div>

    {{-- Modal Container --}}
    <div class="flex min-h-screen items-center justify-center p-4">
        {{-- Modal Content --}}
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full {{ $modalSize }} transform overflow-hidden rounded-lg bg-white dark:bg-slate-900 shadow-2xl transition-all"
            @click.stop
        >
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-slate-800 px-6 py-4 dark:border-slate-700">
                <h3
                    id="modal-title-{{ $id }}"
                    class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100"
                >
                    {{ $title }}
                </h3>

                @if($closeable)
                <button
                    type="button"
                    @click="closeModal()"
                    class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-300 transition-colors"
                    aria-label="Close modal"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                @endif
            </div>

            {{-- Body --}}
            <div class="px-6 py-4">
                {{ $slot }}
            </div>

            {{-- Footer (Optional) --}}
            @isset($footer)
            <div class="flex items-center justify-end gap-3 border-t border-gray-200 dark:border-slate-800 px-6 py-4 bg-gray-50 dark:bg-gray-900/50 dark:border-slate-700 dark:bg-slate-900">
                {{ $footer }}
            </div>
            @endisset
        </div>
    </div>
</div>

{{-- JavaScript Helper (Optional) --}}
@once
<script>
    // Global modal helper functions
    window.openModal = function(modalId) {
        window.dispatchEvent(new CustomEvent('open-modal-' + modalId));
    };

    window.closeModal = function(modalId) {
        window.dispatchEvent(new CustomEvent('close-modal-' + modalId));
    };
</script>
@endonce
