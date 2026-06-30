@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'headerClass' => 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30',
    'bodyClass' => 'p-6',
    'footerClass' => 'bg-gray-50 dark:bg-gray-800 px-6 py-3',
    'hasFooter' => false,
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-shadow duration-300 dark:shadow-none']) }}>
    @if($title || $subtitle || $icon)
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-600 {{ $headerClass }} dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    @if($icon)
                        <div class="flex-shrink-0 bg-blue-500 rounded-lg p-3 mr-4">
                            {!! $icon !!}
                        </div>
                    @endif
                    <div>
                        @if($title)
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200">{{ $title }}</h3>
                        @endif
                        @if($subtitle)
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>
                @if(isset($headerActions))
                    <div>
                        {{ $headerActions }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="{{ $bodyClass }}">
        {{ $slot }}
    </div>

    @if($hasFooter || isset($footer))
        <div class="{{ $footerClass }}">
            @if(isset($footer))
                {{ $footer }}
            @endif
        </div>
    @endif
</div>
