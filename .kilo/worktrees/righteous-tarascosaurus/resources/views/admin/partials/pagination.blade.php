@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Sayfa Navigasyonu" class="flex flex-col sm:flex-row items-center justify-between gap-4">
        {{-- Mobil View --}}
        <div class="flex justify-between items-center w-full sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 cursor-default leading-5 rounded-lg dark:text-gray-500 dark:bg-slate-900 dark:border-gray-600">
                    <i class="fas fa-chevron-left mr-2"></i>
                    Önceki
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-lg hover:bg-gray-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:bg-slate-900 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:text-slate-300">
                    <i class="fas fa-chevron-left mr-2"></i>
                    Önceki
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-lg hover:bg-gray-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:bg-slate-900 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:text-slate-300">
                    Sonraki
                    <i class="fas fa-chevron-right ml-2"></i>
                </a>
            @else
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 cursor-default leading-5 rounded-lg dark:text-gray-500 dark:bg-slate-900 dark:border-gray-600">
                    Sonraki
                    <i class="fas fa-chevron-right ml-2"></i>
                </span>
            @endif
        </div>

        {{-- Desktop View --}}
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700 leading-5 dark:text-gray-400 dark:text-slate-300">
                    @if ($paginator->firstItem())
                        <span class="font-medium">{{ $paginator->firstItem() }}</span>
                        -
                        <span class="font-medium">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    arası gösteriliyor, toplam
                    <span class="font-medium">{{ $paginator->total() }}</span>
                    sonuç
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex rounded-lg shadow-sm -space-x-px dark:shadow-none">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="Önceki Sayfa">
                            <span class="relative inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 cursor-default rounded-l-lg leading-5 dark:bg-slate-900 dark:border-gray-600 dark:text-gray-500" aria-hidden="true">
                                <i class="fas fa-chevron-left"></i>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-lg leading-5 hover:bg-gray-50 hover:text-blue-600 focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:bg-slate-900 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-blue-400" aria-label="Önceki Sayfa">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @if($paginator->lastPage() > 1)
                        @php
                            $start = max(1, $paginator->currentPage() - 2);
                            $end = min($paginator->lastPage(), $paginator->currentPage() + 2);
                        @endphp

                        {{-- First Page --}}
                        @if($start > 1)
                            <a href="{{ $paginator->url(1) }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:bg-gray-50 hover:text-blue-600 focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:bg-slate-900 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-blue-400 dark:text-slate-300">
                                1
                            </a>
                            @if($start > 2)
                                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 cursor-default leading-5 dark:bg-slate-900 dark:border-gray-600 dark:text-gray-400 dark:text-slate-300">...</span>
                            @endif
                        @endif

                        {{-- Page Numbers --}}
                        @for($page = $start; $page <= $end; $page++)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 cursor-default leading-5 shadow-sm dark:shadow-none">{{ $page }}</span>
                            @else
                                <a href="{{ $paginator->url($page) }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:bg-gray-50 hover:text-blue-600 focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:bg-slate-900 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-blue-400 dark:text-slate-300" aria-label="Sayfa {{ $page }}ye git">
                                    {{ $page }}
                                </a>
                            @endif
                        @endfor

                        {{-- Last Page --}}
                        @if($end < $paginator->lastPage())
                            @if($end < $paginator->lastPage() - 1)
                                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 cursor-default leading-5 dark:bg-slate-900 dark:border-gray-600 dark:text-gray-400 dark:text-slate-300">...</span>
                            @endif
                            <a href="{{ $paginator->url($paginator->lastPage()) }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:bg-gray-50 hover:text-blue-600 focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:bg-slate-900 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-blue-400 dark:text-slate-300">
                                {{ $paginator->lastPage() }}
                            </a>
                        @endif
                    @endif

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-lg leading-5 hover:bg-gray-50 hover:text-blue-600 focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:bg-slate-900 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-blue-400" aria-label="Sonraki Sayfa">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="Sonraki Sayfa">
                            <span class="relative inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 cursor-default rounded-r-lg leading-5 dark:bg-slate-900 dark:border-gray-600 dark:text-gray-500" aria-hidden="true">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
