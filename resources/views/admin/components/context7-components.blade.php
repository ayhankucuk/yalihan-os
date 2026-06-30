{{-- Context7 Standard Design System Components --}}

{{-- Page Header Component --}}
@php
$pageHeaderClass = 'content-header mb-8';
$pageTitleClass = 'text-3xl font-bold flex items-center text-gray-800';
$pageDescClass = 'text-lg text-gray-600 mt-2';
$pageIconClass = 'w-12 h-12 rounded-xl flex items-center justify-center mr-4';
@endphp

{{-- Stats Card Component --}}
@php
$statsCardClass = 'bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 dark:shadow-none';
$statsIconClass = 'w-12 h-12 rounded-lg flex items-center justify-center';
$statsValueClass = 'text-2xl font-bold';
$statsLabelClass = 'text-sm text-gray-600 font-medium';
@endphp

{{-- Filter Component --}}
@php
$filterContainerClass = 'bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 mb-8 dark:shadow-none';
$filterTitleClass = 'text-lg font-semibold text-gray-900 mb-4 flex items-center dark:text-slate-100';
$filterInputClass = 'w-full px-3 py-2 rounded-md border border-gray-200 bg-white text-sm placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-800 dark:text-gray-100 transition-colors dark:placeholder:text-slate-500';
$filterLabelClass = 'block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300';
@endphp

{{-- Table Component --}}
@php
$tableContainerClass = 'bg-gray-50 dark:bg-gray-800 rounded-xl shadow-md dark:shadow-none';
$tableHeaderClass = 'px-6 py-4 border-b border-gray-200 dark:border-gray-700';
$tableTitleClass = 'text-lg font-semibold text-gray-900 dark:text-white';
$tableStatsClass = 'text-sm text-gray-500 dark:text-gray-400 flex items-center';
@endphp

{{-- Button Component --}}
@php
$btnPrimaryClass = 'inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none';
$btnSecondaryClass = 'inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 transition-all duration-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700';
$btnSuccessClass = 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200';
$btnDangerClass = 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200';
@endphp

{{-- Gradient Colors for Stats --}}
@php
$gradientColors = [
    'blue' => 'bg-gradient-to-r from-blue-500 to-indigo-600',
    'green' => 'bg-gradient-to-r from-green-500 to-emerald-600',
    'purple' => 'bg-gradient-to-r from-purple-500 to-violet-600',
    'orange' => 'bg-gradient-to-r from-orange-500 to-amber-600',
    'red' => 'bg-gradient-to-r from-red-500 to-rose-600',
    'yellow' => 'bg-gradient-to-r from-yellow-500 to-amber-500',
    'pink' => 'bg-gradient-to-r from-pink-500 to-rose-600',
    'indigo' => 'bg-gradient-to-r from-indigo-500 to-purple-600',
];
@endphp

{{-- Common SVG Icons --}}
@php
$icons = [
    'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>',
    'check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
    'chat' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>',
    'chart' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>',
    'plus' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>',
    'search' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>',
    'filter' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>',
    'close' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>',
    'stats' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>',
];
@endphp

{{-- Context7 Macros --}}

{{-- Page Header Macro --}}
@push('styles')
<style>
.container mx-auto px-4 py-6 {
    @apply space-y-6;
}
</style>
@endpush
