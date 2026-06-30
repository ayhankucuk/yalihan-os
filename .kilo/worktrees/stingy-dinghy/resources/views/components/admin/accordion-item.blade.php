{{--
    Accordion Item Component
    Context7 compliant, Tailwind CSS, Alpine.js

    Kullanım:
    <x-admin.accordion-item
        title="Başlık"
        :open="false"
        icon="<svg>...</svg>">
        İçerik buraya
    </x-admin.accordion-item>

    @context7-compliant true
    @tailwind-only true
--}}

@props([
    'title' => 'Accordion Item',
    'open' => false,
    'icon' => null,
    'bordered' => true,
])

@php
    // Unique ID oluştur
    $uniqueId = 'accordion-' . uniqid();
@endphp

<div
    x-data="{
        id: '{{ $uniqueId }}',
        isOpen: {{ $open ? 'true' : 'false' }},
        init() {
            if (this.isOpen) {
                if (this.$parent.allowMultiple) {
                    this.$parent.activeItems.push(this.id);
                } else {
                    this.$parent.activeItems = this.id;
                }
            }
        }
    }"
    class="{{ $bordered ? 'bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden' : '' }} dark:shadow-none dark:border-slate-700">

    {{-- Accordion Header --}}
    <button
        @click="$parent.toggle(id)"
        :aria-expanded="$parent.isOpen(id)"
        :aria-controls="'content-' + id"
        :id="'button-' + id"
        class="w-full flex items-center justify-between px-6 py-4 text-left transition-all duration-200 {{ $bordered ? 'hover:bg-gray-50 dark:hover:bg-gray-700/50' : 'hover:bg-gray-100 dark:hover:bg-gray-800' }} focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 dark:focus:ring-blue-400"
        type="button">

        <div class="flex items-center gap-3 flex-1">
            {{-- Custom Icon --}}
            @if($icon)
                <span class="flex-shrink-0 text-gray-600 dark:text-gray-400">
                    {{ $icon }}
                </span>
            @endif

            {{-- Title --}}
            <span class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                {{ $title }}
            </span>
        </div>

        {{-- Chevron Icon --}}
        <svg
            :class="$parent.isOpen(id) ? 'rotate-180' : ''"
            class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform duration-200 flex-shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Accordion Content --}}
    <div
        x-show="$parent.isOpen(id)"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 max-h-0"
        x-transition:enter-end="opacity-100 max-h-screen"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 max-h-screen"
        x-transition:leave-end="opacity-0 max-h-0"
        x-cloak
        :id="'content-' + id"
        :aria-labelledby="'button-' + id"
        role="region"
        class="overflow-hidden">

        <div class="px-6 py-4 {{ $bordered ? 'border-t border-gray-200 dark:border-gray-700' : '' }} bg-gray-50 dark:bg-gray-800/50 dark:bg-slate-900 dark:border-slate-700">
            <div class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
