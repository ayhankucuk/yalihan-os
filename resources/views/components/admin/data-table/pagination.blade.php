{{-- resources/views/components/admin/data-table/pagination.blade.php --}}
@props([
    'pagination' => null,
])

@if($pagination && $pagination->hasPages())
@php
    $currentPage = $pagination->currentPage();
    $lastPage = $pagination->lastPage();
    $perPage = $pagination->perPage();
    $total = $pagination->total();
    $from = $pagination->firstItem() ?? 0;
    $to = $pagination->lastItem() ?? 0;

    // Build URL with page param
    $buildUrl = function($page) {
        $params = request()->except(['page']);
        $params['page'] = $page;
        return url()->current() . '?' . http_build_query($params);
    };

    // Get page range to display
    $getPages = function() use ($currentPage, $lastPage) {
        $pages = [];
        $delta = 2;

        for ($i = max(1, $currentPage - $delta); $i <= min($lastPage, $currentPage + $delta); $i++) {
            $pages[] = $i;
        }

        // Add first and last pages if not in range
        if (!in_array(1, $pages)) {
            array_unshift($pages, 1);
            if ($currentPage > $delta + 2) {
                array_splice($pages, 1, 0, '...');
            }
        }
        if (!in_array($lastPage, $pages)) {
            if ($currentPage < $lastPage - $delta - 1) {
                $pages[] = '...';
            }
            $pages[] = $lastPage;
        }

        return $pages;
    };

    $pages = $getPages();
@endphp

<nav class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4" role="navigation" aria-label="Pagination">
    {{-- Results Info --}}
    <div class="text-sm text-slate-500 dark:text-slate-400">
        Showing <span class="font-medium text-slate-700 dark:text-slate-300">{{ $from }}</span>
        to <span class="font-medium text-slate-700 dark:text-slate-300">{{ $to }}</span>
        of <span class="font-medium text-slate-700 dark:text-slate-300">{{ $total }}</span> results
    </div>

    {{-- Page Buttons --}}
    <div class="flex items-center gap-1">
        {{-- Previous --}}
        @if($pagination->onFirstPage())
        <span class="px-3 py-2 text-sm text-slate-400 cursor-not-allowed rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </span>
        @else
        <a href="{{ $buildUrl($currentPage - 1) }}"
           class="px-3 py-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
           rel="prev">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        @endif

        {{-- Page Numbers --}}
        @foreach($pages as $page)
            @if($page === '...')
            <span class="px-3 py-2 text-sm text-slate-400">...</span>
            @elseif($page === $currentPage)
            <span class="px-3 py-2 text-sm font-medium bg-indigo-600 text-white rounded-lg" aria-current="page">
                {{ $page }}
            </span>
            @else
            <a href="{{ $buildUrl($page) }}"
               class="px-3 py-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                {{ $page }}
            </a>
            @endif
        @endforeach

        {{-- Next --}}
        @if($pagination->hasMorePages())
        <a href="{{ $buildUrl($currentPage + 1) }}"
           class="px-3 py-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
           rel="next">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </a>
        @else
        <span class="px-3 py-2 text-sm text-slate-400 cursor-not-allowed rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </span>
        @endif
    </div>
</nav>
@endif
