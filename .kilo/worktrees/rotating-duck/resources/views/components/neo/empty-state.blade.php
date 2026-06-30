@props([
    'title' => 'Kayıt bulunamadı',
    'description' => 'Görüntülenecek veri yok.',
    'actionHref' => null,
    'actionText' => null,
])
<div class="text-center py-12">
    <div class="w-16 h-16 bg-gradient-to-r from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 dark:from-gray-800 dark:to-gray-700">
        @if (isset($icon))
            {{ $icon }}
        @else
            <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
        @endif
    </div>
    <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-2 dark:text-white">{{ $title }}</h3>
    <p class="text-gray-500 dark:text-gray-400">{{ $description }}</p>

    @if ($actionHref && $actionText)
        <div class="mt-6">
            <a href="{{ $actionHref }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg dark:shadow-none">
                {{ $actionText }}
            </a>
        </div>
    @endif
</div>
