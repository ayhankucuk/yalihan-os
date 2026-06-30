@props(['danisman', 'size' => 'sm', 'variant' => 'default'])

@php
    $socialLinks = [];
    
    if (!empty($danisman->instagram_profile)) {
        $socialLinks['instagram'] = [
            'url' => $danisman->instagram_profile,
            'icon' => 'fab fa-instagram',
            'color' => 'bg-gradient-to-r from-pink-500 to-purple-600',
            'hover' => 'hover:from-pink-600 hover:to-purple-700',
            'label' => 'Instagram'
        ];
    }
    
    if (!empty($danisman->linkedin_profile)) {
        $socialLinks['linkedin'] = [
            'url' => $danisman->linkedin_profile,
            'icon' => 'fab fa-linkedin',
            'color' => 'bg-gradient-to-r from-blue-600 to-blue-700',
            'hover' => 'hover:from-blue-700 hover:to-blue-800',
            'label' => 'LinkedIn'
        ];
    }
    
    if (!empty($danisman->facebook_profile)) {
        $socialLinks['facebook'] = [
            'url' => $danisman->facebook_profile,
            'icon' => 'fab fa-facebook',
            'color' => 'bg-gradient-to-r from-blue-700 to-blue-800',
            'hover' => 'hover:from-blue-800 hover:to-blue-900',
            'label' => 'Facebook'
        ];
    }
    
    if (!empty($danisman->twitter_profile)) {
        $socialLinks['twitter'] = [
            'url' => $danisman->twitter_profile,
            'icon' => 'fab fa-twitter',
            'color' => 'bg-gradient-to-r from-blue-400 to-blue-500',
            'hover' => 'hover:from-blue-500 hover:to-blue-600',
            'label' => 'Twitter'
        ];
    }
    
    if (!empty($danisman->youtube_channel)) {
        $socialLinks['youtube'] = [
            'url' => $danisman->youtube_channel,
            'icon' => 'fab fa-youtube',
            'color' => 'bg-gradient-to-r from-red-600 to-red-700',
            'hover' => 'hover:from-red-700 hover:to-red-800',
            'label' => 'YouTube'
        ];
    }
    
    if (!empty($danisman->tiktok_profile)) {
        $socialLinks['tiktok'] = [
            'url' => $danisman->tiktok_profile,
            'icon' => 'fab fa-tiktok',
            'color' => 'bg-gradient-to-r from-gray-900 to-black',
            'hover' => 'hover:from-black hover:to-gray-900',
            'label' => 'TikTok'
        ];
    }
    
    if (!empty($danisman->whatsapp_number)) {
        $whatsappUrl = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $danisman->whatsapp_number);
        $socialLinks['whatsapp'] = [
            'url' => $whatsappUrl,
            'icon' => 'fab fa-whatsapp',
            'color' => 'bg-gradient-to-r from-green-500 to-green-600',
            'hover' => 'hover:from-green-600 hover:to-green-700',
            'label' => 'WhatsApp'
        ];
    }
    
    if (!empty($danisman->telegram_username)) {
        $telegramUrl = 'https://t.me/' . ltrim($danisman->telegram_username, '@');
        $socialLinks['telegram'] = [
            'url' => $telegramUrl,
            'icon' => 'fab fa-telegram',
            'color' => 'bg-gradient-to-r from-blue-500 to-blue-600',
            'hover' => 'hover:from-blue-600 hover:to-blue-700',
            'label' => 'Telegram'
        ];
    }
    
    if (!empty($danisman->website)) {
        $socialLinks['website'] = [
            'url' => $danisman->website,
            'icon' => 'fas fa-globe',
            'color' => 'bg-gradient-to-r from-indigo-600 to-purple-600',
            'hover' => 'hover:from-indigo-700 hover:to-purple-700',
            'label' => 'Website'
        ];
    }
    
    $sizeClasses = [
        'xs' => 'w-6 h-6',
        'sm' => 'w-8 h-8',
        'md' => 'w-10 h-10',
        'lg' => 'w-12 h-12'
    ];
    
    $iconSizeClasses = [
        'xs' => 'text-xs',
        'sm' => 'text-sm',
        'md' => 'text-base',
        'lg' => 'text-lg'
    ];
    
    $currentSize = $sizeClasses[$size] ?? $sizeClasses['sm'];
    $currentIconSize = $iconSizeClasses[$size] ?? $iconSizeClasses['sm'];
    
    $variantClasses = [
        'default' => 'text-white shadow-md hover:shadow-lg dark:shadow-none',
        'outline' => 'text-gray-700 dark:text-gray-300 border-2 border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500',
        'minimal' => 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100'
    ];
    
    $currentVariant = $variantClasses[$variant] ?? $variantClasses['default'];
@endphp

@if(count($socialLinks) > 0)
    <div class="flex items-center gap-2 flex-wrap">
        @foreach($socialLinks as $key => $link)
            @if($variant === 'outline' || $variant === 'minimal')
                <a href="{{ $link['url'] }}" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="inline-flex items-center justify-center {{ $currentSize }} rounded-full $currentVariant transition-all duration-200 hover:-translate-y-0.5 active:scale-95 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                   title="{{ $link['label'] }}"
                   aria-label="{{ $link['label'] }}">
                    <i class="{{ $link['icon'] }} $currentIconSize"></i>
                </a>
            @else
                <a href="{{ $link['url'] }}" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="inline-flex items-center justify-center {{ $currentSize }} rounded-full $link['color'] $link['hover'] $currentVariant transition-all duration-200 hover:-translate-y-0.5 active:scale-95 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                   title="{{ $link['label'] }}"
                   aria-label="{{ $link['label'] }}">
                    <i class="{{ $link['icon'] }} $currentIconSize"></i>
                </a>
            @endif
        @endforeach
    </div>
@endif

