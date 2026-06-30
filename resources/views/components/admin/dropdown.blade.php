{{--
    Dropdown Menu Component

    @component x-admin.dropdown
    @description Modern dropdown menu with Alpine.js

    @props
        - align: string (optional) - Alignment (left, right, center) - default: right
        - width: string (optional) - Width class - default: w-48
        - trigger: slot (optional) - Custom trigger button

    @slots
        - trigger: Button content (required)
        - default: Dropdown items (required)

    @example
        <x-admin.dropdown align="right" width="w-56">
            <x-slot:trigger>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg">
                    Options
                </button>
            </x-slot:trigger>

            <a href="#" class="block px-4 py-2 hover:bg-gray-100">Edit</a>
            <a href="#" class="block px-4 py-2 hover:bg-gray-100">Delete</a>
        </x-admin.dropdown>

    @accessibility
        - ARIA menu role
        - Keyboard navigation (Arrow keys, Escape, Enter)
        - Focus management
        - Click outside to close
--}}

@props([
    'align' => 'right',
    'width' => 'w-48',
    'contentClasses' => 'py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700',
])

@php
$alignmentClasses = [
    'left' => 'left-0',
    'right' => 'right-0',
    'center' => 'left-1/2 -translate-x-1/2',
];

$alignClass = $alignmentClasses[$align] ?? $alignmentClasses['right'];
$triggerId = 'dd_trigger_' . uniqid();
$menuId = 'dd_menu_' . uniqid();
@endphp

<div
    class="relative"
    x-data="{
        open: false,
        toggle() {
            if (this.open) {
                this.close()
            } else {
                this.open = true
            }
        },
        close() {
            this.open = false
        }
    }"
    @click.outside="close()"
    @keydown.escape.window="close()"
>
    {{-- Trigger --}}
    <div @click="toggle()" class="inline-flex">
        @if(isset($trigger))
            {{ $trigger }}
        @else
            <button
                type="button"
                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800
                       border border-gray-300 dark:border-gray-600 rounded-lg
                       text-sm font-medium text-gray-700 dark:text-gray-300
                       hover:bg-gray-50 dark:hover:bg-gray-700
                       focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                       transition-colors duration-200"
                :aria-expanded="open"
                id="{{ $triggerId }}"
                aria-controls="{{ $menuId }}"
            >
                Options
                <svg
                    class="ml-2 -mr-1 h-5 w-5 transition-transform duration-200"
                    :class="{ 'rotate-180': open }"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        @endif
    </div>

    {{-- Dropdown Content --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="absolute z-50 mt-2 {{ $width }} rounded-lg shadow-lg $alignClass"
        style="display: none;"
        @click="close()"
        role="menu"
        aria-orientation="vertical"
        id="{{ $menuId }}"
        :aria-labelledby="'{{ $triggerId }}'"
        x-init="(() => {
            const items = Array.from($el.querySelectorAll('a, button, [role=menuitem]'));
            items.forEach((el, idx) => {
                el.setAttribute('role','menuitem');
                el.setAttribute('tabindex','-1');
                el.addEventListener('keydown', (ev) => {
                    const k = ev.key;
                    if (k === 'ArrowDown' || k === 'ArrowRight') { ev.preventDefault(); (items[idx+1]||items[0]).focus(); }
                    if (k === 'ArrowUp' || k === 'ArrowLeft') { ev.preventDefault(); (items[idx-1]||items[items.length-1]).focus(); }
                    if (k === 'Home') { ev.preventDefault(); items[0].focus(); }
                    if (k === 'End') { ev.preventDefault(); items[items.length-1].focus(); }
                    if (k === 'Escape') { close(); const t=document.getElementById('{{ $triggerId }}'); t && t.focus(); }
                });
            });
            // Açılışta ilk öğeye odakla
            $watch('open', (val) => { if (val && items[0]) { setTimeout(() => items[0].focus(), 0); } });
        })()"
    >
        <div class="{{ $contentClasses }} rounded-lg">
            {{ $slot }}
        </div>
    </div>
</div>
