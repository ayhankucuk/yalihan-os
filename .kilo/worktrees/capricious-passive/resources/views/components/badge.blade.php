@props(['value' => '', 'category' => 'default', 'size' => 'sm'])

@php
$baseClasses = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-all duration-200';

$sizeClasses = match($size) {
    'lg' => 'px-3 py-1 text-sm',
    'md' => 'px-3 py-1',
    default => 'px-2.5 py-0.5 text-xs',
};

// Category-specific color schemes
$colorClasses = match($category) {
    'status' => match(strtolower($value)) {
        'aktif', 'active', 'yayında', 'published' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'pasif', 'inactive', 'taslak', 'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        'onay bekliyor', 'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'satıldı', 'sold' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'arşivlendi', 'archived' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    },
    'priority' => match(strtolower($value)) {
        'high', 'yüksek' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'medium', 'orta' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'low', 'düşük' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    },
    'type' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
    'flag' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    'unread' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
};
@endphp

<span {{ $attributes->merge(['class' => "$baseClasses $sizeClasses $colorClasses"]) }}>
    {{ $value }}
</span>
