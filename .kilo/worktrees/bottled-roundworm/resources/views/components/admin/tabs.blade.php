{{--
    Modern Tabs Component
    Context7 compliant, Tailwind CSS, Alpine.js

    Kullanım:
    <x-admin.tabs>
        <x-slot name="tab1">
            <x-slot name="label">Genel Bilgiler</x-slot>
            <x-slot name="icon">
                <svg>...</svg>
            </x-slot>
            <p>Tab 1 içeriği</p>
        </x-slot>

        <x-slot name="tab2">
            <x-slot name="label">Detaylar</x-slot>
            <p>Tab 2 içeriği</p>
        </x-slot>
    </x-admin.tabs>

    @context7-compliant true
    @tailwind-only true
--}}

@props([
    'defaultTab' => 1,
    'variant' => 'default', // default, pills, underline
    'fullWidth' => false,
])

@php
    // Tab slot'larını topla
    $tabs = [];
    $tabIndex = 1;

    foreach ($attributes->getAttributes() as $key => $value) {
        if (str_starts_with($key, 'tab')) {
            $tabs[$tabIndex] = $value ?? '';
            $tabIndex++;
        }
    }

    // Variant styles
    $variantClasses = [
        'default' => [
            'container' => 'border-b border-gray-200 dark:border-gray-700',
            'button' => 'px-4 py-2.5 text-sm font-medium rounded-t-lg border-b-2 transition-all duration-200',
            'active' => 'border-blue-600 text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20',
            'inactive' => 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'
        ],
        'pills' => [
            'container' => 'bg-gray-100 dark:bg-gray-800 p-1 rounded-lg',
            'button' => 'px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200',
            'active' => 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm dark:shadow-none',
            'inactive' => 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
        ],
        'underline' => [
            'container' => '',
            'button' => 'px-4 py-2.5 text-sm font-medium border-b-2 transition-all duration-200',
            'active' => 'border-blue-600 text-blue-600 dark:text-blue-400',
            'inactive' => 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'
        ]
    ];

    $styles = $variantClasses[$variant] ?? $variantClasses['default'];
@endphp

<div x-data="{ activeTab: {{ $defaultTab }} }" class="w-full">
    {{-- Tab Navigation --}}
    <div class="flex {{ $fullWidth ? 'justify-between' : 'gap-2' }} $styles['container']" role="tablist">
        @foreach($tabs as $index => $content)
            <button
                @click="activeTab = {{ $index }}"
                :class="activeTab === {{ $index }} ? '{{ $styles['active'] }}' : $styles['inactive']"
                class="{{ $styles['button'] }} $fullWidth ? 'flex-1' : '' focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800"
                role="tab"
                :aria-selected="activeTab === {{ $index }}"
                :id="'tab-{{ $index }}'"
                :aria-controls="'panel-{{ $index }}'">

                {{-- Tab Icon (opsiyonel) --}}
                @if(isset(${"tab{$index}Icon"}))
                    <span class="mr-2">
                        {{ ${"tab{$index}Icon"} }}
                    </span>
                @endif

                {{-- Tab Label --}}
                {{ ${"tab{$index}Label"} ?? "Tab {$index}" }}

                {{-- Badge (opsiyonel) --}}
                @if(isset(${"tab{$index}Badge"}))
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ ${"tab{$index}Badge"} }}
                    </span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Tab Content --}}
    <div class="mt-6">
        @foreach($tabs as $index => $content)
            <div
                x-show="activeTab === {{ $index }}"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                role="tabpanel"
                :id="'panel-{{ $index }}'"
                :aria-labelledby="'tab-{{ $index }}'">

                {{ ${"tab{$index}"} }}
            </div>
        @endforeach
    </div>
</div>
