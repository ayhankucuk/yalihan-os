{{-- Badge partial --}}
@php
    $badgeMap = [
        'healthy'   => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400',
        'running'   => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400',
        'applied'   => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400',
        'pending'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400',
        'warning'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400',
        'stopped'   => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-400',
        'unknown'   => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-400',
        'missing'   => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-400',
        'malformed' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400',
        'error'     => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400',
        'failed'    => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400',
        'unreadable'=> 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400',
    ];
    $classes = $badgeMap[$value] ?? $badgeMap['unknown'];
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $classes }}">
    {{ ucfirst($value) }}
</span>
